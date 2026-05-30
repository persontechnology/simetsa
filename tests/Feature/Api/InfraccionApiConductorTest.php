<?php

// tests/Feature/Api/InfraccionApiConductorTest.php

namespace Tests\Feature\Api;

use App\Enums\EstadoInfraccion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Conductor;
use App\Models\Infraccion;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Zona;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la API de infracciones para el conductor (Fase 7.D).
 * Cubre historial de infracciones propias y pago de multas.
 */
class InfraccionApiConductorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
            ParametroSeeder::class,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function agenteUser(): User
    {
        return User::where('email', 'agente@simetsa.gob.ec')->first();
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private function crearConductorConVehiculo(string $placa = 'ABC1234'): array
    {
        $user      = $this->conductorUser();
        $conductor = Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-TEST1', 'estado' => Conductor::ESTADO_ACTIVO]
        );
        $tipo     = TipoVehiculo::factory()->create(['aplica_tarifa' => true]);
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => $placa,
            'estado'           => Vehiculo::ESTADO_ACTIVO,
        ]);

        return [$conductor, $vehiculo];
    }

    private function crearAgente(): AgenteParqueo
    {
        return AgenteParqueo::firstOrCreate(
            ['user_id' => $this->agenteUser()->id],
            [
                'codigo'                  => 'AG-TEST1',
                'numero_credencial'       => 'CRED-001',
                'carta_compromiso_firmada'=> true,
                'fecha_autorizacion'      => now()->toDateString(),
                'estado'                  => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function crearInfraccion(AgenteParqueo $agente, string $placa, ?int $conductorId = null): Infraccion
    {
        return Infraccion::create([
            'placa'             => $placa,
            'conductor_id'      => $conductorId,
            'zona_id'           => Zona::where('codigo', 'centro')->first()->id,
            'agente_parqueo_id' => $agente->id,
            'tipo_infraccion'   => TipoInfraccion::SinTicketVisible,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => 9.20,
            'sbu_vigente'       => 460.00,
        ]);
    }

    // ── GET /api/v1/conductor/infracciones ────────────────────────────────────

    /** Sin token → 401. */
    public function test_historial_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/conductor/infracciones')->assertUnauthorized();
    }

    /** Conductor ve infracciones cuya placa coincide con sus vehículos. */
    public function test_conductor_ve_infracciones_por_placa_de_vehiculo(): void
    {
        $this->crearConductorConVehiculo('ABC1234');
        $agente = $this->crearAgente();

        // Infracción asociada a la placa del conductor (sin conductor_id)
        $this->crearInfraccion($agente, 'ABC1234');

        // Infracción de otra placa (no debe aparecer)
        $this->crearInfraccion($agente, 'XYZ9999');

        $this->withToken($this->token($this->conductorUser()))
            ->getJson('/api/v1/conductor/infracciones')
            ->assertOk()
            ->assertJsonCount(1, 'datos')
            ->assertJsonPath('datos.0.placa', 'ABC1234');
    }

    /** Conductor ve infracciones vinculadas por conductor_id aunque la placa no esté registrada. */
    public function test_conductor_ve_infracciones_por_conductor_id(): void
    {
        [$conductor] = $this->crearConductorConVehiculo('ABC1234');
        $agente      = $this->crearAgente();

        // Infracción vinculada directamente por conductor_id
        $this->crearInfraccion($agente, 'ZZZ9999', $conductor->id);

        $this->withToken($this->token($this->conductorUser()))
            ->getJson('/api/v1/conductor/infracciones')
            ->assertOk()
            ->assertJsonCount(1, 'datos');
    }

    /** Conductor sin vehículos ni infracciones ve lista vacía. */
    public function test_conductor_sin_infracciones_ve_lista_vacia(): void
    {
        Conductor::firstOrCreate(
            ['user_id' => $this->conductorUser()->id],
            ['codigo' => 'CD-EMPTY', 'estado' => Conductor::ESTADO_ACTIVO]
        );

        $this->withToken($this->token($this->conductorUser()))
            ->getJson('/api/v1/conductor/infracciones')
            ->assertOk()
            ->assertJsonCount(0, 'datos');
    }

    /** El agente no puede usar la ruta de conductor (sin el permiso correcto en contexto conductor). */
    public function test_agente_accede_al_historial_pero_ve_solo_sus_placas(): void
    {
        // El agente tiene infracciones.ver → puede acceder, pero si no es conductor, 403
        $this->crearAgente();

        $this->withToken($this->token($this->agenteUser()))
            ->getJson('/api/v1/conductor/infracciones')
            ->assertForbidden();  // no es conductor → error 403 del controller
    }

    // ── POST /api/v1/infracciones/{id}/pagar ──────────────────────────────────

    /** Sin token → 401. */
    public function test_pagar_requiere_autenticacion(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente, 'ABC1234');

        $this->postJson("/api/v1/infracciones/{$infraccion->id}/pagar", ['proveedor' => 'deuna'])
            ->assertUnauthorized();
    }

    /** Conductor paga multa de su propio vehículo → 201 con transacción. */
    public function test_conductor_inicia_pago_de_su_multa(): void
    {
        [$conductor] = $this->crearConductorConVehiculo('ABC1234');
        $agente      = $this->crearAgente();
        $infraccion  = $this->crearInfraccion($agente, 'ABC1234', $conductor->id);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/pagar", ['proveedor' => 'deuna'])
            ->assertCreated()
            ->assertJsonPath('exito', true)
            ->assertJsonStructure(['datos' => [
                'transaccion_id', 'estado', 'monto', 'moneda', 'external_reference',
            ]]);

        $this->assertDatabaseHas('transacciones_pago', [
            'concepto_type' => Infraccion::class,
            'concepto_id'   => $infraccion->id,
        ]);
    }

    /** Conductor no puede pagar multa de un vehículo que no es suyo. */
    public function test_conductor_no_puede_pagar_multa_ajena(): void
    {
        $agente     = $this->crearAgente();
        $infraccion = $this->crearInfraccion($agente, 'XYZ9999'); // placa ajena

        // Crear conductor con otro vehículo
        $this->crearConductorConVehiculo('ABC1234');

        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/pagar", ['proveedor' => 'deuna'])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    /** No se puede pagar una infracción ya pagada. */
    public function test_no_puede_pagar_infraccion_ya_pagada(): void
    {
        [$conductor] = $this->crearConductorConVehiculo('ABC1234');
        $agente      = $this->crearAgente();
        $infraccion  = $this->crearInfraccion($agente, 'ABC1234', $conductor->id);
        $infraccion->update(['estado' => EstadoInfraccion::Pagada]);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/pagar", ['proveedor' => 'deuna'])
            ->assertUnprocessable()
            ->assertJsonPath('exito', false);
    }

    /** Proveedor inválido devuelve 422 de validación. */
    public function test_proveedor_invalido_devuelve_422(): void
    {
        [$conductor] = $this->crearConductorConVehiculo('ABC1234');
        $agente      = $this->crearAgente();
        $infraccion  = $this->crearInfraccion($agente, 'ABC1234', $conductor->id);

        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/pagar", ['proveedor' => 'inexistente'])
            ->assertUnprocessable();
    }

    /** El webhook acredita el pago y libera la inmovilización (Art. 15). */
    public function test_webhook_acredita_pago_y_libera_inmovilizacion(): void
    {
        [$conductor] = $this->crearConductorConVehiculo('ABC1234');
        $agente      = $this->crearAgente();
        $infraccion  = $this->crearInfraccion($agente, 'ABC1234', $conductor->id);

        // Crear inmovilización activa
        \App\Models\Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => \App\Enums\EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        // Iniciar pago (modo fake)
        $this->withToken($this->token($this->conductorUser()))
            ->postJson("/api/v1/infracciones/{$infraccion->id}/pagar", ['proveedor' => 'deuna'])
            ->assertCreated();

        $transaccion = \App\Models\TransaccionPago::where('concepto_id', $infraccion->id)
            ->where('concepto_type', Infraccion::class)
            ->first();

        // Simular webhook de Deuna confirmando el pago
        $this->postJson('/api/v1/pagos/webhook/deuna', [
            'order_id' => $transaccion->external_reference,
            'status'   => 'COMPLETED',
        ])->assertOk();

        $this->assertEquals(EstadoInfraccion::Pagada, $infraccion->fresh()->estado);
        $this->assertEquals(
            \App\Enums\EstadoInmovilizacion::Liberada,
            $infraccion->fresh()->inmovilizacion->estado
        );
    }
}
