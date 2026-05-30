<?php

// tests/Feature/InfraccionModeloTest.php

namespace Tests\Feature;

use App\Contracts\Cobrable;
use App\Enums\EstadoInfraccion;
use App\Enums\EstadoInmovilizacion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la capa de datos de la Fase 7.A:
 * migraciones, modelos, enums y policies de Infraccion e Inmovilizacion.
 *
 * Arts. 15, 17, 18, 28, 29, 30 — Ordenanza SIMETSA.
 */
class InfraccionModeloTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function crearAgente(): AgenteParqueo
    {
        $user = User::where('email', 'agente@simetsa.gob.ec')->first();

        return AgenteParqueo::create([
            'codigo'                  => 'AG-TEST1',
            'user_id'                 => $user->id,
            'numero_credencial'       => 'C-TEST1',
            'carta_compromiso_firmada'=> true,
            'fecha_autorizacion'      => now()->toDateString(),
            'estado'                  => 'activo',
        ]);
    }

    private function zona(): Zona
    {
        return Zona::where('codigo', 'centro')->first();
    }

    private function datosInfraccion(AgenteParqueo $agente, Zona $zona, array $extra = []): array
    {
        return array_merge([
            'placa'             => 'ABC1234',
            'zona_id'           => $zona->id,
            'agente_parqueo_id' => $agente->id,
            'tipo_infraccion'   => TipoInfraccion::SinTicketVisible,
            'estado'            => EstadoInfraccion::Pendiente,
            'monto_multa'       => 11.00,
            'sbu_vigente'       => 550.00,
        ], $extra);
    }

    // ── Migración ─────────────────────────────────────────────────────────────

    /** La migración crea la tabla infracciones con las columnas críticas. */
    public function test_migra_tabla_infracciones(): void
    {
        $agente = $this->crearAgente();
        $zona   = $this->zona();

        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $this->assertDatabaseHas('infracciones', [
            'id'    => $infraccion->id,
            'placa' => 'ABC1234',
        ]);
    }

    /** La migración crea la tabla inmovilizaciones con FK a infracciones. */
    public function test_migra_tabla_inmovilizaciones(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $inmov = Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        $this->assertDatabaseHas('inmovilizaciones', [
            'id'           => $inmov->id,
            'infraccion_id'=> $infraccion->id,
        ]);
    }

    // ── Modelo Infraccion ─────────────────────────────────────────────────────

    /** La factory genera estado 'pendiente' por defecto. */
    public function test_factory_genera_estado_pendiente_por_defecto(): void
    {
        $agente = $this->crearAgente();
        $zona   = $this->zona();

        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $this->assertEquals(EstadoInfraccion::Pendiente, $infraccion->estado);
    }

    /** La placa se normaliza a mayúsculas al asignarla (consistencia con tickets). */
    public function test_placa_se_normaliza_a_mayusculas(): void
    {
        $agente = $this->crearAgente();
        $zona   = $this->zona();

        $infraccion = Infraccion::create(
            $this->datosInfraccion($agente, $zona, ['placa' => 'abc-1234'])
        );

        $this->assertEquals('ABC-1234', $infraccion->placa);
    }

    /** Infraccion implementa Cobrable para ser procesada por PagoManager (Fase 6). */
    public function test_infraccion_implementa_cobrable(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create(
            $this->datosInfraccion($agente, $zona, ['monto_multa' => 11.00])
        );

        $this->assertInstanceOf(Cobrable::class, $infraccion);
        $this->assertEquals(11.00, $infraccion->montoCobrable());
        $this->assertStringContainsString('ABC1234', $infraccion->descripcionCobro());
    }

    /** La relación inmovilizacion() es hasOne nullable (Art. 15: no toda infracción inmoviliza). */
    public function test_infraccion_sin_inmovilizacion_retorna_null(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $this->assertNull($infraccion->inmovilizacion);
    }

    /** La relación agente() resuelve a AgenteParqueo. */
    public function test_relacion_agente_resuelve(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $this->assertEquals($agente->id, $infraccion->agente->id);
    }

    /** Infraccion::acreditar() marca estado pagada y libera la inmovilización (Art. 15). */
    public function test_acreditar_pago_libera_inmovilizacion(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $inmov = Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        // Simulamos la llamada del webhook tras pago confirmado
        $transaccion = new \App\Models\TransaccionPago([
            'estado' => \App\Enums\EstadoTransaccion::Completada,
        ]);
        $infraccion->acreditar($transaccion);

        $this->assertEquals(EstadoInfraccion::Pagada, $infraccion->fresh()->estado);
        $this->assertEquals(EstadoInmovilizacion::Liberada, $inmov->fresh()->estado);
        $this->assertNotNull($inmov->fresh()->liberada_en);
    }

    /** acreditar() sobre infracción sin inmovilización no lanza excepción. */
    public function test_acreditar_sin_inmovilizacion_no_falla(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $transaccion = new \App\Models\TransaccionPago([
            'estado' => \App\Enums\EstadoTransaccion::Completada,
        ]);

        $infraccion->acreditar($transaccion);

        $this->assertEquals(EstadoInfraccion::Pagada, $infraccion->fresh()->estado);
    }

    // ── Enum TipoInfraccion ───────────────────────────────────────────────────

    /** TipoInfraccion tiene exactamente 12 casos (7 de Art. 17 + 5 de Art. 18). */
    public function test_tipo_infraccion_tiene_12_casos(): void
    {
        $this->assertCount(12, TipoInfraccion::cases());
    }

    /** TiempoExcedido retorna null en porcentajeSbu (tabla escalonada Art. 28). */
    public function test_tiempo_excedido_porcentaje_es_null(): void
    {
        $this->assertNull(TipoInfraccion::TiempoExcedido->porcentajeSbu());
    }

    /** AgresionAgente retorna 50% SBU (Art. 30). */
    public function test_agresion_agente_porcentaje_es_50(): void
    {
        $this->assertEquals(50.0, TipoInfraccion::AgresionAgente->porcentajeSbu());
    }

    /** NegarPago retorna 0 (sin cargo económico explícito en Art. 29). */
    public function test_negar_pago_porcentaje_es_cero(): void
    {
        $this->assertEquals(0.0, TipoInfraccion::NegarPago->porcentajeSbu());
    }

    /** requiereInmovilizacion() es true solo para los 3 tipos de Art. 15. */
    public function test_requiere_inmovilizacion_en_tipos_correctos(): void
    {
        $conInmov = [
            TipoInfraccion::TiempoExcedido,
            TipoInfraccion::SinTicketVisible,
            TipoInfraccion::SinAdquirirTicket,
        ];

        foreach (TipoInfraccion::cases() as $tipo) {
            $esperado = in_array($tipo, $conInmov, true);
            $this->assertEquals(
                $esperado,
                $tipo->requiereInmovilizacion(),
                "requiereInmovilizacion() incorrecto para {$tipo->value}"
            );
        }
    }

    // ── Inmovilizacion ────────────────────────────────────────────────────────

    /** Inmovilizacion 1:1 con Infraccion: la FK infraccion_id es unique. */
    public function test_inmovilizacion_es_unica_por_infraccion(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);
    }

    /** estaActiva() retorna true solo cuando estado = Activa. */
    public function test_inmovilizacion_esta_activa(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $inmov = Inmovilizacion::create([
            'infraccion_id'    => $infraccion->id,
            'agente_parqueo_id'=> $agente->id,
            'estado'           => EstadoInmovilizacion::Activa,
            'inmovilizada_en'  => now(),
        ]);

        $this->assertTrue($inmov->estaActiva());

        $inmov->update(['estado' => EstadoInmovilizacion::Liberada]);
        $this->assertFalse($inmov->fresh()->estaActiva());
    }

    // ── Policies ─────────────────────────────────────────────────────────────

    /** El agente solo puede ver infracciones que él registró. */
    public function test_policy_agente_ve_solo_sus_infracciones(): void
    {
        $agente  = $this->crearAgente();
        $zona    = $this->zona();

        // Crear un segundo agente
        $user2  = User::where('email', 'director.seguridad@simetsa.gob.ec')->first();
        $agente2 = AgenteParqueo::create([
            'codigo' => 'AG-TEST2', 'user_id' => $user2->id,
            'numero_credencial' => 'C-TEST2',
            'carta_compromiso_firmada' => true,
            'fecha_autorizacion' => now()->toDateString(),
            'estado' => 'activo',
        ]);

        $infracccionPropia  = Infraccion::create($this->datosInfraccion($agente, $zona));
        $infraccionAjena    = Infraccion::create($this->datosInfraccion($agente2, $zona));

        $userAgente = User::where('email', 'agente@simetsa.gob.ec')->first();

        $this->assertTrue($userAgente->can('view', $infracccionPropia));
        $this->assertFalse($userAgente->can('view', $infraccionAjena));
    }

    /** El comisario ve todas las infracciones (bypass before()). */
    public function test_policy_comisario_ve_todas_las_infracciones(): void
    {
        $agente     = $this->crearAgente();
        $zona       = $this->zona();
        $infraccion = Infraccion::create($this->datosInfraccion($agente, $zona));

        $comisario = User::where('email', 'comisario@simetsa.gob.ec')->first();

        $this->assertTrue($comisario->can('view', $infraccion));
        $this->assertTrue($comisario->can('viewAny', Infraccion::class));
    }
}
