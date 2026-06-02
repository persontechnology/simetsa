<?php

// tests/Feature/Reportes/InfraccionesReporteTest.php

namespace Tests\Feature\Reportes;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoInmovilizacion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
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
 * Tests del reporte de infracciones (Fase 8.C).
 * Arts. 15, 17, 18, 28-30 — Ordenanza SIMETSA.
 */
class InfraccionesReporteTest extends TestCase
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

    private function crearInfraccion(array $attrs = []): Infraccion
    {
        $agente = $this->crearAgente();
        $zona   = Zona::first();

        return Infraccion::factory()->create(array_merge([
            'zona_id'           => $zona->id,
            'agente_parqueo_id' => $agente->id,
        ], $attrs));
    }

    // ── Acceso por rol ────────────────────────────────────────────────────────

    public function test_super_admin_puede_ver_reporte_infracciones(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index'))
            ->assertOk()
            ->assertViewIs('reportes.infracciones.index');
    }

    public function test_comisario_puede_ver_reporte_infracciones(): void
    {
        $this->actingAs($this->comisario())
            ->get(route('reportes.infracciones.index'))
            ->assertOk();
    }

    public function test_agente_no_puede_ver_reporte_infracciones(): void
    {
        $this->actingAs($this->agente())
            ->get(route('reportes.infracciones.index'))
            ->assertForbidden();
    }

    public function test_conductor_no_puede_ver_reporte_infracciones(): void
    {
        $this->actingAs($this->conductor())
            ->get(route('reportes.infracciones.index'))
            ->assertForbidden();
    }

    public function test_no_autenticado_redirige_al_login(): void
    {
        $this->get(route('reportes.infracciones.index'))
            ->assertRedirect(route('login'));
    }

    // ── Totales ───────────────────────────────────────────────────────────────

    public function test_totales_ceros_sin_datos(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index'))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['cantidad'] === 0 &&
                $t['total_multas'] === 0.0 &&
                $t['total_cobrado'] === 0.0 &&
                $t['pendiente_cobro'] === 0.0 &&
                $t['inmovilizaciones_activas'] === 0
            );
    }

    public function test_totales_separan_cobrado_de_pendiente(): void
    {
        $this->crearInfraccion(['estado' => EstadoInfraccion::Pendiente, 'monto_multa' => 11.00]);
        $this->crearInfraccion(['estado' => EstadoInfraccion::Pendiente, 'monto_multa' => 11.00]);
        $this->crearInfraccion(['estado' => EstadoInfraccion::Pagada,    'monto_multa' => 22.00]);
        $this->crearInfraccion(['estado' => EstadoInfraccion::Anulada,   'monto_multa' => 11.00]);

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index'))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['cantidad'] === 4 &&
                $t['total_multas'] === 55.00 &&
                $t['total_cobrado'] === 22.00 &&
                $t['pendiente_cobro'] === 22.00
            );
    }

    public function test_totales_cuentan_inmovilizaciones_activas(): void
    {
        $inf1 = $this->crearInfraccion();
        $inf2 = $this->crearInfraccion();
        $this->crearInfraccion(); // sin inmovilización

        Inmovilizacion::create([
            'infraccion_id'     => $inf1->id,
            'agente_parqueo_id' => $inf1->agente_parqueo_id,
            'estado'            => EstadoInmovilizacion::Activa,
            'inmovilizada_en'   => now(),
        ]);
        // liberada — no cuenta como activa
        Inmovilizacion::create([
            'infraccion_id'     => $inf2->id,
            'agente_parqueo_id' => $inf2->agente_parqueo_id,
            'estado'            => EstadoInmovilizacion::Liberada,
            'inmovilizada_en'   => now()->subHour(),
            'liberada_en'       => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index'))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) =>
                $t['inmovilizaciones_activas'] === 1 &&
                $t['inmovilizaciones_total'] === 2
            );
    }

    // ── Filtros ───────────────────────────────────────────────────────────────

    public function test_filtro_estado_pendiente(): void
    {
        $this->crearInfraccion(['estado' => EstadoInfraccion::Pendiente]);
        $this->crearInfraccion(['estado' => EstadoInfraccion::Pagada]);
        $this->crearInfraccion(['estado' => EstadoInfraccion::Anulada]);

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index', ['estado' => 'pendiente']))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 1);
    }

    public function test_filtro_tipo_infraccion(): void
    {
        $this->crearInfraccion(['tipo_infraccion' => TipoInfraccion::TiempoExcedido]);
        $this->crearInfraccion(['tipo_infraccion' => TipoInfraccion::FueraDeArea]);
        $this->crearInfraccion(['tipo_infraccion' => TipoInfraccion::FueraDeArea]);

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index', ['tipo_infraccion' => TipoInfraccion::FueraDeArea->value]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 2);
    }

    public function test_filtro_fecha_desde(): void
    {
        $this->crearInfraccion(['created_at' => now()->subDays(10)]);
        $this->crearInfraccion(['created_at' => now()]);

        $desde = now()->subDays(1)->toDateString();

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index', ['fecha_desde' => $desde]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 1);
    }

    public function test_filtro_zona(): void
    {
        $zona1 = Zona::first();
        // Crear segunda zona de prueba directamente
        $zona2 = Zona::create([
            'codigo'     => 'norte',
            'nombre'     => 'Norte Test',
            'activa'     => true,
            'centro_lat' => -1.04,
            'centro_lng' => -78.59,
            'zoom'       => 15,
        ]);

        $this->crearInfraccion(['zona_id' => $zona1->id]);
        $this->crearInfraccion(['zona_id' => $zona1->id]);
        $this->crearInfraccion(['zona_id' => $zona2->id]);

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index', ['zona_id' => $zona1->id]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 2);
    }

    public function test_filtro_agente(): void
    {
        $agente = $this->crearAgente();
        $zona   = Zona::first();

        // Crear segundo agente con todos los campos requeridos
        $otroUser   = User::factory()->create();
        $otroAgente = AgenteParqueo::create([
            'user_id'                  => $otroUser->id,
            'codigo'                   => 'AG-0002',
            'numero_credencial'        => 'C-0002',
            'carta_compromiso_firmada' => true,
            'fecha_autorizacion'       => now()->toDateString(),
            'estado'                   => AgenteParqueo::ESTADO_ACTIVO,
        ]);

        Infraccion::factory()->create(['zona_id' => $zona->id, 'agente_parqueo_id' => $agente->id]);
        Infraccion::factory()->create(['zona_id' => $zona->id, 'agente_parqueo_id' => $agente->id]);
        Infraccion::factory()->create(['zona_id' => $zona->id, 'agente_parqueo_id' => $otroAgente->id]);

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.index', ['agente_parqueo_id' => $agente->id]))
            ->assertOk()
            ->assertViewHas('totales', fn ($t) => $t['cantidad'] === 2);
    }

    // ── Exportación ───────────────────────────────────────────────────────────

    public function test_exportacion_excel_descarga_archivo(): void
    {
        $this->crearInfraccion();

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_exportacion_pdf_devuelve_vista_blade(): void
    {
        $this->crearInfraccion();

        $this->actingAs($this->admin())
            ->get(route('reportes.infracciones.pdf'))
            ->assertOk()
            ->assertViewIs('reportes.infracciones.pdf');
    }

    public function test_agente_no_puede_exportar(): void
    {
        $this->actingAs($this->agente())
            ->get(route('reportes.infracciones.excel'))
            ->assertForbidden();
    }
}
