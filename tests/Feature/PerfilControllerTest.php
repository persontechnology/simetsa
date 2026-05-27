<?php
// tests/Feature/PerfilControllerTest.php

namespace Tests\Feature;

use App\Models\PerfilUsuario;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del flujo "Mi perfil SIMETSA".
 *
 * Cubre:
 *  - Primera carga (sin perfil existente).
 *  - Ediciones posteriores (preservación de fecha de consentimiento).
 *  - Redirección del middleware perfil.completo desde rutas operativas.
 *  - Validación LOPDP obligatoria en primera vez.
 */
class PerfilControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Carga el seeder de roles (necesario para los permisos del middleware).
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolPermisoSeeder::class);
    }

    /**
     * Un usuario sin perfil puede acceder a la pantalla de completar.
     *
     * @return void
     */
    public function test_usuario_sin_perfil_accede_a_la_pantalla(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->get(route('perfil.completar'))
             ->assertOk()
             ->assertViewIs('perfil.completar')
             ->assertSee('Foto de perfil');
    }

    /**
     * Crear el perfil por primera vez exige aceptar los términos LOPDP.
     *
     * @return void
     */
    public function test_primera_vez_exige_aceptacion_lopdp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->patch(route('perfil.actualizar'), [
                 'cedula'           => '1710034065',
                 'telefono_celular' => '0991234567',
                 // 'acepta_terminos' omitido a propósito
             ])
             ->assertSessionHasErrors('acepta_terminos');

        $this->assertDatabaseMissing('perfiles_usuario', ['user_id' => $user->id]);
    }

    /**
     * Crear el perfil con consentimiento exitoso registra la fecha de aceptación.
     *
     * @return void
     */
    public function test_completar_perfil_primera_vez_registra_fecha_de_aceptacion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->patch(route('perfil.actualizar'), [
                 'cedula'           => '1710034065',
                 'telefono_celular' => '0991234567',
                 'direccion'        => 'Av. Olmedo y Calle Bolívar',
                 'acepta_terminos'  => '1',
             ])
             ->assertRedirect(route('dashboard'));

        $perfil = PerfilUsuario::where('user_id', $user->id)->first();
        $this->assertNotNull($perfil);
        $this->assertEquals('1710034065', $perfil->cedula);
        $this->assertTrue($perfil->acepta_terminos);
        $this->assertNotNull($perfil->fecha_aceptacion_terminos);
        $this->assertTrue($perfil->activo);
    }

    /**
     * Una segunda edición preserva la fecha original de aceptación
     * (no se reescribe en cada update).
     *
     * @return void
     */
    public function test_segunda_edicion_preserva_fecha_de_aceptacion_original(): void
    {
        $user = User::factory()->create();

        // Primera vez: aceptar términos
        $this->actingAs($user)->patch(route('perfil.actualizar'), [
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'acepta_terminos'  => '1',
        ]);

        $fechaOriginal = PerfilUsuario::where('user_id', $user->id)
            ->first()
            ->fecha_aceptacion_terminos;

        // Simular el paso del tiempo
        $this->travel(2)->days();

        // Segunda edición: cambiar dirección sin tocar consentimiento
        $this->actingAs($user)->patch(route('perfil.actualizar'), [
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'direccion'        => 'Nueva dirección',
        ]);

        $perfilActualizado = PerfilUsuario::where('user_id', $user->id)->first();
        $this->assertEquals('Nueva dirección', $perfilActualizado->direccion);
        $this->assertEquals(
            $fechaOriginal->toDateTimeString(),
            $perfilActualizado->fecha_aceptacion_terminos->toDateTimeString(),
            'La fecha de aceptación LOPDP original debe preservarse.'
        );
    }

    /**
     * Un usuario sin perfil completo es redirigido al intentar acceder
     * a rutas protegidas con el middleware perfil.completo.
     *
     * @return void
     */
    public function test_middleware_redirige_a_completar_perfil(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin'); // Tiene permisos pero sin perfil

        $this->actingAs($user)
             ->get(route('usuarios.index'))
             ->assertRedirect(route('perfil.completar'));
    }

    /**
     * Tras completar el perfil, el usuario puede acceder a las rutas operativas.
     *
     * @return void
     */
    public function test_tras_completar_puede_acceder_a_rutas_operativas(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        // Completar el perfil
        $this->actingAs($user)->patch(route('perfil.actualizar'), [
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'acepta_terminos'  => '1',
        ]);

        // Ahora puede acceder
        $this->actingAs($user->fresh())
             ->get(route('usuarios.index'))
             ->assertOk();
    }

    /**
     * Un perfil con cédula inválida es rechazado.
     *
     * @return void
     */
    public function test_cedula_invalida_es_rechazada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->patch(route('perfil.actualizar'), [
                 'cedula'           => '1234567890',  // dígito verificador inválido
                 'telefono_celular' => '0991234567',
                 'acepta_terminos'  => '1',
             ])
             ->assertSessionHasErrors('cedula');
    }

    /**
     * Otro usuario no puede registrar la misma cédula.
     *
     * @return void
     */
    public function test_cedula_duplicada_es_rechazada(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1)->patch(route('perfil.actualizar'), [
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'acepta_terminos'  => '1',
        ]);

        $this->actingAs($user2)
             ->patch(route('perfil.actualizar'), [
                 'cedula'           => '1710034065',
                 'telefono_celular' => '0991111111',
                 'acepta_terminos'  => '1',
             ])
             ->assertSessionHasErrors('cedula');
    }

    /**
     * El mismo usuario puede actualizarse sin tropezar con la regla unique
     * de su propia cédula (ignore).
     *
     * @return void
     */
    public function test_misma_cedula_no_rebota_al_actualizar_se_a_si_mismo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('perfil.actualizar'), [
            'cedula'           => '1710034065',
            'telefono_celular' => '0991234567',
            'acepta_terminos'  => '1',
        ]);

        // Re-enviar la misma cédula no debe dar error
        $this->actingAs($user)
             ->patch(route('perfil.actualizar'), [
                 'cedula'           => '1710034065',
                 'telefono_celular' => '0999999999',
             ])
             ->assertSessionHasNoErrors();
    }
}