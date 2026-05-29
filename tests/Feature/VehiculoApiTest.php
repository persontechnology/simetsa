<?php
// tests/Feature/VehiculoApiTest.php

namespace Tests\Feature;

use App\Models\Conductor;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\TipoVehiculoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de gestión de vehículos del conductor vía API móvil (Fase 4.B — Art. 25 Ordenanza SIMETSA).
 */
class VehiculoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolPermisoSeeder::class, UsuarioPruebaSeeder::class, TipoVehiculoSeeder::class]);
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function crearConductor(?User $user = null): Conductor
    {
        $user ??= $this->conductorUser();

        return Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-' . str_pad((string) ($user->id + 90000), 5, '0', STR_PAD_LEFT), 'estado' => Conductor::ESTADO_ACTIVO],
        );
    }

    private function tipoVehiculo(): TipoVehiculo
    {
        return TipoVehiculo::where('codigo', 'liviano_privado')->first();
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    // ===== Autenticación =====

    public function test_listar_vehiculos_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/vehiculos')->assertUnauthorized();
    }

    public function test_crear_vehiculo_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/vehiculos', [])->assertUnauthorized();
    }

    // ===== Listado =====

    public function test_conductor_puede_listar_sus_vehiculos(): void
    {
        $conductor = $this->crearConductor();
        $tipo = $this->tipoVehiculo();

        Vehiculo::factory()->count(2)->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
        ]);

        // Vehículo de otro conductor: NO debe aparecer en el listado
        $otroConductor = Conductor::factory()->create();
        Vehiculo::factory()->create([
            'conductor_id'     => $otroConductor->id,
            'tipo_vehiculo_id' => $tipo->id,
        ]);

        $response = $this->withToken($this->token($this->conductorUser()))
            ->getJson('/api/v1/vehiculos')
            ->assertOk();

        $this->assertTrue($response->json('exito'));
        $this->assertCount(2, $response->json('datos'));
    }

    // ===== Creación =====

    public function test_conductor_registra_vehiculo_correctamente(): void
    {
        $this->crearConductor();

        $response = $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/vehiculos', [
                'tipo_vehiculo_id' => $this->tipoVehiculo()->id,
                'placa'            => 'ABC-1234',
                'marca'            => 'Toyota',
                'modelo'           => 'Corolla',
                'anio'             => 2020,
                'color'            => 'Blanco',
            ])
            ->assertCreated();

        $this->assertTrue($response->json('exito'));
        $this->assertEquals('ABC-1234', $response->json('datos.placa'));
        $this->assertDatabaseHas('vehiculos', ['placa' => 'ABC-1234']);
    }

    public function test_placa_con_formato_invalido_falla_validacion(): void
    {
        $this->crearConductor();

        $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/vehiculos', [
                'tipo_vehiculo_id' => $this->tipoVehiculo()->id,
                'placa'            => 'INVALIDA',
                'marca'            => 'Toyota',
                'modelo'           => 'Corolla',
                'anio'             => 2020,
                'color'            => 'Blanco',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    public function test_placa_duplicada_de_otro_conductor_falla(): void
    {
        $this->crearConductor();
        $tipo = $this->tipoVehiculo();

        // Otro conductor ya tiene la placa
        $otroConductor = Conductor::factory()->create();
        Vehiculo::factory()->create([
            'conductor_id'     => $otroConductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'XYZ-9999',
        ]);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/vehiculos', [
                'tipo_vehiculo_id' => $tipo->id,
                'placa'            => 'xyz-9999',    // minúsculas: el servicio normaliza a mayúsculas
                'marca'            => 'Chevrolet',
                'modelo'           => 'Aveo',
                'anio'             => 2019,
                'color'            => 'Rojo',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    // ===== Detalle =====

    public function test_conductor_puede_ver_su_propio_vehiculo(): void
    {
        $conductor = $this->crearConductor();
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $this->tipoVehiculo()->id,
            'placa'            => 'MNO-5678',
        ]);

        $response = $this->withToken($this->token($this->conductorUser()))
            ->getJson("/api/v1/vehiculos/{$vehiculo->id}")
            ->assertOk();

        $this->assertEquals('MNO-5678', $response->json('datos.placa'));
    }

    public function test_conductor_no_puede_ver_vehiculo_ajeno(): void
    {
        $this->crearConductor();

        $otroConductor = Conductor::factory()->create();
        $vehiculoAjeno = Vehiculo::factory()->create([
            'conductor_id'     => $otroConductor->id,
            'tipo_vehiculo_id' => $this->tipoVehiculo()->id,
        ]);

        $this->withToken($this->token($this->conductorUser()))
            ->getJson("/api/v1/vehiculos/{$vehiculoAjeno->id}")
            ->assertForbidden();
    }

    // ===== Actualización =====

    public function test_conductor_puede_actualizar_su_vehiculo(): void
    {
        $conductor = $this->crearConductor();
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $this->tipoVehiculo()->id,
            'color'            => 'Azul',
        ]);

        $response = $this->withToken($this->token($this->conductorUser()))
            ->putJson("/api/v1/vehiculos/{$vehiculo->id}", ['color' => 'Verde'])
            ->assertOk();

        $this->assertEquals('Verde', $response->json('datos.color'));
        $this->assertEquals('Verde', $vehiculo->fresh()->color);
    }

    public function test_conductor_no_puede_actualizar_vehiculo_ajeno(): void
    {
        $this->crearConductor();

        $otroConductor = Conductor::factory()->create();
        $vehiculoAjeno = Vehiculo::factory()->create([
            'conductor_id'     => $otroConductor->id,
            'tipo_vehiculo_id' => $this->tipoVehiculo()->id,
        ]);

        $this->withToken($this->token($this->conductorUser()))
            ->putJson("/api/v1/vehiculos/{$vehiculoAjeno->id}", ['color' => 'Rojo'])
            ->assertForbidden();
    }

    // ===== Eliminación =====

    public function test_conductor_puede_eliminar_su_vehiculo(): void
    {
        $conductor = $this->crearConductor();
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $this->tipoVehiculo()->id,
        ]);

        $this->withToken($this->token($this->conductorUser()))
            ->deleteJson("/api/v1/vehiculos/{$vehiculo->id}")
            ->assertOk()
            ->assertJsonPath('exito', true);

        $this->assertSoftDeleted('vehiculos', ['id' => $vehiculo->id]);
    }

    public function test_puede_registrar_misma_placa_tras_soft_delete(): void
    {
        $conductor = $this->crearConductor();
        $tipo = $this->tipoVehiculo();

        // Crear y luego eliminar el vehículo
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'DEL-0001',
        ]);
        $vehiculo->delete();

        // Re-registrar la misma placa debe ser posible (índice parcial excluye eliminados)
        $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/vehiculos', [
                'tipo_vehiculo_id' => $tipo->id,
                'placa'            => 'DEL-0001',
                'marca'            => 'Nissan',
                'modelo'           => 'Sentra',
                'anio'             => 2021,
                'color'            => 'Negro',
            ])
            ->assertCreated();

        $this->assertSoftDeleted('vehiculos', ['placa' => 'DEL-0001', 'id' => $vehiculo->id]);
        $this->assertDatabaseHas('vehiculos', ['placa' => 'DEL-0001', 'deleted_at' => null]);
    }
}
