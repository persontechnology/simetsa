<?php

// tests/Feature/DeunaPaymentProviderTest.php

namespace Tests\Feature;

use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use App\Models\Conductor;
use App\Models\Ticket;
use App\Models\TipoVehiculo;
use App\Models\TransaccionPago;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Zona;
use App\Services\Pagos\DeunaPaymentProvider;
use App\Services\Pagos\PagoManager;
use Carbon\Carbon;
use Database\Seeders\HorarioOperacionSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests del DeunaPaymentProvider en modo fake — Fase 6.A.
 *
 * Seguridad: Http::assertNothingSent() garantiza que NUNCA se hace
 * una llamada HTTP externa cuando DEUNA_ENABLED=false o DEUNA_MODE=fake.
 */
class DeunaPaymentProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            HorarioOperacionSeeder::class,
            ZonaSeeder::class,
        ]);
    }

    private function crearTicket(): Ticket
    {
        $user      = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $conductor = Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-DEU1', 'estado' => Conductor::ESTADO_ACTIVO]
        );
        $tipo    = TipoVehiculo::factory()->create(['aplica_tarifa' => true]);
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'DEU-001',
            'estado'           => Vehiculo::ESTADO_ACTIVO,
        ]);
        $zona = Zona::where('codigo', 'centro')->first();

        return Ticket::create([
            'codigo'          => Ticket::generarCodigo(),
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $zona->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => \App\Enums\EstadoTicket::Pendiente,
            'metodo_pago'     => 'link',
            'proveedor'       => 'deuna',
            'es_exonerado'    => false,
            'comprado_en'     => now(),
            'expira_en'       => now()->addHour(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Seguridad: sin llamadas HTTP en modo fake
    // ────────────────────────────────────────────────────────────────────────

    public function test_iniciar_cobro_no_hace_llamadas_http_cuando_deuna_disabled(): void
    {
        Http::fake(); // Intercepta cualquier llamada real
        config(['pagos.deuna.enabled' => false]);

        $ticket   = $this->crearTicket();
        $provider = new DeunaPaymentProvider();
        $provider->iniciarCobro($ticket);

        Http::assertNothingSent();
    }

    public function test_iniciar_cobro_no_hace_llamadas_http_cuando_modo_fake(): void
    {
        Http::fake();
        config(['pagos.deuna.enabled' => true, 'pagos.deuna.mode' => 'fake']);

        $ticket   = $this->crearTicket();
        $provider = new DeunaPaymentProvider();
        $provider->iniciarCobro($ticket);

        Http::assertNothingSent();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Comportamiento de iniciarCobro en modo fake
    // ────────────────────────────────────────────────────────────────────────

    public function test_iniciar_cobro_crea_transaccion_con_estado_pendiente(): void
    {
        config(['pagos.deuna.enabled' => false]);

        $ticket   = $this->crearTicket();
        $provider = new DeunaPaymentProvider();
        $transaccion = $provider->iniciarCobro($ticket);

        $this->assertInstanceOf(TransaccionPago::class, $transaccion);
        $this->assertEquals(EstadoTransaccion::Pendiente, $transaccion->estado);
        $this->assertEquals(ProveedorPago::Deuna, $transaccion->proveedor);
        $this->assertEquals($ticket->montoCobrable(), (float) $transaccion->monto);
        $this->assertStringStartsWith('fake-', $transaccion->external_reference);
        $this->assertNotNull($transaccion->payment_url);
    }

    public function test_transaccion_pago_es_polimorfica_al_ticket(): void
    {
        config(['pagos.deuna.enabled' => false]);

        $ticket   = $this->crearTicket();
        $provider = new DeunaPaymentProvider();
        $transaccion = $provider->iniciarCobro($ticket);

        $this->assertInstanceOf(Ticket::class, $transaccion->concepto);
        $this->assertEquals($ticket->id, $transaccion->concepto->id);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Cobrable — Ticket implementa la interfaz correctamente
    // ────────────────────────────────────────────────────────────────────────

    public function test_ticket_monto_cobrable_devuelve_el_monto(): void
    {
        $ticket = $this->crearTicket();

        $this->assertEquals(0.25, $ticket->montoCobrable());
    }

    public function test_ticket_descripcion_cobro_contiene_el_codigo(): void
    {
        $ticket = $this->crearTicket();

        $this->assertStringContainsString($ticket->codigo, $ticket->descripcionCobro());
    }

    // ────────────────────────────────────────────────────────────────────────
    // PagoManager
    // ────────────────────────────────────────────────────────────────────────

    public function test_pago_manager_predeterminado_devuelve_deuna(): void
    {
        config(['pagos.default_provider' => 'deuna']);
        $manager = app(PagoManager::class);

        $this->assertInstanceOf(DeunaPaymentProvider::class, $manager->predeterminado());
    }

    public function test_pago_manager_lanza_excepcion_si_proveedor_no_registrado(): void
    {
        $manager = app(PagoManager::class);

        $this->expectException(DomainException::class);
        $manager->proveedor('inexistente');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Webhook — idempotencia
    // ────────────────────────────────────────────────────────────────────────

    public function test_webhook_fake_actualiza_estado_a_completada(): void
    {
        config(['pagos.deuna.enabled' => false]);

        $ticket      = $this->crearTicket();
        $provider    = new DeunaPaymentProvider();
        $transaccion = $provider->iniciarCobro($ticket);

        $payload = [
            'external_reference' => $transaccion->external_reference,
            'status'             => 'approved',
        ];

        $resultado = $provider->procesarWebhook($payload, '');

        $this->assertEquals(EstadoTransaccion::Completada, $resultado->estado);
        $this->assertNotNull($resultado->callback_recibido_en);
    }

    public function test_webhook_es_idempotente_para_transacciones_ya_completadas(): void
    {
        config(['pagos.deuna.enabled' => false]);

        $ticket      = $this->crearTicket();
        $provider    = new DeunaPaymentProvider();
        $transaccion = $provider->iniciarCobro($ticket);

        // Primera llamada: completada
        $payload = ['external_reference' => $transaccion->external_reference, 'status' => 'approved'];
        $provider->procesarWebhook($payload, '');

        // Segunda llamada: no debe cambiar nada
        $resultado = $provider->procesarWebhook($payload, '');
        $this->assertEquals(EstadoTransaccion::Completada, $resultado->estado);
    }
}
