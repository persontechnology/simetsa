<?php

// tests/Feature/IntegracionPagosTest.php

namespace Tests\Feature;

use App\Enums\EstadoReembolso;
use App\Enums\EstadoTicket;
use App\Enums\EstadoTransaccion;
use App\Models\Conductor;
use App\Models\Ticket;
use App\Models\TipoVehiculo;
use App\Models\TransaccionPago;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Zona;
use App\Services\TicketService;
use Carbon\Carbon;
use Database\Seeders\HorarioOperacionSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests de integración: pagos digitales ↔ tickets ↔ webhook — Fase 6.C.
 */
class IntegracionPagosTest extends TestCase
{
    use RefreshDatabase;

    private TicketService $service;

    private const HORA_OPERATIVA = '2026-03-03 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            HorarioOperacionSeeder::class,
            ZonaSeeder::class,
        ]);
        $this->service = app(TicketService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function crearConductorConVehiculo(): array
    {
        $user      = $this->conductorUser();
        $conductor = Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-PAG1', 'estado' => Conductor::ESTADO_ACTIVO]
        );
        $tipo    = TipoVehiculo::factory()->create(['aplica_tarifa' => true]);
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'PAG-001',
            'estado'           => Vehiculo::ESTADO_ACTIVO,
        ]);

        return [$conductor, $vehiculo];
    }

    private function datosCompraEfectivo(int $conductorId, int $vehiculoId): array
    {
        return [
            'conductor_id'    => $conductorId,
            'vehiculo_id'     => $vehiculoId,
            'zona_id'         => Zona::where('codigo', 'centro')->first()->id,
            'calle_id'        => null,
            'horas_compradas' => 1,
            'metodo_pago'     => 'efectivo',
            'proveedor'       => 'none',
        ];
    }

    private function datosCompraDeuna(int $conductorId, int $vehiculoId): array
    {
        return [
            'conductor_id'    => $conductorId,
            'vehiculo_id'     => $vehiculoId,
            'zona_id'         => Zona::where('codigo', 'centro')->first()->id,
            'calle_id'        => null,
            'horas_compradas' => 1,
            'metodo_pago'     => 'link',
            'proveedor'       => 'deuna',
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Compra con proveedor digital → nace en pendiente_pago
    // ────────────────────────────────────────────────────────────────────────

    public function test_compra_con_deuna_crea_ticket_en_pendiente_pago(): void
    {
        Http::fake(); // Garantiza que Deuna no hace llamadas HTTP
        Carbon::setTestNow(self::HORA_OPERATIVA);
        config(['pagos.deuna.enabled' => false]); // modo fake

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $ticket = $this->service->comprar($this->datosCompraDeuna($conductor->id, $vehiculo->id));

        $this->assertEquals(EstadoTicket::PendientePago, $ticket->estado);

        Http::assertNothingSent();
    }

    public function test_compra_con_deuna_crea_transaccion_pago_pendiente(): void
    {
        Http::fake();
        Carbon::setTestNow(self::HORA_OPERATIVA);
        config(['pagos.deuna.enabled' => false]);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $ticket = $this->service->comprar($this->datosCompraDeuna($conductor->id, $vehiculo->id));

        $this->assertCount(1, $ticket->transacciones);
        $this->assertEquals(EstadoTransaccion::Pendiente, $ticket->transacciones->first()->estado);
    }

    public function test_compra_con_efectivo_crea_ticket_en_pendiente(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();

        $ticket = $this->service->comprar($this->datosCompraEfectivo($conductor->id, $vehiculo->id));

        $this->assertEquals(EstadoTicket::Pendiente, $ticket->estado);
        $this->assertCount(0, $ticket->transacciones);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Webhook: completada → ticket pasa a Pendiente (listo para agente)
    // ────────────────────────────────────────────────────────────────────────

    public function test_webhook_completada_transiciona_ticket_a_pendiente(): void
    {
        Http::fake();
        Carbon::setTestNow(self::HORA_OPERATIVA);
        config(['pagos.deuna.enabled' => false]);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $ticket      = $this->service->comprar($this->datosCompraDeuna($conductor->id, $vehiculo->id));
        $transaccion = $ticket->transacciones->first();

        $conductorUser = $this->conductorUser();
        $payload       = [
            'external_reference' => $transaccion->external_reference,
            'status'             => 'approved',
        ];

        $response = $this->withToken($this->token($conductorUser))
            ->postJson("/api/v1/pagos/webhook/deuna", $payload);

        // El webhook es público (sin auth), pero usamos token para simplificar el test
        // La ruta es pública, así que también funciona sin token
        $response->assertOk();

        $this->assertEquals(EstadoTicket::Pendiente, $ticket->fresh()->estado);
        $this->assertEquals(EstadoTransaccion::Completada, $transaccion->fresh()->estado);
    }

    public function test_webhook_sin_token_es_accesible_publicamente(): void
    {
        Http::fake();
        Carbon::setTestNow(self::HORA_OPERATIVA);
        config(['pagos.deuna.enabled' => false]);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $ticket      = $this->service->comprar($this->datosCompraDeuna($conductor->id, $vehiculo->id));
        $transaccion = $ticket->transacciones->first();

        $response = $this->postJson('/api/v1/pagos/webhook/deuna', [
            'external_reference' => $transaccion->external_reference,
            'status'             => 'approved',
        ]);

        $response->assertOk()
            ->assertJsonPath('exito', true);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Cancelación y estado de reembolso
    // ────────────────────────────────────────────────────────────────────────

    public function test_cancelar_ticket_efectivo_es_no_aplica(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $ticket = $this->service->comprar($this->datosCompraEfectivo($conductor->id, $vehiculo->id));

        $cancelacion = $this->service->cancelar($ticket, $this->conductorUser(), 'Sin necesidad.');

        $this->assertEquals(EstadoReembolso::NoAplica, $cancelacion->estado_reembolso);
    }

    public function test_cancelar_ticket_digital_sin_pago_confirmado_es_no_aplica(): void
    {
        Http::fake();
        Carbon::setTestNow(self::HORA_OPERATIVA);
        config(['pagos.deuna.enabled' => false]);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $ticket = $this->service->comprar($this->datosCompraDeuna($conductor->id, $vehiculo->id));

        // La transacción existe pero está en estado Pendiente (no Completada)
        $cancelacion = $this->service->cancelar($ticket, $this->conductorUser(), 'Cancelé el pago.');

        $this->assertEquals(EstadoReembolso::NoAplica, $cancelacion->estado_reembolso);
    }

    public function test_cancelar_ticket_digital_con_pago_confirmado_es_pendiente(): void
    {
        Http::fake();
        Carbon::setTestNow(self::HORA_OPERATIVA);
        config(['pagos.deuna.enabled' => false]);

        [$conductor, $vehiculo] = $this->crearConductorConVehiculo();
        $ticket      = $this->service->comprar($this->datosCompraDeuna($conductor->id, $vehiculo->id));
        $transaccion = $ticket->transacciones->first();

        // Simular pago confirmado
        $transaccion->update(['estado' => EstadoTransaccion::Completada]);
        $ticket->update(['estado' => EstadoTicket::Pendiente]);

        $cancelacion = $this->service->cancelar($ticket->fresh(), $this->conductorUser(), 'Cambié de idea.');

        $this->assertEquals(EstadoReembolso::Pendiente, $cancelacion->estado_reembolso);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helper: obtener token Sanctum
    // ────────────────────────────────────────────────────────────────────────

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }
}
