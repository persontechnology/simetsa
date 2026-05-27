<?php
// tests/Feature/UsuarioPruebaSeederTest.php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\PerfilUsuario;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del UsuarioPruebaSeeder.
 *
 * Verifica que tras ejecutar el seeder:
 *  - Existen los 6 usuarios.
 *  - Cada uno tiene su PerfilUsuario y rol Spatie correctamente asignados.
 *  - Las cédulas son únicas y válidas.
 *  - Re-ejecutar el seeder NO duplica filas (idempotencia).
 */
class UsuarioPruebaSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ejecuta los seeders dependientes antes de cada test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolPermisoSeeder::class);   // Roles deben existir primero
        $this->seed(UsuarioPruebaSeeder::class);
    }

    /**
     * Debe existir exactamente 1 usuario por cada rol del Enum.
     *
     * @return void
     */
    public function test_se_crea_un_usuario_por_cada_rol(): void
    {
        $this->assertEquals(count(RolSistema::cases()), User::count());

        foreach (RolSistema::cases() as $rol) {
            $usuarios = User::role($rol->value)->get();
            $this->assertCount(
                1,
                $usuarios,
                "Debe haber exactamente 1 usuario con rol {$rol->value}."
            );
        }
    }

    /**
     * Cada usuario creado debe tener su PerfilUsuario asociado con
     * consentimiento LOPDP aceptado.
     *
     * @return void
     */
    public function test_cada_usuario_tiene_perfil_con_consentimiento(): void
    {
        User::all()->each(function (User $user) {
            $this->assertNotNull(
                $user->perfil,
                "El usuario {$user->email} debe tener un PerfilUsuario."
            );
            $this->assertTrue(
                $user->perfil->acepta_terminos,
                "El perfil de {$user->email} debe tener consentimiento aceptado."
            );
            $this->assertNotNull($user->perfil->fecha_aceptacion_terminos);
        });
    }

    /**
     * Las cédulas de los usuarios sembrados deben ser todas distintas.
     *
     * @return void
     */
    public function test_las_cedulas_de_usuarios_son_unicas(): void
    {
        $cedulas = PerfilUsuario::pluck('cedula');
        $this->assertEquals($cedulas->count(), $cedulas->unique()->count());
    }

    /**
     * El usuario super_admin debe estar accesible por su email conocido
     * y tener exactamente ese rol.
     *
     * @return void
     */
    public function test_usuario_super_admin_se_crea_correctamente(): void
    {
        $admin = User::where('email', 'admin@simetsa.gob.ec')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole(RolSistema::SuperAdmin->value));
        $this->assertEquals('1710034065', $admin->perfil->cedula);
    }

    /**
     * Re-ejecutar el seeder no debe duplicar usuarios ni perfiles.
     *
     * @return void
     */
    public function test_el_seeder_es_idempotente(): void
    {
        $cantidadInicialUsers   = User::count();
        $cantidadInicialPerfiles = PerfilUsuario::count();

        // Ejecutar el seeder por segunda vez
        $this->seed(UsuarioPruebaSeeder::class);

        $this->assertEquals($cantidadInicialUsers, User::count());
        $this->assertEquals($cantidadInicialPerfiles, PerfilUsuario::count());
    }

    /**
     * En entorno "production" el seeder debe abortar sin crear datos.
     *
     * @return void
     */
    public function test_el_seeder_aborta_en_produccion(): void
    {
        // Resetear el estado para esta prueba
        User::query()->forceDelete();
        PerfilUsuario::query()->forceDelete();

        // Forzar entorno production
        app()->detectEnvironment(fn () => 'production');

        $this->seed(UsuarioPruebaSeeder::class);

        $this->assertEquals(0, User::count());
    }
}