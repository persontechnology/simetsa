<?php
// tests/Feature/UserPolicyTest.php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de UserPolicy.
 *
 * Verifica el cuadro de autorización del modelo User en función de
 * los 6 roles del sistema. Se apoya en los usuarios sembrados por
 * UsuarioPruebaSeeder.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $comisario;
    private User $director;
    private User $agente;
    private User $puntoVenta;
    private User $conductor;

    /**
     * Carga los seeders y obtiene una referencia a cada usuario de prueba.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);

        $this->superAdmin = User::where('email', 'admin@simetsa.gob.ec')->first();
        $this->comisario  = User::where('email', 'comisario@simetsa.gob.ec')->first();
        $this->director   = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $this->agente     = User::where('email', 'agente@simetsa.gob.ec')->first();
        $this->puntoVenta = User::where('email', 'puntoventa@simetsa.gob.ec')->first();
        $this->conductor  = User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    /**
     * super_admin puede realizar todas las acciones SOBRE OTROS usuarios
     * (bypass en before()). Las acciones contra sí mismo se cubren aparte.
     *
     * @return void
     */
    public function test_super_admin_aprueba_acciones_sobre_otros_usuarios(): void
    {
        $otro = $this->comisario;

        $this->assertTrue($this->superAdmin->can('viewAny', User::class));
        $this->assertTrue($this->superAdmin->can('view',    $otro));
        $this->assertTrue($this->superAdmin->can('create',  User::class));
        $this->assertTrue($this->superAdmin->can('update',  $otro));
        $this->assertTrue($this->superAdmin->can('delete',  $otro));
        $this->assertTrue($this->superAdmin->can('assignRole', $otro));
    }

    /**
     * El comisario puede listar/crear/editar usuarios (tiene los permisos),
     * pero NO puede tocar al super_admin.
     *
     * @return void
     */
    public function test_comisario_no_puede_modificar_a_super_admin(): void
    {
        $this->assertTrue($this->comisario->can('viewAny', User::class));
        $this->assertTrue($this->comisario->can('create',  User::class));
        $this->assertTrue($this->comisario->can('update',  $this->conductor));

        // No puede tocar al super_admin
        $this->assertFalse($this->comisario->can('update', $this->superAdmin));
        $this->assertFalse($this->comisario->can('delete', $this->superAdmin));
        $this->assertFalse($this->comisario->can('assignRole', $this->superAdmin));
    }

    /**
     * El director_seguridad solo tiene `usuarios.ver`, no puede crear/editar/eliminar.
     *
     * @return void
     */
    public function test_director_seguridad_solo_puede_ver_usuarios(): void
    {
        $this->assertTrue($this->director->can('viewAny', User::class));
        $this->assertTrue($this->director->can('view', $this->conductor));

        $this->assertFalse($this->director->can('create', User::class));
        $this->assertFalse($this->director->can('update', $this->conductor));
        $this->assertFalse($this->director->can('delete', $this->conductor));
        $this->assertFalse($this->director->can('assignRole', $this->conductor));
    }

    /**
     * Roles sin permisos de gestión de usuarios no pueden ver el listado
     * ni operar sobre otros usuarios.
     *
     * @return void
     */
    public function test_agente_punto_venta_y_conductor_no_pueden_gestionar_usuarios(): void
    {
        foreach ([$this->agente, $this->puntoVenta, $this->conductor] as $sinPermisos) {
            $this->assertFalse($sinPermisos->can('viewAny', User::class));
            $this->assertFalse($sinPermisos->can('create',  User::class));
            $this->assertFalse($sinPermisos->can('update',  $this->comisario));
            $this->assertFalse($sinPermisos->can('delete',  $this->comisario));
        }
    }

    /**
     * Todo usuario puede verse y editarse a sí mismo, aunque no tenga
     * el permiso usuarios.editar.
     *
     * @return void
     */
    public function test_todo_usuario_puede_verse_y_editarse_a_si_mismo(): void
    {
        foreach ([$this->agente, $this->puntoVenta, $this->conductor] as $usuario) {
            $this->assertTrue($usuario->can('view',   $usuario));
            $this->assertTrue($usuario->can('update', $usuario));
        }
    }

    /**
     * Regla universal: NADIE puede eliminarse a sí mismo, incluyendo super_admin.
     *
     * @return void
     */
    public function test_nadie_puede_eliminarse_a_si_mismo_ni_siquiera_super_admin(): void
    {
        $this->assertFalse($this->superAdmin->can('delete', $this->superAdmin));
        $this->assertFalse($this->comisario->can('delete',  $this->comisario));
        $this->assertFalse($this->director->can('delete',   $this->director));
        $this->assertFalse($this->agente->can('delete',     $this->agente));
        $this->assertFalse($this->puntoVenta->can('delete', $this->puntoVenta));
        $this->assertFalse($this->conductor->can('delete',  $this->conductor));
    }

    /**
     * Regla universal: NADIE puede cambiar sus propios roles, incluyendo
     * super_admin. Evita que el único administrador se degrade y deje
     * al sistema sin gestión.
     *
     * @return void
     */
    public function test_nadie_puede_cambiar_sus_propios_roles_ni_siquiera_super_admin(): void
    {
        $this->assertFalse($this->superAdmin->can('assignRole', $this->superAdmin));
        $this->assertFalse($this->comisario->can('assignRole',  $this->comisario));
        $this->assertFalse($this->director->can('assignRole',   $this->director));
        $this->assertFalse($this->agente->can('assignRole',     $this->agente));
        $this->assertFalse($this->puntoVenta->can('assignRole', $this->puntoVenta));
        $this->assertFalse($this->conductor->can('assignRole',  $this->conductor));
    }

    /**
     * El Gate 'asignar-rol-super-admin' solo aprueba para super_admin.
     *
     * @return void
     */
    public function test_gate_asignar_super_admin_solo_para_super_admin(): void
    {
        $this->assertTrue($this->superAdmin->can('asignar-rol-super-admin'));

        foreach ([$this->comisario, $this->director, $this->agente, $this->puntoVenta, $this->conductor] as $u) {
            $this->assertFalse(
                $u->can('asignar-rol-super-admin'),
                "El usuario {$u->email} NO debería poder asignar el rol super_admin."
            );
        }
    }

    /**
     * super_admin SÍ puede eliminar a otros y asignarles roles
     * (la regla universal solo bloquea acciones contra sí mismo).
     *
     * @return void
     */
    public function test_super_admin_puede_eliminar_y_asignar_roles_a_otros(): void
    {
        $this->assertTrue($this->superAdmin->can('delete',     $this->conductor));
        $this->assertTrue($this->superAdmin->can('assignRole', $this->conductor));
    }
}