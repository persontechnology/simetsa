<?php

// tests/Feature/SincronizarEstadosTicketsTest.php

namespace Tests\Feature;

use App\Enums\EstadoTicket;
use App\Models\Conductor;
use App\Models\SesionParqueo;
use App\Models\Ticket;
use App\Models\TipoVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\Zona;
use Carbon\Carbon;
use Database\Seeders\HorarioOperacionSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del comando simetsa:sincronizar-estados-tickets — Fase 6.0.
 */
class SincronizarEstadosTicketsTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function crearTicketActivo(string $expiraEn): Ticket
    {
        $user      = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $conductor = Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-SYNC1', 'estado' => Conductor::ESTADO_ACTIVO]
        );
        $tipo    = TipoVehiculo::factory()->create(['aplica_tarifa' => true]);
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'SYN-001',
            'estado'           => Vehiculo::ESTADO_ACTIVO,
        ]);
        $zona = Zona::where('codigo', 'centro')->first();

        $ticket = Ticket::create([
            'codigo'          => Ticket::generarCodigo(),
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $zona->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Activo,
            'metodo_pago'     => 'efectivo',
            'proveedor'       => 'none',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subHour(),
            'expira_en'       => $expiraEn,
        ]);

        // Crear sesión para que sea estado Activo válido
        SesionParqueo::create([
            'ticket_id'        => $ticket->id,
            'inicio_at'        => now()->subHour(),
            'fin_programado_at'=> $expiraEn,
            'estado'           => \App\Enums\EstadoSesionParqueo::Activa,
        ]);

        return $ticket;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Tests
    // ────────────────────────────────────────────────────────────────────────

    public function test_ticket_activo_vencido_hace_mas_de_5_min_pasa_a_expirado(): void
    {
        Carbon::setTestNow('2026-03-10 12:00:00');
        $ticket = $this->crearTicketActivo('2026-03-10 10:00:00'); // venció hace 2h

        $this->artisan('simetsa:sincronizar-estados-tickets')
            ->assertExitCode(0);

        $this->assertEquals(EstadoTicket::Expirado, $ticket->fresh()->estado);
    }

    public function test_ticket_activo_vencido_hace_3_min_pasa_a_en_tolerancia(): void
    {
        Carbon::setTestNow('2026-03-10 12:03:00');
        $ticket = $this->crearTicketActivo('2026-03-10 12:00:00'); // venció hace 3 min

        $this->artisan('simetsa:sincronizar-estados-tickets')
            ->assertExitCode(0);

        $this->assertEquals(EstadoTicket::EnTolerancia, $ticket->fresh()->estado);
    }

    public function test_ticket_cancelado_no_se_toca(): void
    {
        Carbon::setTestNow('2026-03-10 12:00:00');

        $user      = User::where('email', 'conductor@simetsa.gob.ec')->first();
        $conductor = Conductor::firstOrCreate(
            ['user_id' => $user->id],
            ['codigo' => 'CD-SYNC2', 'estado' => Conductor::ESTADO_ACTIVO]
        );
        $tipo    = TipoVehiculo::factory()->create(['aplica_tarifa' => true]);
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
            'placa'            => 'SYN-002',
            'estado'           => Vehiculo::ESTADO_ACTIVO,
        ]);
        $zona = Zona::where('codigo', 'centro')->first();

        $ticket = Ticket::create([
            'codigo'          => Ticket::generarCodigo(),
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => $zona->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Cancelado,
            'metodo_pago'     => 'efectivo',
            'proveedor'       => 'none',
            'es_exonerado'    => false,
            'comprado_en'     => now()->subHours(2),
            'expira_en'       => now()->subHour(),
        ]);

        $this->artisan('simetsa:sincronizar-estados-tickets')
            ->assertExitCode(0);

        $this->assertEquals(EstadoTicket::Cancelado, $ticket->fresh()->estado);
    }

    public function test_sin_tickets_candidatos_retorna_exitoso(): void
    {
        $this->artisan('simetsa:sincronizar-estados-tickets')
            ->expectsOutput('Sin tickets a sincronizar.')
            ->assertExitCode(0);
    }

    public function test_dry_run_no_modifica_la_base_de_datos(): void
    {
        Carbon::setTestNow('2026-03-10 12:00:00');
        $ticket = $this->crearTicketActivo('2026-03-10 10:00:00'); // venció hace 2h

        $this->artisan('simetsa:sincronizar-estados-tickets', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertEquals(EstadoTicket::Activo, $ticket->fresh()->estado); // sin cambio
    }
}
