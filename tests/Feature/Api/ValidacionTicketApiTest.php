<?php

// tests/Feature/Api/ValidacionTicketApiTest.php

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
 * Tests de validación de ticket por placa y sesiones de parqueo (Fase 5.D).
 * Arts. 13, 38 Ordenanza SIMETSA.
 */
class ValidacionTicketApiTest extends TestCase
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

    private function crearAgenteParqueo(?User $user = null): AgenteParqueo
    {
        $user ??= $this->agenteUser();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'               => 'AG-0001',
                'solicitud_agente_id'  => null,
                'numero_credencial'    => 'CRED-001',
                'fecha_autorizacion'   => now()->toDateString(),
                'estado'               => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function conductorUser(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function token(User $user): string
    {
        return $user->createToken('movil')->plainTextToken;
    }

    private function agente(): AgenteParqueo
    {
        return AgenteParqueo::where('user_id', $this->agenteUser()->id)->first();
    }

    private function crearTicketConPlaca(string $placa, EstadoTicket $estado = EstadoTicket::Pendiente, int $minutosDesde = 0, int $horas = 1): Ticket
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
            'placa'            => $placa,
        ]);

        $compradoEn = now()->subMinutes($minutosDesde);

        return Ticket::create([
            'codigo'          => 'T-2026-' . str_pad(rand(1, 9999), 5, '0', STR_PAD_LEFT),
            'conductor_id'    => $conductor->id,
            'vehiculo_id'     => $vehiculo->id,
            'zona_id'         => Zona::where('codigo', 'centro')->first()->id,
            'horas_compradas' => $horas,
            'monto'           => 0.25,
            'estado'          => $estado,
            'metodo_pago'     => 'efectivo',
            'es_exonerado'    => false,
            'comprado_en'     => $compradoEn,
            'expira_en'       => $compradoEn->copy()->addHours($horas),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Validación por placa — acceso
    // ────────────────────────────────────────────────────────────────────────

    public function test_validar_placa_requiere_autenticacion(): void
    {
        $this->getJson('/api/v1/tickets/validar/ABC-1234')->assertUnauthorized();
    }

    public function test_conductor_no_puede_validar_placa(): void
    {
        $user = $this->conductorUser();
        $this->withToken($this->token($user))
            ->getJson('/api/v1/tickets/validar/ABC-1234')
            ->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Validación por placa — casos de estado
    // ────────────────────────────────────────────────────────────────────────

    public function test_validar_placa_sin_ticket_devuelve_sin_ticket(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $agente = $this->agenteUser();

        $this->withToken($this->token($agente))
            ->getJson('/api/v1/tickets/validar/SIN-0000')
            ->assertOk()
            ->assertJsonPath('datos.estado', 'sin_ticket')
            ->assertJsonPath('datos.en_tolerancia', false);
    }

    public function test_validar_placa_con_ticket_activo_devuelve_activo(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $agente = $this->agenteUser();
        $ticket = $this->crearTicketConPlaca('ACT-1234', EstadoTicket::Activo, 10, 1);

        // Crear sesión para que el estado sea "activo"
        SesionParqueo::create([
            'ticket_id'        => $ticket->id,
            'inicio_at'        => now()->subMinutes(10),
            'fin_programado_at'=> now()->addMinutes(50),
            'estado'           => EstadoSesionParqueo::Activa,
        ]);

        $this->withToken($this->token($agente))
            ->getJson('/api/v1/tickets/validar/ACT-1234')
            ->assertOk()
            ->assertJsonPath('datos.estado', 'activo')
            ->assertJsonPath('datos.en_tolerancia', false);
    }

    public function test_validar_placa_ticket_vencido_3_min_es_en_tolerancia(): void
    {
        // Ticket venció hace 3 min → en_tolerancia (Art. 13: 5 min de gracia)
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $agente = $this->agenteUser();
        $ticket = $this->crearTicketConPlaca('TOL-0003', EstadoTicket::Activo, 63, 1);

        $this->withToken($this->token($agente))
            ->getJson('/api/v1/tickets/validar/TOL-0003')
            ->assertOk()
            ->assertJsonPath('datos.estado', 'en_tolerancia')
            ->assertJsonPath('datos.en_tolerancia', true);
    }

    public function test_validar_placa_ticket_vencido_7_min_es_expirado(): void
    {
        Carbon::setTestNow(self::HORA_OPERATIVA);
        $agente = $this->agenteUser();
        $ticket = $this->crearTicketConPlaca('EXP-0007', EstadoTicket::Activo, 67, 1);

        $this->withToken($this->token($agente))
            ->getJson('/api/v1/tickets/validar/EXP-0007')
            ->assertOk()
            ->assertJsonPath('datos.estado', 'expirado')
            ->assertJsonPath('datos.en_tolerancia', false);
    }
}
