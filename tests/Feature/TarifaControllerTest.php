<?php
// tests/Feature/TarifaControllerTest.php

namespace Tests\Feature;

use App\Models\Tarifa;
use App\Models\TipoPlaza;
use App\Models\User;
use App\Services\TarifaService;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TarifaSeeder;
use Database\Seeders\TipoPlazaSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del CRUD de Tarifas y del TarifaService.
 *
 * Cubre:
 *  - Seed correcto de tarifas iniciales.
 *  - Cálculo "por hora o fracción" del Art. 22.
 *  - Detección de solapamientos de rangos.
 *  - Permisos por rol.
 */
class TarifaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            TipoPlazaSeeder::class,
            TarifaSeeder::class,
        ]);
    }

    /**
     * El seeder carga 3 tarifas iniciales (normal, carga, taxi).
     *
     * @return void
     */
    public function test_seeder_carga_tarifas_iniciales(): void
    {
        $this->assertEquals(3, Tarifa::count());
        $tipoNormal = TipoPlaza::porCodigo('normal');
        $this->assertDatabaseHas('tarifas', [
            'tipo_plaza_id' => $tipoNormal->id,
            'valor_hora'    => '0.2500',
        ]);
    }

    /**
     * El cálculo del costo aplica "hora o fracción" del Art. 22.
     *
     * @return void
     */
    public function test_calculo_de_costo_aplica_hora_o_fraccion(): void
    {
        $tipo   = TipoPlaza::porCodigo('normal');
        $tarifa = Tarifa::where('tipo_plaza_id', $tipo->id)->first();
        $svc    = app(TarifaService::class);

        $this->assertEquals(0.00, $svc->calcularCosto($tarifa, 0));
        $this->assertEquals(0.25, $svc->calcularCosto($tarifa, 1));
        $this->assertEquals(0.25, $svc->calcularCosto($tarifa, 30));
        $this->assertEquals(0.25, $svc->calcularCosto($tarifa, 60));
        $this->assertEquals(0.50, $svc->calcularCosto($tarifa, 61));
        $this->assertEquals(0.50, $svc->calcularCosto($tarifa, 120));
        $this->assertEquals(0.75, $svc->calcularCosto($tarifa, 121));
    }

    /**
     * La tarifa vigente para hoy es la del seeder.
     *
     * @return void
     */
    public function test_tarifa_vigente_retorna_la_activa_para_hoy(): void
    {
        $tipo = TipoPlaza::porCodigo('normal');
        $svc  = app(TarifaService::class);

        $vigente = $svc->tarifaVigente($tipo->id);
        $this->assertNotNull($vigente);
        $this->assertEquals('0.2500', (string) $vigente->valor_hora);
    }

    /**
     * Una tarifa con fecha futura no es vigente hoy.
     *
     * @return void
     */
    public function test_tarifa_futura_no_es_vigente_hoy(): void
    {
        $tipo = TipoPlaza::porCodigo('discapacidad'); // exonerada, sin tarifa actual
        Tarifa::create([
            'tipo_plaza_id' => $tipo->id,
            'nombre'        => 'Futura',
            'valor_hora'    => 1.00,
            'vigente_desde' => now()->addMonth()->toDateString(),
            'activo'        => true,
        ]);

        $svc = app(TarifaService::class);
        $this->assertNull($svc->tarifaVigente($tipo->id, now()));
    }

    /**
     * No se permite crear una tarifa con rango que se solapa con otra activa.
     *
     * @return void
     */
    public function test_no_se_permite_crear_tarifa_solapada(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $tipo = TipoPlaza::porCodigo('normal');

        // Ya existe tarifa vigente desde 2020-02-10 sin fin
        $this->actingAs($u)->post(route('tarifas.store'), [
            'tipo_plaza_id' => $tipo->id,
            'nombre'        => 'Tarifa solapada',
            'valor_hora'    => 0.30,
            'vigente_desde' => '2025-01-01',
            'vigente_hasta' => '2025-12-31',
            'activo'        => 1,
        ])->assertSessionHasErrors('vigente_desde');
    }

    /**
     * Sí se permite cerrar la tarifa actual y crear una nueva contigua.
     *
     * @return void
     */
    public function test_se_permite_secuenciar_tarifas_sin_solapamiento(): void
    {
        $u    = User::where('email', 'admin@simetsa.gob.ec')->first();
        $tipo = TipoPlaza::porCodigo('normal');

        // 1) Cerrar la tarifa actual
        $actual = Tarifa::where('tipo_plaza_id', $tipo->id)->first();
        $this->actingAs($u)->put(route('tarifas.update', $actual), [
            'tipo_plaza_id' => $tipo->id,
            'nombre'        => $actual->nombre,
            'valor_hora'    => $actual->valor_hora,
            'vigente_desde' => $actual->vigente_desde->toDateString(),
            'vigente_hasta' => '2025-12-31',
            'activo'        => 1,
        ])->assertRedirect();

        // 2) Crear la nueva con vigencia 2026-01-01
        $this->actingAs($u)->post(route('tarifas.store'), [
            'tipo_plaza_id' => $tipo->id,
            'nombre'        => 'Tarifa 2026',
            'valor_hora'    => 0.30,
            'vigente_desde' => '2026-01-01',
            'activo'        => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('tarifas', [
            'nombre'     => 'Tarifa 2026',
            'valor_hora' => '0.3000',
        ]);
    }

    /**
     * Un conductor no puede acceder al listado de tarifas.
     *
     * @return void
     */
    public function test_conductor_no_accede_a_tarifas(): void
    {
        $conductor = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $this->actingAs($conductor)
             ->get(route('tarifas.index'))
             ->assertForbidden();
    }

    /**
     * El director_seguridad sí puede acceder.
     *
     * @return void
     */
    public function test_director_seguridad_accede_a_tarifas(): void
    {
        $director = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->actingAs($director)
             ->get(route('tarifas.index'))
             ->assertOk();
    }

    /**
     * Destroy hace soft delete (no elimina físicamente).
     *
     * @return void
     */
    public function test_destroy_hace_soft_delete(): void
    {
        $u = User::where('email', 'admin@simetsa.gob.ec')->first();
        $t = Tarifa::first();

        $this->actingAs($u)
             ->delete(route('tarifas.destroy', $t))
             ->assertRedirect();

        $this->assertSoftDeleted('tarifas', ['id' => $t->id]);
    }
}