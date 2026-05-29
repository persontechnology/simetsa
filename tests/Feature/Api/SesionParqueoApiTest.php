<?php

// tests/Feature/Api/SesionParqueoApiTest.php

namespace Tests\Feature\Api;

use App\Enums\EstadoSesionParqueo;
use App\Enums\EstadoTicket;
use App\Models\AgenteParqueo;
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
 * Tests de endpoints de sesiones de parqueo para el agente (Fase 5.D).
 * Art. 38 Ordenanza SIMETSA.
 */
class SesionParqueoApiTest extends TestCase
{
    use RefreshDatabase;

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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function agenteUser(): User
    {
        return User::where('email', 'agente@simetsa.gob.ec')->first();
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function comisarioUser(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    /** Crea (o recupera) el registro AgenteParqueo para el usuario agente del seeder. */
    private function crearAgenteParqueo(?User $user = null): AgenteParqueo
    {
        $user ??= $this->agenteUser();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'              => 'AG-TEST1',
                'solicitud_agente_id' => null,
                'numero_credencial'   => 'CRED-001',
                'fecha_autorizacion'  => now()->toDateString(),
                'estado'              => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function crearTicketPendiente(): Ticket
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
            'codigo'          => 'T-2026-TEST01',
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => Zona::where('codigo', 'centro')->first()->id,
            'horas_compradas' => 1,
            'monto'           => 0.25,
            'estado'          => EstadoTicket::Pendiente,
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => now(),
            'expira_en'       => now()->addHour(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Iniciar sesión
    // ────────────────────────────────────────────────────────────────────────

    public function test_iniciar_sesion_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/sesiones-parqueo', [])->assertUnauthorized();
    }

    public function test_conductor_no_puede_iniciar_sesion(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $ticket = $this->crearTicketPendiente();

        $this->withToken($this->token($this->conductorUser()))
            ->postJson('/api/v1/sesiones-parqueo', ['ticket_id' => $ticket->id])
            ->assertForbidden();
    }

    public function test_agente_puede_iniciar_sesion_para_ticket_pendiente(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $ticket = $this->crearTicketPendiente();
        $this->crearAgenteParqueo(); // Crear perfil AgenteParqueo en BD
        $agente = $this->agenteUser();

        $this->withToken($this->token($agente))
            ->postJson('/api/v1/sesiones-parqueo', [
                'ticket_id' => $ticket->id,
                'latitud'   => -1.0458,
                'longitud'  => -78.5916,
            ])
            ->assertCreated()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('datos.ticket_id', $ticket->id);

        $this->assertDatabaseHas('sesiones_parqueo', ['ticket_id' => $ticket->id]);
        $this->assertEquals('activo', $ticket->fresh()->estado->value);
    }

    public function test_agente_no_puede_iniciar_segunda_sesion_para_mismo_ticket(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $ticket = $this->crearTicketPendiente();
        $this->crearAgenteParqueo();
        $agente = $this->agenteUser();

        // Primera sesión
        $this->withToken($this->token($agente))
            ->postJson('/api/v1/sesiones-parqueo', ['ticket_id' => $ticket->id])
            ->assertCreated();

        // Intento de segunda sesión
        $this->withToken($this->token($agente))
            ->postJson('/api/v1/sesiones-parqueo', ['ticket_id' => $ticket->id])
            ->assertStatus(422)
            ->assertJsonPath('exito', false);
    }

    public function test_agente_sin_perfil_de_agente_recibe_403(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $ticket = $this->crearTicketPendiente();

        // Usuario con rol agente_parqueo pero sin perfil en tabla agentes_parqueo
        $user = User::factory()->create();
        $user->assignRole('agente_parqueo');

        $this->withToken($this->token($user))
            ->postJson('/api/v1/sesiones-parqueo', ['ticket_id' => $ticket->id])
            ->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Detalle de sesión
    // ────────────────────────────────────────────────────────────────────────

    public function test_agente_puede_ver_detalle_de_sesion(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $ticket      = $this->crearTicketPendiente();
        $agente      = $this->agenteUser();
        $agenteModel = $this->crearAgenteParqueo();

        $sesion = SesionParqueo::create([
            'ticket_id'        => $ticket->id,
            'agente_id'        => $agenteModel->id,
            'inicio_at'        => now(),
            'fin_programado_at'=> now()->addHour(),
            'estado'           => EstadoSesionParqueo::Activa,
        ]);

        $this->withToken($this->token($agente))
            ->getJson("/api/v1/sesiones-parqueo/{$sesion->id}")
            ->assertOk()
            ->assertJsonPath('datos.id', $sesion->id);
    }

    public function test_comisario_puede_ver_cualquier_sesion(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $ticket = $this->crearTicketPendiente();

        $sesion = SesionParqueo::create([
            'ticket_id'        => $ticket->id,
            'inicio_at'        => now(),
            'fin_programado_at'=> now()->addHour(),
            'estado'           => EstadoSesionParqueo::Activa,
        ]);

        $this->withToken($this->token($this->comisarioUser()))
            ->getJson("/api/v1/sesiones-parqueo/{$sesion->id}")
            ->assertOk();
    }
}
