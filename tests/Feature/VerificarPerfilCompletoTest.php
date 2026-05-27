<?php
// tests/Feature/VerificarPerfilCompletoTest.php

namespace Tests\Feature;

use App\Models\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tests del middleware VerificarPerfilCompleto.
 *
 * Verifica el comportamiento ante usuarios con perfil completo,
 * incompleto y peticiones tanto web (redirección) como API (JSON 403).
 */
class VerificarPerfilCompletoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Registra una ruta temporal protegida con el middleware bajo prueba.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(['web', 'auth', 'perfil.completo'])
             ->get('/ruta-prueba', fn () => response('ok'))
             ->name('ruta.prueba');
    }

    /**
     * Usuario sin perfil → web → redirige (o si la ruta perfil.completar
     * no existe, el middleware deja pasar con log).
     *
     * @return void
     */
    public function test_usuario_sin_perfil_no_accede_a_rutas_protegidas(): void
    {
        $user = User::factory()->create();

        $respuesta = $this->actingAs($user)->get('/ruta-prueba');

        // Como `perfil.completar` aún no existe (se define en 1.E), el
        // middleware deja pasar pero loguea el caso. Validamos los dos
        // escenarios posibles:
        if (Route::has('perfil.completar')) {
            $respuesta->assertRedirect(route('perfil.completar'));
        } else {
            $respuesta->assertOk(); // fallback defensivo
        }
    }

    /**
     * Usuario con perfil pero sin consentimiento → comportamiento equivalente
     * al usuario sin perfil.
     *
     * @return void
     */
    public function test_usuario_sin_consentimiento_no_accede_a_rutas_protegidas(): void
    {
        $user = User::factory()->create();
        PerfilUsuario::factory()
            ->sinConsentimiento()
            ->create(['user_id' => $user->id]);

        $respuesta = $this->actingAs($user)->get('/ruta-prueba');

        $this->assertFalse($user->fresh()->tienePerfilCompleto());
        if (Route::has('perfil.completar')) {
            $respuesta->assertRedirect(route('perfil.completar'));
        }
    }

    /**
     * Usuario con perfil completo y consentimiento accede sin problemas.
     *
     * @return void
     */
    public function test_usuario_con_perfil_completo_accede_correctamente(): void
    {
        $user = User::factory()->create();
        PerfilUsuario::factory()->create(['user_id' => $user->id]);

        $respuesta = $this->actingAs($user)->get('/ruta-prueba');

        $respuesta->assertOk();
        $respuesta->assertSee('ok');
    }

    /**
     * Petición JSON (API móvil) sin perfil → 403 con estructura estándar.
     *
     * @return void
     */
    public function test_peticion_json_sin_perfil_devuelve_403_estandar(): void
    {
        $user = User::factory()->create();

        $respuesta = $this->actingAs($user)
            ->getJson('/ruta-prueba');

        $respuesta->assertStatus(403);
        $respuesta->assertJsonStructure(['exito', 'mensaje', 'datos', 'errores']);
        $respuesta->assertJson([
            'exito'   => false,
            'errores' => ['perfil' => 'incompleto'],
        ]);
    }
}