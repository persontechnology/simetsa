<?php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\AgenteParqueo;
use App\Models\SolicitudAgente;
use App\Models\User;
use App\Services\AgenteParqueoService;
use Database\Seeders\RolPermisoSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la resolución de identidad en la autorización de agentes (Art. 36),
 * espejo de los casos validados para Puntos de Venta. Verifica que la cédula
 * ya registrada deja de provocar un unique violation y ahora arroja un
 * DomainException claro.
 */
class AutorizacionAgenteIdentidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Necesario para que assignRole('agente_parqueo') encuentre el rol.
        $this->seed(RolPermisoSeeder::class);
    }

    private function servicio(): AgenteParqueoService
    {
        return app(AgenteParqueoService::class);
    }

    private function solicitudEnAutorizacion(array $extra = []): SolicitudAgente
    {
        return SolicitudAgente::create(array_merge([
            'codigo'           => 'SAG-' . fake()->unique()->numberBetween(1000, 9999),
            'cedula'           => '0999000001',
            'nombres'          => 'Luis',
            'apellidos'        => 'Gómez',
            'fecha_nacimiento' => now()->subYears(25)->toDateString(),
            'email'            => 'luis.ag@example.com',
            'telefono_celular' => '0991111111',
            'direccion'        => 'Calle Sucre',
            'nivel_educacion'  => 'bachillerato',
            'estado'           => SolicitudAgente::ESTADO_AUTORIZACION,
            'fecha_solicitud'  => now()->toDateString(),
        ], $extra));
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'email'                   => 'nuevo.ag@example.com',
            'numero_credencial'       => 'CRED-1',
            'numero_oficio_comisario' => 'OF-1',
        ], $extra);
    }

    private function usuarioConCedula(string $email, string $cedula): User
    {
        $user = User::factory()->create(['email' => $email, 'name' => 'Persona']);
        $user->perfil()->create([
            'cedula'                    => $cedula,
            'telefono_celular'          => '0991111111',
            'acepta_terminos'           => true,
            'fecha_aceptacion_terminos' => now(),
            'activo'                    => true,
        ]);

        return $user;
    }

    public function test_autoriza_crea_agente_perfil_y_usuario_nuevo(): void
    {
        $s = $this->solicitudEnAutorizacion(['cedula' => '0999000001']);

        $resultado = $this->servicio()->autorizar($s, $this->datos());

        $this->assertNotNull($resultado['password_temporal']); // cuenta nueva
        $this->assertSame(SolicitudAgente::ESTADO_AUTORIZADA, $s->fresh()->estado);

        $user = User::where('email', 'nuevo.ag@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(RolSistema::AgenteParqueo->value));
        $this->assertNotNull($user->perfil);
        $this->assertSame('0999000001', $user->perfil->cedula);
    }

    public function test_no_autoriza_si_la_cedula_pertenece_a_otro_correo(): void
    {
        // El bug histórico: la cédula del admin (0503652349) ya identifica otra cuenta.
        $this->usuarioConCedula('otro@example.com', '0503652349');
        $s = $this->solicitudEnAutorizacion(['cedula' => '0503652349']);

        try {
            $this->servicio()->autorizar($s, $this->datos(['email' => 'distinto@example.com']));
            $this->fail('Se esperaba DomainException por cédula ya registrada.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('0503652349', $e->getMessage());
        }

        $this->assertSame(0, AgenteParqueo::count());
        $this->assertSame(SolicitudAgente::ESTADO_AUTORIZACION, $s->fresh()->estado);
    }

    public function test_vincula_a_persona_existente_usando_su_correo(): void
    {
        $persona = $this->usuarioConCedula('persona@example.com', '0999333444');
        $s = $this->solicitudEnAutorizacion(['cedula' => '0999333444']);

        $resultado = $this->servicio()->autorizar($s, $this->datos(['email' => 'persona@example.com']));

        $this->assertNull($resultado['password_temporal']); // cuenta existente, sin contraseña nueva
        $this->assertTrue($persona->fresh()->hasRole(RolSistema::AgenteParqueo->value));
        $this->assertSame(1, AgenteParqueo::count());
        $this->assertSame(1, $persona->perfil()->count()); // no se duplicó el perfil
    }

    public function test_no_autoriza_si_el_correo_pertenece_a_otra_persona(): void
    {
        $this->usuarioConCedula('ocupado@example.com', '0999555666');
        $s = $this->solicitudEnAutorizacion(['cedula' => '0999777888']); // cédula distinta

        $this->expectException(DomainException::class);
        $this->servicio()->autorizar($s, $this->datos(['email' => 'ocupado@example.com']));
    }

    public function test_no_autoriza_si_usuario_ya_es_agente(): void
    {
        $s1 = $this->solicitudEnAutorizacion(['cedula' => '0999000002']);
        $this->servicio()->autorizar($s1, $this->datos(['email' => 'agente@example.com']));

        $s2 = $this->solicitudEnAutorizacion(['cedula' => '0999000002']);

        $this->expectException(DomainException::class);
        $this->servicio()->autorizar($s2, $this->datos(['email' => 'agente@example.com']));
    }

    public function test_no_autoriza_si_no_esta_en_autorizacion(): void
    {
        $s = $this->solicitudEnAutorizacion(['estado' => SolicitudAgente::ESTADO_DOCUMENTACION]);

        $this->expectException(DomainException::class);
        $this->servicio()->autorizar($s, $this->datos());
    }
}