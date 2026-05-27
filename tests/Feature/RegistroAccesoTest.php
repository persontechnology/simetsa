<?php
// tests/Feature/RegistroAccesoTest.php

namespace Tests\Feature;

use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de auditoría de accesos.
 *
 * Cubre:
 *  - Listener registra los 3 eventos clave (login, logout, fallido).
 *  - Controlador respeta el permiso 'accesos.ver'.
 *  - Filtros del listado funcionan correctamente.
 */
class RegistroAccesoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);
    }

    /**
     * Un login exitoso debe quedar registrado con el user_id correcto.
     *
     * @return void
     */
    public function test_login_exitoso_registra_acceso(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('clave-segura-123'),
        ]);

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'clave-segura-123',
        ]);

        $this->assertDatabaseHas('registros_acceso', [
            'user_id' => $user->id,
            'evento'  => RegistroAcceso::EVENTO_LOGIN,
        ]);
    }

    /**
     * Un logout debe quedar registrado.
     *
     * @return void
     */
    public function test_logout_registra_acceso(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'));

        $this->assertDatabaseHas('registros_acceso', [
            'user_id' => $user->id,
            'evento'  => RegistroAcceso::EVENTO_LOGOUT,
        ]);
    }

    /**
     * Un intento de login con credenciales inválidas debe registrarse
     * con el email intentado y user_id=null si el email no existe.
     *
     * @return void
     */
    public function test_login_fallido_registra_acceso_con_email_intentado(): void
    {
        $this->post(route('login'), [
            'email'    => 'inexistente@simetsa.gob.ec',
            'password' => 'cualquier-cosa',
        ]);

        $this->assertDatabaseHas('registros_acceso', [
            'user_id'       => null,
            'email_intento' => 'inexistente@simetsa.gob.ec',
            'evento'        => RegistroAcceso::EVENTO_FALLIDO,
        ]);
    }

    /**
     * Un intento fallido sobre un email QUE SÍ existe registra
     * el email_intento y opcionalmente el user_id (depende de la versión
     * de Laravel — Failed event a veces incluye user, a veces no).
     *
     * @return void
     */
    public function test_login_fallido_con_password_incorrecta_se_registra(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correcta'),
        ]);

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'incorrecta',
        ]);

        $this->assertDatabaseHas('registros_acceso', [
            'email_intento' => $user->email,
            'evento'        => RegistroAcceso::EVENTO_FALLIDO,
        ]);
    }

    /**
     * El registro de acceso captura IP y user_agent.
     *
     * @return void
     */
    public function test_se_capturan_ip_y_user_agent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->withHeaders(['User-Agent' => 'TestBrowser/1.0'])
             ->post(route('logout'));

        $registro = RegistroAcceso::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($registro);
        $this->assertEquals('TestBrowser/1.0', $registro->user_agent);
        $this->assertNotEmpty($registro->ip);
    }

    /**
     * Un usuario sin permiso 'accesos.ver' NO puede acceder al listado.
     *
     * @return void
     */
    public function test_conductor_no_puede_ver_el_registro(): void
    {
        $conductor = User::where('email', 'conductor@simetsa.gob.ec')->first();

        $this->actingAs($conductor)
             ->get(route('accesos.index'))
             ->assertForbidden();
    }

    /**
     * Un super_admin sí ve el listado.
     *
     * @return void
     */
    public function test_super_admin_ve_el_listado(): void
    {
        $admin = User::where('email', 'admin@simetsa.gob.ec')->first();

        $this->actingAs($admin)
             ->get(route('accesos.index'))
             ->assertOk()
             ->assertViewIs('registro-accesos.index');
    }

    /**
     * El filtro por tipo de evento funciona.
     *
     * @return void
     */
    public function test_filtro_por_evento_funciona(): void
    {
        $admin = User::where('email', 'admin@simetsa.gob.ec')->first();

        // Generar registros de distintos tipos
        RegistroAcceso::create([
            'user_id'     => $admin->id,
            'evento'      => RegistroAcceso::EVENTO_LOGIN,
            'ip'          => '127.0.0.1',
            'ocurrido_en' => now(),
        ]);
        RegistroAcceso::create([
            'email_intento' => 'atacante@evil.com',
            'evento'        => RegistroAcceso::EVENTO_FALLIDO,
            'ip'            => '10.0.0.1',
            'ocurrido_en'   => now(),
        ]);

        // Filtrar solo los fallidos
        $this->actingAs($admin)
             ->get(route('accesos.index', ['evento' => 'fallido']))
             ->assertOk()
             ->assertSee('atacante@evil.com')
             ->assertDontSee('admin@simetsa.gob.ec');
    }
}