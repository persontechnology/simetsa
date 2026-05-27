<?php
// tests/Feature/RolPermisoTest.php

namespace Tests\Feature;

use App\Enums\RolSistema;
use Database\Seeders\RolPermisoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests del seeder RolPermisoSeeder.
 *
 * Garantiza que el cuadro de roles y permisos coincide con la Ordenanza
 * SIMETSA y que las asignaciones críticas no se rompen en cambios futuros.
 */
class RolPermisoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ejecuta el seeder antes de cada test para tener un escenario limpio.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolPermisoSeeder::class);
    }

    /**
     * Los 6 roles definidos en el Enum RolSistema deben existir en BD.
     *
     * @return void
     */
    public function test_los_seis_roles_del_sistema_existen(): void
    {
        foreach (RolSistema::cases() as $rol) {
            $this->assertDatabaseHas('roles', [
                'name'       => $rol->value,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * El super_admin debe tener TODOS los permisos del catálogo.
     *
     * @return void
     */
    public function test_super_admin_tiene_todos_los_permisos(): void
    {
        $rol = Role::findByName(RolSistema::SuperAdmin->value);
        $this->assertEquals(Permission::count(), $rol->permissions->count());
    }

    /**
     * El agente_parqueo debe poder vender tickets, registrar infracciones
     * y notificar al ECU 911 (Art. 24, 38.b, 38.m).
     *
     * @return void
     */
    public function test_agente_parqueo_tiene_permisos_de_calle(): void
    {
        $rol = Role::findByName(RolSistema::AgenteParqueo->value);
        $this->assertTrue($rol->hasPermissionTo('tickets.comprar'));
        $this->assertTrue($rol->hasPermissionTo('infracciones.registrar'));
        $this->assertTrue($rol->hasPermissionTo('reportes_ecu911.enviar'));
        $this->assertTrue($rol->hasPermissionTo('turnos.iniciar'));
    }

    /**
     * El comisario debe poder generar órdenes de pago, autorizar
     * inmovilizaciones y aprobar solicitudes de agentes (Art. 15, 28, 36).
     *
     * @return void
     */
    public function test_comisario_tiene_permisos_administrativos(): void
    {
        $rol = Role::findByName(RolSistema::Comisario->value);
        $this->assertTrue($rol->hasPermissionTo('ordenes_pago.generar'));
        $this->assertTrue($rol->hasPermissionTo('inmovilizaciones.aplicar'));
        $this->assertTrue($rol->hasPermissionTo('solicitudes_agente.aprobar'));
        $this->assertTrue($rol->hasPermissionTo('amonestaciones.registrar'));
    }

    /**
     * El director_seguridad debe poder gestionar catálogos base
     * y otorgar la autorización final a los agentes (Art. 10, 36).
     *
     * @return void
     */
    public function test_director_seguridad_tiene_permisos_de_catalogos_y_autorizacion(): void
    {
        $rol = Role::findByName(RolSistema::DirectorSeguridad->value);
        $this->assertTrue($rol->hasPermissionTo('zonas.crear'));
        $this->assertTrue($rol->hasPermissionTo('manzanas.crear'));
        $this->assertTrue($rol->hasPermissionTo('tarifas.crear'));
        $this->assertTrue($rol->hasPermissionTo('solicitudes_agente.aprobar'));
        $this->assertTrue($rol->hasPermissionTo('liquidaciones.generar'));
    }

    /**
     * El conductor puede comprar tickets pero NO puede registrar
     * infracciones ni aplicar inmovilizaciones.
     *
     * @return void
     */
    public function test_conductor_no_tiene_permisos_de_fiscalizacion(): void
    {
        $rol = Role::findByName(RolSistema::Conductor->value);
        $this->assertTrue($rol->hasPermissionTo('tickets.comprar'));
        $this->assertFalse($rol->hasPermissionTo('infracciones.registrar'));
        $this->assertFalse($rol->hasPermissionTo('inmovilizaciones.aplicar'));
        $this->assertFalse($rol->hasPermissionTo('zonas.crear'));
    }

    /**
     * El punto_venta solo puede vender tickets, no administrar el sistema.
     *
     * @return void
     */
    public function test_punto_venta_solo_puede_vender_tickets(): void
    {
        $rol = Role::findByName(RolSistema::PuntoVenta->value);
        $this->assertTrue($rol->hasPermissionTo('tickets.comprar'));
        $this->assertTrue($rol->hasPermissionTo('comprobantes.emitir'));
        $this->assertFalse($rol->hasPermissionTo('agentes.crear'));
        $this->assertFalse($rol->hasPermissionTo('zonas.crear'));
        $this->assertFalse($rol->hasPermissionTo('infracciones.registrar'));
    }
}