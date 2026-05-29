<?php
// tests/Feature/UsuarioControllerTest.php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\PerfilUsuario;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del UsuarioController.
 *
 * Cubre flujo completo CRUD + autorización por rol + desactivación
 * lógica + reactivación.
 */
class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $comisario;
    private User $conductor;

    /**
     * Setup común: siembra roles, permisos y usuarios de prueba.
     *
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
     * Un conductor no puede acceder al listado (sin permiso usuarios.ver).
     *
     * @return void
     */
    public function test_conductor_no_puede_acceder_al_listado(): void
    {
        $this->actingAs($this->conductor)
             ->get(route('usuarios.index'))
             ->assertForbidden();
    }

    /**
     * El comisario sí puede acceder al listado.
     *
     * @return void
     */
    public function test_comisario_accede_al_listado(): void
    {
        $this->actingAs($this->comisario)
             ->get(route('usuarios.index'))
             ->assertOk()
             ->assertViewIs('usuarios.index');
    }

    /**
     * El super_admin puede crear un usuario completo con un rol.
     *
     * @return void
     */
    public function test_super_admin_crea_un_usuario_con_perfil_y_rol(): void
    {
        $datos = [
            'name'                  => 'Usuario Nuevo Test',
            'email'                 => 'nuevo@simetsa.gob.ec',
            'password'              => 'Secreta12345',
            'password_confirmation' => 'Secreta12345',
            'roles'                 => [RolSistema::AgenteParqueo->value],
            'cedula'                => '0508901238',
            'telefono_celular'      => '0991234567',
            'acepta_terminos'       => '1',
        ];

        $this->actingAs($this->superAdmin)
            ->post(route('usuarios.store'), $datos)
            ->assertRedirect();

        $nuevo = User::where('email', 'nuevo@simetsa.gob.ec')->first();
        $this->assertTrue($nuevo->hasRole(RolSistema::AgenteParqueo->value));
    }

    /**
     * El super_admin puede asignar varios roles al mismo usuario.
     * Caso real: un comisario que también es conductor.
     *
     * @return void
     */
    public function test_super_admin_puede_asignar_multiples_roles(): void
    {
        $datos = [
            'name'                  => 'Funcionario Conductor',
            'email'                 => 'multi@simetsa.gob.ec',
            'password'              => 'Secreta12345',
            'password_confirmation' => 'Secreta12345',
            'roles'                 => [
                RolSistema::Comisario->value,
                RolSistema::Conductor->value,
            ],
            'cedula'                => '0508901238',
            'telefono_celular'      => '0991234567',
        ];

        $this->actingAs($this->superAdmin)
            ->post(route('usuarios.store'), $datos)
            ->assertRedirect();

        $nuevo = User::where('email', 'multi@simetsa.gob.ec')->first();
        $this->assertCount(2, $nuevo->roles);
        $this->assertTrue($nuevo->hasRole(RolSistema::Comisario->value));
        $this->assertTrue($nuevo->hasRole(RolSistema::Conductor->value));

        // Verifica que sus permisos combinen los de ambos roles
        $this->assertTrue($nuevo->can('inmovilizaciones.aplicar')); // del comisario
        $this->assertTrue($nuevo->can('tickets.comprar'));          // del conductor
    }

    /**
     * Prueba separada: creación exitosa con cédula válida calculada.
     *
     * @return void
     */
    public function test_super_admin_crea_un_usuario_con_perfil_y_rol_con_cedula_valida(): void
    {
        $datos = [
            'name'                  => 'Usuario Nuevo Test Válido',
            'email'                 => 'nuevo_valido@simetsa.gob.ec',
            'password'              => 'Secreta12345',
            'password_confirmation' => 'Secreta12345',
            'roles'                 => [RolSistema::AgenteParqueo->value],
            'cedula'                => '0508901238',
            'telefono_celular'      => '0991234567',
            'acepta_terminos'       => '1',
        ];

        $this->actingAs($this->superAdmin)
             ->post(route('usuarios.store'), $datos)
             ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'nuevo_valido@simetsa.gob.ec']);
        $this->assertDatabaseHas('perfiles_usuario', ['cedula' => '0508901238']);

        $nuevo = User::where('email', 'nuevo_valido@simetsa.gob.ec')->first();
        $this->assertTrue($nuevo->hasRole(RolSistema::AgenteParqueo->value));
        $this->assertTrue($nuevo->perfil->acepta_terminos);
    }

    /**
     * Crear con cédula inválida es rechazado.
     *
     * @return void
     */
    public function test_crear_falla_con_cedula_invalida(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('usuarios.store'), [
                'name'                  => 'Test',
                'email'                 => 'test@simetsa.gob.ec',
                'password'              => 'Secreta12345',
                'password_confirmation' => 'Secreta12345',
                'roles'                 => [RolSistema::Conductor->value],
                'cedula'                => '1234567890',
                'telefono_celular'      => '0991234567',
            ])
            ->assertSessionHasErrors('cedula');
    }

    /**
     * Un usuario no super_admin no puede otorgar el rol super_admin
     * (ni siquiera incluyéndolo dentro de un array con otros).
     *
     * @return void
     */
    public function test_comisario_no_puede_otorgar_rol_super_admin(): void
    {
        $datos = [
            'name'                  => 'Intento Escalada',
            'email'                 => 'escalada@simetsa.gob.ec',
            'password'              => 'Secreta12345',
            'password_confirmation' => 'Secreta12345',
            'roles'                 => [
                RolSistema::Conductor->value,
                RolSistema::SuperAdmin->value, // ← intento de escalada
            ],
            'cedula'                => '1710034065',
            'telefono_celular'      => '0991234567',
        ];

        $this->actingAs($this->comisario)
            ->post(route('usuarios.store'), $datos)
            ->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['email' => 'escalada@simetsa.gob.ec']);
    }

    /**
     * El super_admin puede actualizar a otro usuario (incluyendo sus roles).
     *
     * @return void
     */
    public function test_super_admin_actualiza_usuario(): void
    {
        $datos = [
            'name'             => 'Conductor Actualizado',
            'email'            => $this->conductor->email,
            'roles'            => [RolSistema::Conductor->value],
            'cedula'           => $this->conductor->perfil->cedula,
            'telefono_celular' => '0999999999',
        ];

        $this->actingAs($this->superAdmin)
            ->put(route('usuarios.update', $this->conductor), $datos)
            ->assertRedirect();

        $this->assertEquals('Conductor Actualizado', $this->conductor->fresh()->name);
        $this->assertEquals('0999999999', $this->conductor->fresh()->perfil->telefono_celular);
    }

    /**
     * Destroy desactiva (soft delete del perfil + activo=false),
     * NO elimina el registro User.
     *
     * @return void
     */
    public function test_destroy_desactiva_y_no_elimina_user(): void
    {
        $this->actingAs($this->superAdmin)
             ->delete(route('usuarios.destroy', $this->conductor))
             ->assertRedirect();

        // El User sigue existiendo
        $this->assertDatabaseHas('users', ['id' => $this->conductor->id]);

        // El perfil quedó soft-deleted y activo=false
        $perfil = PerfilUsuario::withTrashed()
            ->where('user_id', $this->conductor->id)
            ->first();
        $this->assertTrue($perfil->trashed());
        $this->assertFalse($perfil->activo);
    }

    /**
     * Reactivar restaura el perfil y vuelve a marcar activo=true.
     *
     * @return void
     */
    public function test_reactivar_restaura_al_usuario(): void
    {
        // Desactivar primero
        $this->actingAs($this->superAdmin)
             ->delete(route('usuarios.destroy', $this->conductor));

        // Reactivar
        $this->actingAs($this->superAdmin)
             ->patch(route('usuarios.reactivar', $this->conductor))
             ->assertRedirect();

        $perfil = PerfilUsuario::where('user_id', $this->conductor->id)->first();
        $this->assertNotNull($perfil);
        $this->assertFalse($perfil->trashed());
        $this->assertTrue($perfil->activo);
    }

    /**
     * Nadie puede eliminar al super_admin (UserPolicy::delete).
     *
     * @return void
     */
    public function test_no_se_puede_desactivar_al_super_admin(): void
    {
        $this->actingAs($this->comisario)
             ->delete(route('usuarios.destroy', $this->superAdmin))
             ->assertForbidden();
    }

    /**
     * El listado responde a búsqueda por cédula.
     *
     * @return void
     */
    public function test_listado_filtra_por_cedula(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('usuarios.index', ['buscar' => $this->conductor->perfil->cedula]))
             ->assertOk()
             ->assertSee($this->conductor->name)
             ->assertDontSee($this->comisario->name);
    }
    /**
     * Si un admin crea un rol custom desde RolController, ese rol debe
     * estar disponible para asignarse a usuarios desde UsuarioController.
     * Cubre el bug de que el formulario solo mostraba roles del Enum.
     *
     * @return void
     */
    public function test_se_pueden_asignar_roles_custom_creados_por_admin(): void
    {
        // Crear un rol custom (como lo haría el RolController)
        \Spatie\Permission\Models\Role::create([
            'name'       => 'secretaria_general',
            'guard_name' => 'web',
        ]);

        // 1) El listado de roles del formulario lo incluye
        $respuesta = $this->actingAs($this->superAdmin)
            ->get(route('usuarios.create'));
        $respuesta->assertOk();
        $respuesta->assertSee('secretaria_general');
        $respuesta->assertSee('Secretaria General');

        // 2) Se puede crear un usuario asignándole ese rol
        $datos = [
            'name'                  => 'Secretaria Test',
            'email'                 => 'secretaria@simetsa.gob.ec',
            'password'              => 'Secreta12345',
            'password_confirmation' => 'Secreta12345',
            'roles'                 => ['secretaria_general'],
            'cedula'                => '0508901238',
            'telefono_celular'      => '0991234567',
            'acepta_terminos'       => '1',
        ];

        $this->actingAs($this->superAdmin)
            ->post(route('usuarios.store'), $datos)
            ->assertRedirect();

        $usuario = \App\Models\User::where('email', 'secretaria@simetsa.gob.ec')->first();
        $this->assertTrue($usuario->hasRole('secretaria_general'));
    }
}