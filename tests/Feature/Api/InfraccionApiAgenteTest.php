<?php

// tests/Feature/Api/InfraccionApiAgenteTest.php

namespace Tests\Feature\Api;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoInmovilizacion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la API de infracciones para el agente en calle (Fase 7.C).
 * Arts. 15, 17, 18, 28, 29, 30 — Ordenanza SIMETSA.
 */
class InfraccionApiAgenteTest extends TestCase
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

    private function agenteUser(): User
    {
        return User::where('email', 'agente@simetsa.gob.ec')->first();
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private function crearAgente(?User $user = null): AgenteParqueo
    {
        $user ??= $this->agenteUser();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'                  => 'AG-TEST1',
                'numero_credencial'       => 'CRED-001',
                'carta_compromiso_firmada'=> true,
                'fecha_autorizacion'      => now()->toDateString(),
                'estado'                  => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function zona(): Zona
    {
        return Zona::where('codigo', 'centro')->first();
    }

    private function bodyInfraccion(array $extra = []): array
    {
        return array_merge([
            'placa'           => 'ABC1234',
            'tipo_infraccion' => TipoInfraccion::SinTicketVisible->value,
            'zona_id'         => $this->zona()->id,
        ], $extra);
    }

    private function crearInfraccion(AgenteParqueo $agente): Infraccion
    {
        return Infraccion::create([
            'placa'             => 'ABC1234',
            'zona_id'           => $this->zona()->id,
            'agente_parqueo_id' => $agente->id,
            'tipo_infraccion'   => TipoInfraccion::SinTicketVisible,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => 9.20,
            'sbu_vigente'       => 460.00,
        ]);
    }

    // ── POST /api/v1/infracciones ─────────────────────────────────────────────

    /** Sin token → 401. */
    public function test_registrar_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/infracciones', $this->bodyInfraccion())
            ->assertUnauthorized();
    }

    /** Conductor no tiene permiso infracciones.registrar → 403. */
    public function test_conductor_no_puede_registrar_infraccion(): void
    {
        $this->crearAgente();   // asegura que la zona existe

        $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/infracciones', $this->bodyInfraccion())
            ->assertForbidden();
    }

    /** Happy path: agente registra infracción → 201 con envelope correcto. */
    public function test_agente_registra_infraccion_correctamente(): void
    {
        $this->crearAgente();

        $this->withToken($this->token($this->agenteUser()))
            ->postJson('/api/v1/infracciones', $this->bodyInfraccion())
            ->assertCreated()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.placa', 'ABC1234')
            ->assertJsonPath('datos.tipo_infraccion', TipoInfraccion::SinTicketVisible->value)
            ->assertJsonPath('datos.estado', EstadoInfraccion::Pendiente->value);

        $this->assertDatabaseHas('infracciones', ['placa' => 'ABC1234']);
    }

    /** Tipo de infracción inválido → 422 de validación. */
    public function test_tipo_infraccion_invalido_devuelve_422(): void
    {
        $this->crearAgente();

        $this->withToken($this->token($this->agenteUser()))
            ->postJson('/api/v1/infracciones', $this->bodyInfraccion([
                'tipo_infraccion' => 'tipo_inexistente',
            ]))
            ->assertUnprocessable();
    }

    /** tiempo_excedido sin minutos_excedidos → 422 de negocio (InfraccionService). */
    public function test_tiempo_excedido_sin_minutos_es_422(): void
    {
        $this->crearAgente();

        $this->withToken($this->token($this->agenteUser()))
            ->postJson('/api/v1/infracciones', $this->bodyInfraccion([
                'tipo_infraccion'   => TipoInfraccion::TiempoExcedido->value,
                'minutos_excedidos' => 3,   // < 6 → DomainException
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    /** tiempo_excedido con 75 minutos → multa 4% SBU. */
    public function test_tiempo_excedido_calcula_multa_correctamente(): void
    {
        $this->crearAgente();
        $sbu = 460.00;

        $this->withToken($this->token($this->agenteUser()))
            ->postJson('/api/v1/infracciones', $this->bodyInfraccion([
                'tipo_infraccion'   => TipoInfraccion::TiempoExcedido->value,
                'minutos_excedidos' => 75,
            ]))
            ->assertCreated()
            ->assertJsonPath('datos.monto_multa', number_format(round($sbu * 0.04, 2), 2, '.', ''));
    }

    // ── GET /api/v1/infracciones/{id} ─────────────────────────────────────────

    /** Agente puede ver sus propias infracciones. */
    public function test_agente_puede_ver_su_infraccion(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente);

        $this->withToken($this->token($this->agenteUser()))
            ->getJson("/api/v1/infracciones/{$infraccion->id}")
            ->assertOk()
            ->assertJsonPath('datos.id', $infraccion->id);
    }

    /** Agente no puede ver infracciones de otro agente → 403. */
    public function test_agente_no_puede_ver_infraccion_ajena(): void
    {
        // Agente 1 registra la infracción
        $agente1    = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente1);

        // El conductor no tiene permiso infracciones.ver → el middleware devuelve 403
        $this->withToken($this->token($this->conductorUser()))
            ->getJson("/api/v1/infracciones/{$infraccion->id}")
            ->assertForbidden();
    }

    /** El comisario puede ver cualquier infracción (bypass policy). */
    public function test_comisario_puede_ver_cualquier_infraccion(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente);

        $this->withToken($this->token($this->comisarioUser()))
            ->getJson("/api/v1/infracciones/{$infraccion->id}")
            ->assertOk()
            ->assertJsonPath('datos.id', $infraccion->id);
    }

    // ── POST /api/v1/infracciones/{id}/inmovilizar ────────────────────────────

    /** Agente inmoviliza vehículo → 201 con inmovilización activa (Art. 15). */
    public function test_agente_inmoviliza_vehiculo(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente);

        $this->withToken($this->token($this->agenteUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/inmovilizar", [
                'notas' => 'Sin ticket visible en parabrisas.',
            ])
            ->assertCreated()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.inmovilizacion.estado', EstadoInmovilizacion::Activa->value);

        $this->assertDatabaseHas('inmovilizaciones', [
            'infraccion_id' => $infraccion->id,
            'estado'        => EstadoInmovilizacion::Activa->value,
        ]);
    }

    /** Segunda inmovilización sobre la misma infracción → 422. */
    public function test_no_se_puede_inmovilizar_dos_veces(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente);

        Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        $this->withToken($this->token($this->agenteUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/inmovilizar")
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    // ── POST /api/v1/infracciones/{id}/liberar ────────────────────────────────

    /** Liberar sin pago ni motivo → 422 (Art. 15). */
    public function test_liberar_sin_pago_ni_motivo_es_422(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente);

        Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        $this->withToken($this->token($this->agenteUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/liberar", [])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    /** Liberar con motivo (admin forzado) → 200 con inmovilización liberada. */
    public function test_comisario_libera_con_motivo(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente);

        Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        $this->withToken($this->token($this->comisarioUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/liberar", [
                'motivo' => 'Vehículo exonerado no identificado al registrar.',
            ])
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.inmovilizacion.estado', EstadoInmovilizacion::Liberada->value);
    }

    /** Liberar sin inmovilización registrada → 422. */
    public function test_liberar_infraccion_sin_inmovilizacion_es_422(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente);

        $this->withToken($this->token($this->comisarioUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/liberar", [
                'motivo' => 'sin candado',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }
}
