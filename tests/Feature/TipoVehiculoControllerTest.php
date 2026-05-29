<?php
// tests/Feature/TipoVehiculoControllerTest.php

namespace Tests\Feature;

use App\Models\TipoVehiculo;
use App\Models\User;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TipoVehiculoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del catálogo de tipos de vehículo (Fase 4.A — Art. 25 Ordenanza SIMETSA).
 */
class TipoVehiculoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
    }

    private function director(): User
    {
        return User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
    }

    private function conductor(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    // ===== Web backoffice =====

    public function test_director_puede_ver_el_listado(): void
    {
        $this->actingAs($this->director())
            ->get(route('tipos-vehiculo.index'))
            ->assertOk();
    }

    public function test_conductor_no_puede_crear_tipo_de_vehiculo(): void
    {
        $this->actingAs($this->conductor())
            ->post(route('tipos-vehiculo.store'), [
                'codigo' => 'moto',
                'nombre' => 'Motocicleta',
            ])
            ->assertForbidden();
    }

    public function test_admin_crea_tipo_correctamente(): void
    {
        $this->actingAs($this->admin())
            ->post(route('tipos-vehiculo.store'), [
                'codigo'        => 'moto',
                'nombre'        => 'Motocicleta',
                'descripcion'   => 'Vehículo de dos ruedas.',
                'aplica_tarifa' => '1',
                'activo'        => '1',
            ])
            ->assertRedirect(route('tipos-vehiculo.index'));

        $this->assertDatabaseHas('tipos_vehiculo', [
            'codigo' => 'moto',
            'nombre' => 'Motocicleta',
        ]);
    }

    public function test_codigo_duplicado_falla_validacion(): void
    {
        TipoVehiculo::factory()->create(['codigo' => 'moto']);

        $this->actingAs($this->admin())
            ->post(route('tipos-vehiculo.store'), [
                'codigo' => 'moto',
                'nombre' => 'Otro',
            ])
            ->assertSessionHasErrors('codigo');
    }

    public function test_director_puede_editar_tipo(): void
    {
        $tipo = TipoVehiculo::factory()->create(['nombre' => 'Original', 'codigo' => 'original']);

        $this->actingAs($this->director())
            ->put(route('tipos-vehiculo.update', $tipo), [
                'codigo'        => 'original',
                'nombre'        => 'Actualizado',
                'aplica_tarifa' => '1',
                'activo'        => '1',
            ])
            ->assertRedirect(route('tipos-vehiculo.index'));

        $this->assertEquals('Actualizado', $tipo->fresh()->nombre);
    }

    public function test_admin_puede_desactivar_tipo(): void
    {
        $tipo = TipoVehiculo::factory()->create(['codigo' => 'borrar_me']);

        $this->actingAs($this->admin())
            ->delete(route('tipos-vehiculo.destroy', $tipo))
            ->assertRedirect(route('tipos-vehiculo.index'));

        $this->assertSoftDeleted('tipos_vehiculo', ['id' => $tipo->id]);
    }

    // ===== API móvil =====

    public function test_api_tipos_vehiculo_requiere_token(): void
    {
        $this->getJson('/api/v1/tipos-vehiculo')
            ->assertUnauthorized();
    }

    public function test_api_conductor_obtiene_tipos_activos(): void
    {
        $this->seed(TipoVehiculoSeeder::class);

        $user = $this->conductor();
        $token = $user->createToken('movil')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/tipos-vehiculo')
            ->assertOk()
            ->assertJsonStructure([
                'exito', 'mensaje',
                'datos' => [['id', 'codigo', 'nombre', 'aplica_tarifa']],
            ]);

        $this->assertTrue($response->json('exito'));
        $this->assertCount(6, $response->json('datos'));
    }

    public function test_api_tipos_no_incluye_inactivos(): void
    {
        TipoVehiculo::factory()->create(['activo' => true,  'codigo' => 'tipo_a']);
        TipoVehiculo::factory()->create(['activo' => false, 'codigo' => 'tipo_b']);

        $token = $this->conductor()->createToken('movil')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/tipos-vehiculo')
            ->assertOk();

        $codigos = collect($response->json('datos'))->pluck('codigo');
        $this->assertTrue($codigos->contains('tipo_a'));
        $this->assertFalse($codigos->contains('tipo_b'));
    }
}
