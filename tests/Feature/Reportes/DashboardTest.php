<?php

// tests/Feature/Reportes/DashboardTest.php

namespace Tests\Feature\Reportes;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoTicket;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\SesionParqueo;
use App\Models\Ticket;
use App\Models\TransaccionPago;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests del Dashboard de KPIs (Fase 8.A).
 * Verifica acceso por rol, precisión de KPIs y contrato del endpoint JSON.
 */
class DashboardTest extends TestCase
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
        Cache::flush();
    }

    // ── Helpers de usuarios ───────────────────────────────────────────────────

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
    }

    private function comisario(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function director(): User
    {
        return User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
    }

    private function agente(): User
    {
        return User::where('email', 'agente@simetsa.gob.ec')->first();
    }

    private function conductor(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    // ── Helpers de datos ─────────────────────────────────────────────────────

    private function crearAgente(): AgenteParqueo
    {
        $user = User::where('email', 'agente@simetsa.gob.ec')->first();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'                   => 'AG-0001',
                'numero_credencial'        => 'C-0001',
                'carta_compromiso_firmada' => true,
                'fecha_autorizacion'       => now()->toDateString(),
                'estado'                   => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    // ── Helper para crear transacciones ──────────────────────────────────────

    private function crearTransaccionCompletada(float $monto, ?string $fecha = null): TransaccionPago
    {
        $ticket = Ticket::factory()->create(['estado' => EstadoTicket::Activo]);

        return TransaccionPago::factory()
            ->completada()
            ->create([
                'concepto_type' => Ticket::class,
                'concepto_id'   => $ticket->id,
                'monto'         => $monto,
                'created_at'    => $fecha ? now()->parse($fecha) : now(),
            ]);
    }

    // ── Acceso al dashboard (vista HTML) ─────────────────────────────────────

    public function test_super_admin_puede_ver_el_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_comisario_puede_ver_el_dashboard(): void
    {
        $this->actingAs($this->comisario())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_director_seguridad_puede_ver_el_dashboard(): void
    {
        $this->actingAs($this->director())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_agente_puede_ver_el_dashboard_sin_kpis(): void
    {
        $this->actingAs($this->agente())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('kpi-tickets-activos');
    }

    public function test_conductor_puede_ver_el_dashboard_sin_kpis(): void
    {
        $this->actingAs($this->conductor())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('kpi-tickets-activos');
    }

    public function test_usuario_no_autenticado_es_redirigido_al_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    // ── Endpoint JSON /dashboard/kpis ─────────────────────────────────────────

    public function test_endpoint_kpis_requiere_permiso_kpi_ver(): void
    {
        $this->actingAs($this->agente())
            ->getJson(route('dashboard.kpis'))
            ->assertForbidden();
    }

    public function test_conductor_no_puede_acceder_al_endpoint_kpis(): void
    {
        $this->actingAs($this->conductor())
            ->getJson(route('dashboard.kpis'))
            ->assertForbidden();
    }

    public function test_endpoint_kpis_retorna_json_con_todas_las_claves(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJsonStructure([
                'tickets_activos',
                'recaudacion_hoy',
                'recaudacion_mes',
                'infracciones_pendientes',
                'plazas_ocupadas',
                'agentes_hoy',
            ]);
    }

    public function test_kpis_retornan_ceros_cuando_no_hay_datos(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJson([
                'tickets_activos'         => 0,
                'recaudacion_hoy'         => 0,
                'recaudacion_mes'         => 0,
                'infracciones_pendientes' => 0,
                'plazas_ocupadas'         => 0,
                'agentes_hoy'             => 0,
            ]);
    }

    // ── Precisión de KPIs ─────────────────────────────────────────────────────

    public function test_recaudacion_hoy_incluye_solo_transacciones_completadas_de_hoy(): void
    {
        $this->crearTransaccionCompletada(1.50);
        $this->crearTransaccionCompletada(2.00);

        // Pendiente — no debe sumar
        $ticket = Ticket::factory()->create(['estado' => EstadoTicket::Pendiente]);
        TransaccionPago::factory()->pendiente()->create([
            'concepto_type' => Ticket::class,
            'concepto_id'   => $ticket->id,
            'monto'         => 5.00,
        ]);

        $this->actingAs($this->admin())
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJsonPath('recaudacion_hoy', 3.50);
    }

    public function test_recaudacion_hoy_excluye_transacciones_de_dias_anteriores(): void
    {
        $this->crearTransaccionCompletada(1.00);
        $this->crearTransaccionCompletada(100.00, now()->subDay()->toDateTimeString());

        $this->actingAs($this->admin())
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJsonPath('recaudacion_hoy', 1.00);
    }

    public function test_tickets_activos_cuenta_activos_y_en_tolerancia(): void
    {
        Ticket::factory()->create(['estado' => EstadoTicket::Activo]);
        Ticket::factory()->create(['estado' => EstadoTicket::Activo]);
        Ticket::factory()->create(['estado' => EstadoTicket::EnTolerancia]);
        Ticket::factory()->create(['estado' => EstadoTicket::Expirado]);   // no cuenta
        Ticket::factory()->create(['estado' => EstadoTicket::Cancelado]);  // no cuenta

        $this->actingAs($this->admin())
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJsonPath('tickets_activos', 3);
    }

    public function test_infracciones_pendientes_excluye_pagadas_y_anuladas(): void
    {
        $agente = $this->crearAgente();
        $zona   = Zona::first();

        Infraccion::factory()->create(['estado' => EstadoInfraccion::Pendiente, 'zona_id' => $zona->id, 'agente_parqueo_id' => $agente->id]);
        Infraccion::factory()->create(['estado' => EstadoInfraccion::Pendiente, 'zona_id' => $zona->id, 'agente_parqueo_id' => $agente->id]);
        Infraccion::factory()->pagada()->create(['zona_id' => $zona->id, 'agente_parqueo_id' => $agente->id]);
        Infraccion::factory()->anulada()->create(['zona_id' => $zona->id, 'agente_parqueo_id' => $agente->id]);

        $this->actingAs($this->admin())
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJsonPath('infracciones_pendientes', 2);
    }

    public function test_plazas_ocupadas_cuenta_sesiones_sin_fin_real_at(): void
    {
        $ticket1 = Ticket::factory()->create(['estado' => EstadoTicket::Activo]);
        $ticket2 = Ticket::factory()->create(['estado' => EstadoTicket::Activo]);
        $ticket3 = Ticket::factory()->create(['estado' => EstadoTicket::Expirado]);

        SesionParqueo::create([
            'ticket_id'         => $ticket1->id,
            'agente_id'         => null,
            'plaza_id'          => null,
            'inicio_at'         => now()->subHour(),
            'fin_programado_at' => now()->addHour(),
            'fin_real_at'       => null,
        ]);

        SesionParqueo::create([
            'ticket_id'         => $ticket2->id,
            'agente_id'         => null,
            'plaza_id'          => null,
            'inicio_at'         => now()->subHour(),
            'fin_programado_at' => now()->addHour(),
            'fin_real_at'       => null,
        ]);

        // Sesión cerrada — no debe contar
        SesionParqueo::create([
            'ticket_id'         => $ticket3->id,
            'agente_id'         => null,
            'plaza_id'          => null,
            'inicio_at'         => now()->subHour(),
            'fin_programado_at' => now()->subMinutes(5),
            'fin_real_at'       => now()->subMinutes(3),
        ]);

        $this->actingAs($this->admin())
            ->getJson(route('dashboard.kpis'))
            ->assertOk()
            ->assertJsonPath('plazas_ocupadas', 2);
    }
}
