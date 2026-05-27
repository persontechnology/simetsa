<?php
// tests/Feature/RolControllerTest.php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests del RolController.
 *
 * Cubre CRUD completo + protección de super_admin + restricciones
 * sobre roles del sistema + bloqueo de eliminación con usuarios asignados.
 */
class RolControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $comisario;
    private User $conductor;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);

        $this->superAdmin = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->comisario  = User::where('email', 'comisario@simetsa.gob.ec')->first();
        $this->conductor  = User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    /**
     * Un conductor no puede listar roles.
     *
     * @return void
     */
    public function test_conductor_no_puede_listar_roles(): void
    {
        $this->actingAs($this->conductor)
             ->get(route('roles.index'))
             ->assertForbidden();
    }

    /**
     * El super_admin puede listar todos los roles.
     *
     * @return void
     */
    public function test_super_admin_lista_roles(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('roles.index'))
             ->assertOk()
             ->assertSee('super_admin')
             ->assertSee('comisario');
    }

    /**
     * El super_admin puede crear un rol custom con permisos.
     *
     * @return void
     */
    public function test_super_admin_crea_rol_custom(): void
    {
        $datos = [
            'name'     => 'supervisor_zonal',
            'permisos' => ['zonas.ver', 'plazas.ver', 'turnos.ver'],
        ];

        $this->actingAs($this->superAdmin)
             ->post(route('roles.store'), $datos)
             ->assertRedirect();

        $rol = Role::where('name', 'supervisor_zonal')->first();
        $this->assertNotNull($rol);
        $this->assertCount(3, $rol->permissions);
    }

    /**
     * Crear con nombre inválido (no snake_case) falla.
     *
     * @return void
     */
    public function test_crear_falla_con_nombre_invalido(): void
    {
        $this->actingAs($this->superAdmin)
             ->post(route('roles.store'), ['name' => 'Mi Rol Inválido'])
             ->assertSessionHasErrors('name');
    }

    /**
     * No se permite crear rol con nombre duplicado.
     *
     * @return void
     */
    public function test_crear_falla_con_nombre_duplicado(): void
    {
        $this->actingAs($this->superAdmin)
             ->post(route('roles.store'), ['name' => 'comisario'])
             ->assertSessionHasErrors('name');
    }

    /**
     * El super_admin puede editar los permisos de un rol del sistema
     * que no sea super_admin (ej: agregar/quitar permisos al comisario).
     *
     * @return void
     */
    public function test_super_admin_edita_permisos_de_rol_del_sistema(): void
    {
        $comisarioRol = Role::findByName(RolSistema::Comisario->value);

        $this->actingAs($this->superAdmin)
             ->put(route('roles.update', $comisarioRol), [
                 'permisos' => ['ordenes_pago.generar', 'inmovilizaciones.aplicar'],
             ])
             ->assertRedirect();

        $comisarioRol->refresh();
        $this->assertCount(2, $comisarioRol->permissions);
    }

    /**
     * Nadie puede editar al rol super_admin (la policy lo bloquea).
     *
     * @return void
     */
    public function test_nadie_puede_editar_rol_super_admin(): void
    {
        $superRol = Role::findByName(RolSistema::SuperAdmin->value);

        $this->actingAs($this->superAdmin)
             ->get(route('roles.edit', $superRol))
             ->assertForbidden();
    }

    /**
     * Nadie puede eliminar al rol super_admin.
     *
     * @return void
     */
    public function test_nadie_puede_eliminar_rol_super_admin(): void
    {
        $superRol = Role::findByName(RolSistema::SuperAdmin->value);

        $this->actingAs($this->superAdmin)
             ->delete(route('roles.destroy', $superRol))
             ->assertForbidden();
    }

    /**
     * No se permite eliminar un rol del sistema (los 6 del Enum)
     * aunque no tenga usuarios.
     *
     * @return void
     */
    public function test_no_se_permite_eliminar_rol_del_sistema(): void
    {
        // El rol del director no tiene usuarios en algunos casos; agrego nada
        $directorRol = Role::findByName(RolSistema::DirectorSeguridad->value);

        $this->actingAs($this->superAdmin)
             ->delete(route('roles.destroy', $directorRol))
             ->assertForbidden();
    }

    /**
     * No se permite eliminar un rol que tenga usuarios asignados.
     *
     * @return void
     */
    public function test_no_se_permite_eliminar_rol_con_usuarios_asignados(): void
    {
        // Crear rol custom y asignarlo a un usuario
        $custom = Role::create(['name' => 'rol_custom_temporal3', 'guard_name' => 'web']);
        $this->conductor->assignRole('rol_custom_temporal3');

        $this->actingAs($this->superAdmin)
             ->delete(route('roles.destroy', $custom))
             ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'rol_custom_temporal3']);
    }

    /**
     * Sí se permite eliminar un rol custom sin usuarios.
     *
     * @return void
     */
    public function test_se_elimina_rol_custom_sin_usuarios(): void
    {
        Role::create(['name' => 'rol_sin_usuarios', 'guard_name' => 'web']);

        $rol = Role::findByName('rol_sin_usuarios');
        $this->actingAs($this->superAdmin)
             ->delete(route('roles.destroy', $rol))
             ->assertRedirect();

        $this->assertDatabaseMissing('roles', ['name' => 'rol_sin_usuarios']);
    }

    /**
     * Editar un rol del sistema NO permite cambiar el nombre
     * (el controlador descarta el campo aunque venga en el payload).
     *
     * @return void
     */
    public function test_editar_rol_del_sistema_no_cambia_el_nombre(): void
    {
        $comisarioRol = Role::findByName(RolSistema::Comisario->value);

        $this->actingAs($this->superAdmin)
             ->put(route('roles.update', $comisarioRol), [
                 'name'     => 'otro_nombre',
                 'permisos' => ['usuarios.ver'],
             ])
             ->assertRedirect();

        $comisarioRol->refresh();
        $this->assertEquals('comisario', $comisarioRol->name); // sigue igual
    }

    /**
     * Un super_admin NO debe poder eliminar ninguno de los 6 roles del sistema,
     * ni siquiera con UI o ruta manipulada. Reproduce el bug histórico.
     *
     * @return void
     */
    public function test_super_admin_no_puede_eliminar_ningun_rol_del_sistema(): void
    {
        foreach (\App\Enums\RolSistema::cases() as $rolEnum) {
            $rol = \Spatie\Permission\Models\Role::findByName($rolEnum->value);

            $this->actingAs($this->superAdmin)
                ->delete(route('roles.destroy', $rol))
                ->assertForbidden();

            $this->assertDatabaseHas('roles', ['name' => $rolEnum->value]);
        }
    }
}