<?php

// tests/Feature/Reportes/RecaudacionTest.php

namespace Tests\Feature\Reportes;

use App\Enums\EstadoTicket;
use App\Enums\ProveedorPago;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
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
 * Tests del reporte de recaudación (Fase 8.B).
 * Verifica acceso, filtros, totales y exportación.
 */
class RecaudacionTest extends TestCase
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

    private function comisario(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function agente(): User
    {
        return User::where('email', 'agente@simetsa.gob.ec')->first();
    }

    private function conductor(): User
    {
        return User::where('email', 'conductor@simetsa.gob.ec')->first();
    }

    private function crearAgenteParqueo(): AgenteParqueo
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

    private function crearTxTicket(float $monto = 1.00, string $proveedor = 'none', ?string $fecha = null): TransaccionPago
    {
        $ticket = Ticket::factory()->create(['estado' => EstadoTicket::Activo]);

        return TransaccionPago::factory()->completada()->create([
            'concepto_type' => Ticket::class,
            'concepto_id'   => $ticket->id,
            'monto'         => $monto,
            'proveedor'     => $proveedor,
            'created_at'    => $fecha ? now()->parse($fecha) : now(),
        ]);
    }

    private function crearTxInfraccion(float $monto = 11.00, ?string $fecha = null): TransaccionPago
    {
        $agente     = $this->crearAgenteParqueo();
        $zona       = Zona::first();
        $infraccion = Infraccion::factory()->create([
            'zona_id'           => $zona->id,
            'agente_parqueo_id' => $agente->id,
        ]);

        return TransaccionPago::factory()->completada()->create([
            'concepto_type' => Infraccion::class,
            'concepto_id'   => $infraccion->id,
            'monto'         => $monto,
            'created_at'    => $fecha ? now()->parse($fecha) : now(),
        ]);
    }

    // ── Acceso por rol ────────────────────────────────────────────────────────

    public function test_super_admin_puede_ver_reporte_recaudacion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index'))
            ->assertOk()
            ->assertViewIs('reportes.recaudacion.index');
    }

    public function test_comisario_puede_ver_reporte_recaudacion(): void
    {
        $this->actingAs($this->comisario())
            ->get(route('reportes.recaudacion.index'))
            ->assertOk();
    }

    public function test_agente_no_puede_ver_reporte_recaudacion(): void
    {
        $this->actingAs($this->agente())
            ->get(route('reportes.recaudacion.index'))
            ->assertForbidden();
    }

    public function test_conductor_no_puede_ver_reporte_recaudacion(): void
    {
        $this->actingAs($this->conductor())
            ->get(route('reportes.recaudacion.index'))
            ->assertForbidden();
    }

    public function test_no_autenticado_redirige_al_login(): void
    {
        $this->get(route('reportes.recaudacion.index'))
            ->assertRedirect(route('login'));
    }

    // ── Totales ───────────────────────────────────────────────────────────────

    public function test_totales_incluyen_tickets_e_infracciones(): void
    {
        $this->crearTxTicket(2.50);
        $this->crearTxInfraccion(11.00);

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index'))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['total'] === 13.50 &&
                $t['total_tickets'] === 2.50 &&
                $t['total_infracciones'] === 11.00 &&
                $t['cantidad'] === 2
            );
    }

    public function test_totales_con_cero_transacciones(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index'))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['total'] === 0.0 &&
                $t['cantidad'] === 0
            );
    }

    // ── Filtros ───────────────────────────────────────────────────────────────

    public function test_filtro_tipo_ticket_excluye_infracciones(): void
    {
        $this->crearTxTicket(1.00);
        $this->crearTxInfraccion(11.00);

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index', ['tipo' => 'ticket']))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['cantidad'] === 1 && $t['total_tickets'] === 1.00
            );
    }

    public function test_filtro_tipo_infraccion_excluye_tickets(): void
    {
        $this->crearTxTicket(1.00);
        $this->crearTxInfraccion(11.00);

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index', ['tipo' => 'infraccion']))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['cantidad'] === 1 && $t['total_infracciones'] === 11.00
            );
    }

    public function test_filtro_fecha_desde_excluye_anteriores(): void
    {
        $this->crearTxTicket(5.00, 'none', now()->subDays(10)->toDateTimeString());
        $this->crearTxTicket(2.00, 'none', now()->toDateTimeString());

        $desde = now()->subDays(1)->toDateString();

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index', ['fecha_desde' => $desde]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 1 && $t['total'] === 2.00);
    }

    public function test_filtro_fecha_hasta_excluye_posteriores(): void
    {
        $this->crearTxTicket(1.00, 'none', now()->subDays(5)->toDateTimeString());
        $this->crearTxTicket(9.00, 'none', now()->toDateTimeString());

        $hasta = now()->subDays(3)->toDateString();

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index', ['fecha_hasta' => $hasta]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 1 && $t['total'] === 1.00);
    }

    public function test_filtro_proveedor_deuna(): void
    {
        $this->crearTxTicket(1.00, ProveedorPago::None->value);
        $this->crearTxTicket(2.00, ProveedorPago::Deuna->value);

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.index', ['proveedor' => ProveedorPago::Deuna->value]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 1 && $t['total'] === 2.00);
    }

    // ── Exportación ───────────────────────────────────────────────────────────

    public function test_exportacion_excel_descarga_archivo(): void
    {
        $this->crearTxTicket(1.00);

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_exportacion_pdf_devuelve_vista_blade(): void
    {
        $this->crearTxTicket(1.50);

        $this->actingAs($this->admin())
            ->get(route('reportes.recaudacion.pdf'))
            ->assertOk()
            ->assertViewIs('reportes.recaudacion.pdf');
    }

    public function test_agente_no_puede_exportar(): void
    {
        $this->actingAs($this->agente())
            ->get(route('reportes.recaudacion.excel'))
            ->assertForbidden();

        $this->actingAs($this->agente())
            ->get(route('reportes.recaudacion.pdf'))
            ->assertForbidden();
    }
}
