<?php

// tests/Feature/TicketControllerTest.php

namespace Tests\Feature;

use App\Enums\EstadoTicket;
use App\Models\Conductor;
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
 * Tests del backoffice de supervisión de tickets (Fases 5.E y 5.F).
 */
class TicketControllerTest extends TestCase
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

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function crearTicket(EstadoTicket $estado = EstadoTicket::Pendiente): Ticket
    {
        $conductorUser = $this->conductorUser();
        $conductor     = Conductor::firstOrCreate(
            ['user_id' => $conductorUser->id],
            ['codigo' => 'CD-001', 'estado' => Conductor::ESTADO_ACTIVO]
        );
        $tipo     = TipoVehiculo::factory()->create();
        $vehiculo = Vehiculo::factory()->create([
            'conductor_id'     => $conductor->id,
            'tipo_vehiculo_id' => $tipo->id,
        ]);

        return Ticket::create([
            'codigo'          => 'T-2026-00001',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => Zona::where('codigo', 'centro')->first()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => $estado,
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now(),
            'expira_en'       => now()->addHour(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Acceso al listado
    // ────────────────────────────────────────────────────────────────────────

    public function test_listado_requiere_autenticacion(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }

    public function test_conductor_no_puede_acceder_al_listado(): void
    {
        $this->actingAs($this->conductorUser())
            ->get(route('tickets.index'))
            ->assertForbidden();
    }

    public function test_comisario_puede_ver_listado_de_tickets(): void
    {
        Carbon::setTestNow('2026-03-03 10:00:00');
        $this->crearTicket();

        $this->actingAs($this->comisarioUser())
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('T-2026-00001');
    }

    public function test_listado_filtra_por_estado(): void
    {
        Carbon::setTestNow('2026-03-03 10:00:00');
        $this->crearTicket(EstadoTicket::Pendiente);

        $this->actingAs($this->comisarioUser())
            ->get(route('tickets.index', ['estado' => 'cancelado']))
            ->assertOk()
            ->assertDontSee('T-2026-00001');
    }

    // ────────────────────────────────────────────────────────────────────────
    // Detalle
    // ────────────────────────────────────────────────────────────────────────

    public function test_comisario_puede_ver_detalle_de_ticket(): void
    {
        Carbon::setTestNow('2026-03-03 10:00:00');
        $ticket = $this->crearTicket();

        $this->actingAs($this->comisarioUser())
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->codigo);
    }

    public function test_conductor_no_puede_ver_detalle_backoffice(): void
    {
        Carbon::setTestNow('2026-03-03 10:00:00');
        $ticket = $this->crearTicket();

        $this->actingAs($this->conductorUser())
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Anulación (5.F)
    // ────────────────────────────────────────────────────────────────────────

    public function test_comisario_puede_anular_ticket_pendiente(): void
    {
        Carbon::setTestNow('2026-03-03 10:00:00');
        $ticket = $this->crearTicket(EstadoTicket::Pendiente);

        $this->actingAs($this->comisarioUser())
            ->patch(route('tickets.anular', $ticket), [
                'motivo' => 'Anulación por solicitud del supervisor.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertEquals(EstadoTicket::Anulado, $ticket->fresh()->estado);
        $this->assertDatabaseHas('cancelaciones', ['ticket_id' => $ticket->id]);
    }

    public function test_conductor_no_puede_anular_ticket_via_backoffice(): void
    {
        Carbon::setTestNow('2026-03-03 10:00:00');
        $ticket = $this->crearTicket(EstadoTicket::Pendiente);

        $this->actingAs($this->conductorUser())
            ->patch(route('tickets.anular', $ticket), [
                'motivo' => 'Intento no autorizado.',
            ])
            ->assertForbidden();
    }

    public function test_anular_ticket_ya_cancelado_muestra_error(): void
    {
        Carbon::setTestNow('2026-03-03 10:00:00');
        $ticket = $this->crearTicket(EstadoTicket::Cancelado);

        $this->actingAs($this->comisarioUser())
            ->patch(route('tickets.anular', $ticket), [
                'motivo' => 'Intento de anular ticket ya cancelado.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
