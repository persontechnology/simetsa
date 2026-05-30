<?php

// tests/Feature/InfraccionControllerTest.php

namespace Tests\Feature;

use App\Enums\EstadoInfraccion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del backoffice de supervisión de infracciones (Fase 7.E).
 * Arts. 15, 28-30 — Ordenanza SIMETSA.
 */
class InfraccionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
            ParametroSeeder::class,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function comisario(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function director(): User
    {
        return User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
    }

    private function conductor(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function crearAgente(): AgenteParqueo
    {
        $user = User::where('email', 'agente@simetsa.gob.ec')->first();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'                  => 'AG-TEST1',
                'numero_credencial'       => 'C-TEST1',
                'carta_compromiso_firmada'=> true,
                'fecha_autorizacion'      => now()->toDateString(),
                'estado'                  => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function crearInfraccion(
        EstadoInfraccion $estado = EstadoInfraccion::Pendiente
    ): Infraccion {
        $agente = $this->crearAgente();

        return Infraccion::create([
            'placa'             => 'ABC1234',
            'zona_id'           => Zona::where('codigo', 'centro')->first()->id,
            'agente_parqueo_id' => $agente->id,
            'tipo_infraccion'   => TipoInfraccion::SinTicketVisible,
            'estado'            => $estado,
            'monto_multa'       => 9.20,
            'sbu_vigente'       => 460.00,
        ]);
    }

    // ── GET /infracciones ─────────────────────────────────────────────────────

    /** Invitado no puede acceder al listado. */
    public function test_invitado_no_puede_ver_listado(): void
    {
        $this->get(route('infracciones.index'))->assertRedirect('/login');
    }

    /** Conductor no tiene acceso al backoffice. */
    public function test_conductor_no_puede_ver_listado(): void
    {
        $this->actingAs($this->conductor())
            ->get(route('infracciones.index'))
            ->assertForbidden();
    }

    /** Comisario puede ver el listado. */
    public function test_comisario_puede_ver_listado(): void
    {
        $this->crearInfraccion();

        $this->actingAs($this->comisario())
            ->get(route('infracciones.index'))
            ->assertOk()
            ->assertViewIs('infracciones.index')
            ->assertViewHas('infracciones');
    }

    /** Director puede ver el listado (solo lectura). */
    public function test_director_puede_ver_listado(): void
    {
        $this->actingAs($this->director())
            ->get(route('infracciones.index'))
            ->assertOk();
    }

    /** El filtro por placa funciona correctamente. */
    public function test_filtro_placa_funciona(): void
    {
        $this->crearInfraccion();   // placa ABC1234

        $this->actingAs($this->comisario())
            ->get(route('infracciones.index', ['placa' => 'XYZ9999']))
            ->assertOk()
            ->assertViewHas('infracciones', fn ($col) => $col->total() === 0);
    }

    // ── GET /infracciones/{id} ────────────────────────────────────────────────

    /** Comisario puede ver el detalle de una infracción. */
    public function test_comisario_puede_ver_detalle(): void
    {
        $infraccion = $this->crearInfraccion();

        $this->actingAs($this->comisario())
            ->get(route('infracciones.show', $infraccion))
            ->assertOk()
            ->assertViewIs('infracciones.show')
            ->assertViewHas('infraccion', fn ($i) => $i->id === $infraccion->id);
    }

    /** Conductor no puede ver el detalle desde backoffice. */
    public function test_conductor_no_puede_ver_detalle(): void
    {
        $infraccion = $this->crearInfraccion();

        $this->actingAs($this->conductor())
            ->get(route('infracciones.show', $infraccion))
            ->assertForbidden();
    }

    // ── PATCH /infracciones/{id}/anular ──────────────────────────────────────

    /** Comisario puede anular una infracción pendiente con motivo válido. */
    public function test_comisario_puede_anular_infraccion(): void
    {
        $infraccion = $this->crearInfraccion();

        $this->actingAs($this->comisario())
            ->patch(route('infracciones.anular', $infraccion), [
                'motivo' => 'Vehículo exonerado no registrado al momento.',
            ])
            ->assertRedirect(route('infracciones.show', $infraccion));

        $this->assertEquals(EstadoInfraccion::Anulada, $infraccion->fresh()->estado);
    }

    /** Anular sin motivo devuelve error de validación. */
    public function test_anular_sin_motivo_devuelve_error(): void
    {
        $infraccion = $this->crearInfraccion();

        $this->actingAs($this->comisario())
            ->patch(route('infracciones.anular', $infraccion), ['motivo' => ''])
            ->assertSessionHasErrors('motivo');
    }

    /** No se puede anular una infracción ya pagada. */
    public function test_no_puede_anular_infraccion_pagada(): void
    {
        $infraccion = $this->crearInfraccion(EstadoInfraccion::Pagada);

        $this->actingAs($this->comisario())
            ->patch(route('infracciones.anular', $infraccion), [
                'motivo' => 'Intento de anulación sobre pagada.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(EstadoInfraccion::Pagada, $infraccion->fresh()->estado);
    }
}
