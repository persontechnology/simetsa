<?php

namespace Tests\Feature;

use App\Enums\RolSistema;
use App\Models\PuntoVenta;
use App\Models\SolicitudPuntoVenta;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PuntoVentaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolPermisoSeeder::class);
        $this->seed(UsuarioPruebaSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
    }

    private function solicitudEnContrato(array $extra = []): SolicitudPuntoVenta
    {
        return SolicitudPuntoVenta::create(array_merge([
            'codigo' => 'SPV-' . fake()->unique()->numberBetween(1000, 9999),
            'cedula' => '0999000001',
            'nombres' => 'Carlos',
            'apellidos' => 'Mena',
            'email' => 'carlos.pv@example.com',
            'nombre_comercial' => 'Minimarket La Esquina',
            'direccion_local' => 'Av. Olmedo y Bolívar',
            'estado' => SolicitudPuntoVenta::ESTADO_CONTRATO,
            'fecha_solicitud' => now(),
        ], $extra));
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'email' => 'nuevo.pv@example.com',
            'numero_contrato' => 'CPV-1',
            'fecha_firma' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
        ], $extra);
    }

    private function usuarioConCedula(string $email, string $cedula): User
    {
        $user = User::factory()->create(['email' => $email, 'name' => 'Persona']);
        $user->perfil()->create([
            'cedula' => $cedula,
            'acepta_terminos' => true,
            'fecha_aceptacion_terminos' => now(),
            'activo' => true,
        ]);

        return $user;
    }

    public function test_activa_punto_de_venta_crea_punto_contrato_y_usuario(): void
    {
        $s = $this->solicitudEnContrato(['cedula' => '0999000001']);

        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s), $this->payload())
            ->assertRedirect();

        $punto = PuntoVenta::first();
        $this->assertNotNull($punto);
        $this->assertNotNull($punto->contrato);
        $this->assertSame(SolicitudPuntoVenta::ESTADO_ACTIVA, $s->fresh()->estado);

        $user = User::where('email', 'nuevo.pv@example.com')->first();
        $this->assertTrue($user->hasRole(RolSistema::PuntoVenta->value));
        $this->assertNotNull($user->perfil);
    }

    public function test_no_activa_si_no_esta_en_contrato(): void
    {
        $s = $this->solicitudEnContrato(['estado' => SolicitudPuntoVenta::ESTADO_DOCUMENTACION]);

        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s), $this->payload())
            ->assertSessionHas('error');

        $this->assertSame(0, PuntoVenta::count());
    }

    public function test_activar_requiere_numero_de_contrato(): void
    {
        $s = $this->solicitudEnContrato();

        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s), $this->payload(['numero_contrato' => '']))
            ->assertSessionHasErrors('numero_contrato');
    }

    public function test_no_activa_si_la_cedula_pertenece_a_otro_correo(): void
    {
        // Reproduce el caso reportado: la cédula ya es de otra cuenta.
        $this->usuarioConCedula('otro@example.com', '0999111222');
        $s = $this->solicitudEnContrato(['cedula' => '0999111222']);

        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s), $this->payload(['email' => 'nuevo.distinto@example.com']))
            ->assertSessionHas('error');

        $this->assertSame(0, PuntoVenta::count());
    }

    public function test_vincula_a_persona_existente_usando_su_correo(): void
    {
        $persona = $this->usuarioConCedula('persona@example.com', '0999333444');
        $s = $this->solicitudEnContrato(['cedula' => '0999333444']);

        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s), $this->payload(['email' => 'persona@example.com']))
            ->assertRedirect()
            ->assertSessionMissing('password_temporal');

        $this->assertTrue($persona->fresh()->hasRole(RolSistema::PuntoVenta->value));
        $this->assertSame(1, PuntoVenta::count());
    }

    public function test_no_activa_si_el_correo_pertenece_a_otra_persona(): void
    {
        $this->usuarioConCedula('ocupado@example.com', '0999555666');
        $s = $this->solicitudEnContrato(['cedula' => '0999777888']); // cédula distinta

        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s), $this->payload(['email' => 'ocupado@example.com']))
            ->assertSessionHas('error');

        $this->assertSame(0, PuntoVenta::count());
    }

    public function test_no_activa_si_usuario_ya_es_punto_de_venta(): void
    {
        $s1 = $this->solicitudEnContrato(['cedula' => '0999000002']);
        $this->actingAs($this->admin())->post(route('puntos-venta.activar', $s1), $this->payload(['email' => 'dueno@example.com']));

        $s2 = $this->solicitudEnContrato(['cedula' => '0999000002']);
        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s2), $this->payload(['email' => 'dueno@example.com']))
            ->assertSessionHas('error');

        $this->assertSame(1, PuntoVenta::count());
    }

    public function test_bloquea_por_punto_de_venta_cercano(): void
    {
        PuntoVenta::create([
            'codigo' => 'PV-9000', 'nombre_comercial' => 'Existente', 'direccion_local' => 'X',
            'latitud' => 0, 'longitud' => 0, 'estado' => PuntoVenta::ESTADO_ACTIVO,
        ]);

        $s = $this->solicitudEnContrato(['cedula' => '0999000003', 'latitud' => 0.0004, 'longitud' => 0]);

        $this->actingAs($this->admin())
            ->post(route('puntos-venta.activar', $s), $this->payload())
            ->assertSessionHas('error');

        $this->assertSame(1, PuntoVenta::count());
    }

    public function test_cambia_estado_del_punto_de_venta(): void
    {
        $punto = PuntoVenta::create([
            'codigo' => 'PV-9001', 'nombre_comercial' => 'Test', 'direccion_local' => 'X',
            'estado' => PuntoVenta::ESTADO_ACTIVO,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('puntos-venta.estado', $punto), ['estado' => PuntoVenta::ESTADO_SUSPENDIDO])
            ->assertRedirect();

        $this->assertSame(PuntoVenta::ESTADO_SUSPENDIDO, $punto->fresh()->estado);
    }
}