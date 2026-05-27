<?php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\Conductor;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el ciclo de autenticación del conductor por la API v1:
 * registro (con LOPDP), unicidad de cédula/correo, login, perfil y logout.
 */
class AutenticacionConductorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolPermisoSeeder::class); // crea el rol 'conductor'
    }

    private function datosRegistro(array $extra = []): array
    {
        return array_merge([
            'cedula'                => '0503652349', // cédula ecuatoriana válida
            'nombres'               => 'Juan',
            'apellidos'             => 'Pérez',
            'email'                 => 'juan.conductor@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'telefono_celular'      => '0991234567',
            'acepta_terminos'       => true,
        ], $extra);
    }

    private function tokenDeNuevoConductor(array $extra = []): string
    {
        return $this->postJson('/api/v1/registro', $this->datosRegistro($extra))->json('datos.token');
    }

    public function test_registro_crea_conductor_usuario_perfil_y_token(): void
    {
        $this->postJson('/api/v1/registro', $this->datosRegistro())
            ->assertStatus(201)
            ->assertJsonPath('exito', true)
            ->assertJsonStructure(['datos' => ['token', 'conductor' => ['id', 'codigo', 'estado', 'cedula']]]);

        $user = User::where('email', 'juan.conductor@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(RolSistema::Conductor->value));
        $this->assertNotNull($user->perfil);
        $this->assertSame(1, Conductor::count());
    }

    public function test_registro_rechaza_correo_duplicado(): void
    {
        $this->postJson('/api/v1/registro', $this->datosRegistro());

        $this->postJson('/api/v1/registro', $this->datosRegistro(['cedula' => '1710034065']))
            ->assertStatus(422)
            ->assertJsonPath('exito', false)
            ->assertJsonValidationErrors('email');
    }

    public function test_registro_rechaza_cedula_duplicada(): void
    {
        $this->postJson('/api/v1/registro', $this->datosRegistro());

        $this->postJson('/api/v1/registro', $this->datosRegistro(['email' => 'otro@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cedula');
    }

    public function test_registro_exige_consentimiento_lopdp(): void
    {
        $this->postJson('/api/v1/registro', $this->datosRegistro(['acepta_terminos' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('acepta_terminos');

        $this->assertSame(0, Conductor::count());
    }

    public function test_login_devuelve_token(): void
    {
        $this->postJson('/api/v1/registro', $this->datosRegistro());

        $this->postJson('/api/v1/login', ['email' => 'juan.conductor@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonStructure(['datos' => ['token', 'conductor']]);
    }

    public function test_login_rechaza_credenciales_invalidas(): void
    {
        $this->postJson('/api/v1/registro', $this->datosRegistro());

        $this->postJson('/api/v1/login', ['email' => 'juan.conductor@example.com', 'password' => 'incorrecta'])
            ->assertStatus(401)
            ->assertJsonPath('exito', false);
    }

    public function test_perfil_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/perfil')->assertStatus(401)->assertJsonPath('exito', false);
    }

    public function test_perfil_devuelve_datos_del_conductor_autenticado(): void
    {
        $token = $this->tokenDeNuevoConductor();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/perfil')
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.estado', Conductor::ESTADO_ACTIVO);
    }

    public function test_logout_revoca_el_token(): void
    {
        $token = $this->tokenDeNuevoConductor();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('exito', true);

        // El token revocado ya no autoriza
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/perfil')
            ->assertStatus(401);
    }
}