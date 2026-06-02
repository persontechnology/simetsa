<?php

// tests/Feature/Reportes/OcupacionTest.php

namespace Tests\Feature\Reportes;

use App\Enums\EstadoTicket;
use App\Models\AgenteParqueo;
use App\Models\Plazas;
use App\Models\SesionParqueo;
use App\Models\Ticket;
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
 * Tests del reporte de ocupación (Fase 8.D).
 */
class OcupacionTest extends TestCase
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

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function admin(): User
    {
        return User::where('email', 'admin@simetsa.gob.ec')->first();
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

    private function crearSesion(array $attrs = []): SesionParqueo
    {
        $ticket = Ticket::factory()->create(['estado' => EstadoTicket::Expirado]);

        return SesionParqueo::create(array_merge([
            'ticket_id'         => $ticket->id,
            'agente_id'         => null,
            'plaza_id'          => null,
            'inicio_at'         => now()->subHour(),
            'fin_programado_at' => now(),
            'fin_real_at'       => now(),
        ], $attrs));
    }

    // ── Acceso por rol ────────────────────────────────────────────────────────

    public function test_super_admin_puede_ver_reporte_ocupacion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.ocupacion.index'))
            ->assertOk()
            ->assertViewIs('reportes.ocupacion.index');
    }

    public function test_director_puede_ver_reporte_ocupacion(): void
    {
        $this->actingAs($this->director())
            ->get(route('reportes.ocupacion.index'))
            ->assertOk();
    }

    public function test_agente_no_puede_ver_reporte_ocupacion(): void
    {
        $this->actingAs($this->agente())
            ->get(route('reportes.ocupacion.index'))
            ->assertForbidden();
    }

    public function test_conductor_no_puede_ver_reporte_ocupacion(): void
    {
        $this->actingAs($this->conductor())
            ->get(route('reportes.ocupacion.index'))
            ->assertForbidden();
    }

    public function test_no_autenticado_redirige_al_login(): void
    {
        $this->get(route('reportes.ocupacion.index'))
            ->assertRedirect(route('login'));
    }

    // ── Datos ─────────────────────────────────────────────────────────────────

    public function test_totales_ceros_sin_sesiones(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.ocupacion.index'))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['total_sesiones'] === 0 &&
                $t['hora_pico'] === null
            );
    }

    public function test_total_sesiones_cuenta_dentro_del_rango(): void
    {
        // Dentro del rango por defecto (últimos 30 días)
        $this->crearSesion(['inicio_at' => now()->subDays(5), 'fin_programado_at' => now()->subDays(5)->addHour(), 'fin_real_at' => now()->subDays(5)->addHour()]);
        $this->crearSesion(['inicio_at' => now()->subDays(1), 'fin_programado_at' => now()->subDays(1)->addHour(), 'fin_real_at' => now()->subDays(1)->addHour()]);
        // Fuera del rango
        $this->crearSesion(['inicio_at' => now()->subDays(60), 'fin_programado_at' => now()->subDays(60)->addHour(), 'fin_real_at' => now()->subDays(60)->addHour()]);

        $this->actingAs($this->admin())
            ->get(route('reportes.ocupacion.index'))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['total_sesiones'] === 2);
    }

    public function test_filtro_fecha_acota_el_rango(): void
    {
        $this->crearSesion(['inicio_at' => now()->subDays(15), 'fin_programado_at' => now()->subDays(15)->addHour(), 'fin_real_at' => now()->subDays(15)->addHour()]);
        $this->crearSesion(['inicio_at' => now()->subDays(3),  'fin_programado_at' => now()->subDays(3)->addHour(),  'fin_real_at' => now()->subDays(3)->addHour()]);

        $desde = now()->subDays(7)->toDateString();
        $hasta = now()->toDateString();

        $this->actingAs($this->admin())
            ->get(route('reportes.ocupacion.index', ['fecha_desde' => $desde, 'fecha_hasta' => $hasta]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['total_sesiones'] === 1);
    }

    public function test_vista_recibe_arrays_para_graficos(): void
    {
        $this->crearSesion();

        $this->actingAs($this->admin())
            ->get(route('reportes.ocupacion.index'))
            ->assertOk()
            ->assertViewHas('porDia',  fn ($d) => isset($d['labels'], $d['data']) && count($d['labels']) === 30)
            ->assertViewHas('porHora', fn ($d) => isset($d['labels'], $d['data']) && count($d['labels']) === 24)
            ->assertViewHas('porZona', fn ($d) => isset($d['labels'], $d['data']));
    }
}
