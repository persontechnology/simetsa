<?php

// tests/Feature/InfraccionServiceTest.php

namespace Tests\Feature;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoInmovilizacion;
use App\Enums\TipoInfraccion;
use App\Models\AgenteParqueo;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use App\Models\Parametro;
use App\Models\User;
use App\Models\Zona;
use App\Services\InfraccionService;
use Database\Seeders\ParametroSeeder;
use Database\Seeders\RolPermisoSeeder;
use Database\Seeders\UsuarioPruebaSeeder;
use Database\Seeders\ZonaSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del InfraccionService (Fase 7.B).
 *
 * Cubre todas las reglas de cálculo de multas y el ciclo de vida de infracciones.
 * Arts. 15, 17, 18, 28, 29, 30 — Ordenanza SIMETSA.
 */
class InfraccionServiceTest extends TestCase
{
    use RefreshDatabase;

    private InfraccionService $service;
    private const SBU = 460.00;   // SBU sembrado por ParametroSeeder

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolPermisoSeeder::class,
            UsuarioPruebaSeeder::class,
            ZonaSeeder::class,
            ParametroSeeder::class,
        ]);
        $this->service = app(InfraccionService::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function agente(): AgenteParqueo
    {
        $user = User::where('email', 'agente@simetsa.gob.ec')->first();

        return AgenteParqueo::firstOrCreate(
            ['user_id' => $user->id],
            [
                'codigo'                  => 'AG-TEST1',
                'numero_credencial'       => 'C-TEST1',
                'carta_compromiso_firmada'=> true,
                'fecha_autorizacion'      => now()->toDateString(),
                'estado'                  => AgenteParqueo::ESTADO_ACTIVO,
            ]
        );
    }

    private function zona(): Zona
    {
        return Zona::where('codigo', 'centro')->first();
    }

    private function comisario(): User
    {
        return User::where('email', 'comisario@simetsa.gob.ec')->first();
    }

    private function datosBase(array $extra = []): array
    {
        return array_merge([
            'placa'           => 'ABC1234',
            'tipo_infraccion' => TipoInfraccion::SinTicketVisible,
            'zona_id'         => $this->zona()->id,
        ], $extra);
    }

    // ── calcularMulta ─────────────────────────────────────────────────────────

    /** Art. 28: tiempo excedido 6–60 min → 2% SBU. */
    public function test_multa_tiempo_excedido_6_a_60_min(): void
    {
        $esperado = round(self::SBU * 0.02, 2);   // $9.20

        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 6, self::SBU));
        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 30, self::SBU));
        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 60, self::SBU));
    }

    /** Art. 28: tiempo excedido 61–120 min → 4% SBU. */
    public function test_multa_tiempo_excedido_61_a_120_min(): void
    {
        $esperado = round(self::SBU * 0.04, 2);   // $18.40

        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 61, self::SBU));
        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 120, self::SBU));
    }

    /** Art. 28: tiempo excedido > 120 min → 8% SBU. */
    public function test_multa_tiempo_excedido_mas_de_120_min(): void
    {
        $esperado = round(self::SBU * 0.08, 2);   // $36.80

        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 121, self::SBU));
        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 200, self::SBU));
    }

    /** Art. 28 + Art. 13: tiempo excedido < 6 min lanza excepción (dentro de tolerancia). */
    public function test_multa_tiempo_excedido_menos_de_6_min_lanza_excepcion(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Art\. 28/');

        $this->service->calcularMulta(TipoInfraccion::TiempoExcedido, 5, self::SBU);
    }

    /** Art. 29: sin ticket visible (Art. 17.b) → 2% SBU. */
    public function test_multa_sin_ticket_visible_es_2_pct_sbu(): void
    {
        $esperado = round(self::SBU * 0.02, 2);

        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::SinTicketVisible, 0, self::SBU));
    }

    /** Art. 29: sin adquirir ticket (Art. 17.c) → 2% SBU. */
    public function test_multa_sin_adquirir_ticket_es_2_pct_sbu(): void
    {
        $esperado = round(self::SBU * 0.02, 2);

        $this->assertEquals($esperado, $this->service->calcularMulta(TipoInfraccion::SinAdquirirTicket, 0, self::SBU));
    }

    /** Art. 29: intercambio de tickets (Art. 17.f) → 2% SBU. */
    public function test_multa_intercambio_tickets_es_2_pct_sbu(): void
    {
        $this->assertEquals(
            round(self::SBU * 0.02, 2),
            $this->service->calcularMulta(TipoInfraccion::IntercambioTickets, 0, self::SBU)
        );
    }

    /** Art. 29: ticket alterado (Art. 17.d) → 20% SBU. */
    public function test_multa_ticket_alterado_es_20_pct_sbu(): void
    {
        $this->assertEquals(
            round(self::SBU * 0.20, 2),
            $this->service->calcularMulta(TipoInfraccion::TicketAlterado, 0, self::SBU)
        );
    }

    /** Art. 29: retirar candado (Art. 17.e) → 20% SBU. */
    public function test_multa_retirar_candado_es_20_pct_sbu(): void
    {
        $this->assertEquals(
            round(self::SBU * 0.20, 2),
            $this->service->calcularMulta(TipoInfraccion::RetirarCandado, 0, self::SBU)
        );
    }

    /** Art. 29: doble columna (Art. 18.a), calle prohibida (18.b), vehículo prohibido (18.c), fuera de área (18.d) → 20% SBU. */
    public function test_multas_art18_abcd_son_20_pct_sbu(): void
    {
        $esperado = round(self::SBU * 0.20, 2);
        $tipos    = [
            TipoInfraccion::DobleColumna,
            TipoInfraccion::CalleProhibidaBuses,
            TipoInfraccion::VehiculoProhibido,
            TipoInfraccion::FueraDeArea,
        ];

        foreach ($tipos as $tipo) {
            $this->assertEquals($esperado, $this->service->calcularMulta($tipo, 0, self::SBU), $tipo->value);
        }
    }

    /** Art. 30: agresión al agente (Art. 18.e) → 50% SBU. */
    public function test_multa_agresion_agente_es_50_pct_sbu(): void
    {
        $this->assertEquals(
            round(self::SBU * 0.50, 2),
            $this->service->calcularMulta(TipoInfraccion::AgresionAgente, 0, self::SBU)
        );
    }

    /** Art. 17.g: negar pago → 0 (sin cargo económico explícito). */
    public function test_multa_negar_pago_es_cero(): void
    {
        $this->assertEquals(0.0, $this->service->calcularMulta(TipoInfraccion::NegarPago, 0, self::SBU));
    }

    // ── registrar ─────────────────────────────────────────────────────────────

    /** Registrar infracción guarda SBU snapshot y monto correcto. */
    public function test_registrar_guarda_sbu_snapshot_y_monto(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar(
            $this->datosBase(['tipo_infraccion' => TipoInfraccion::SinTicketVisible]),
            $agente
        );

        $this->assertEquals(self::SBU, (float) $infraccion->sbu_vigente);
        $this->assertEquals(round(self::SBU * 0.02, 2), (float) $infraccion->monto_multa);
        $this->assertEquals(EstadoInfraccion::Pendiente, $infraccion->estado);
    }

    /** Registrar infracción por tiempo excedido guarda los minutos. */
    public function test_registrar_tiempo_excedido_guarda_minutos(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar(
            $this->datosBase([
                'tipo_infraccion'   => TipoInfraccion::TiempoExcedido,
                'minutos_excedidos' => 75,
            ]),
            $agente
        );

        $this->assertEquals(75, $infraccion->minutos_excedidos);
        $this->assertEquals(round(self::SBU * 0.04, 2), (float) $infraccion->monto_multa);
    }

    /** Registrar con tiempo excedido < 6 min lanza excepción (Art. 28). */
    public function test_registrar_tiempo_excedido_menos_6_min_lanza_excepcion(): void
    {
        $this->expectException(DomainException::class);

        $this->service->registrar(
            $this->datosBase([
                'tipo_infraccion'   => TipoInfraccion::TiempoExcedido,
                'minutos_excedidos' => 3,
            ]),
            $this->agente()
        );
    }

    /** No se puede registrar infracción con agente suspendido. */
    public function test_registrar_agente_suspendido_lanza_excepcion(): void
    {
        $agente          = $this->agente();
        $agente->estado  = AgenteParqueo::ESTADO_SUSPENDIDO;
        $agente->save();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/activo/');

        $this->service->registrar($this->datosBase(), $agente);
    }

    /** La placa se normaliza a mayúsculas al registrar. */
    public function test_registrar_normaliza_placa_a_mayusculas(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar(
            $this->datosBase(['placa' => 'abc-1234']),
            $agente
        );

        $this->assertEquals('ABC-1234', $infraccion->placa);
    }

    // ── inmovilizar ───────────────────────────────────────────────────────────

    /** Inmovilizar crea registro con estado activa (Art. 15). */
    public function test_inmovilizar_crea_registro_activo(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);

        $inmov = $this->service->inmovilizar($infraccion, $agente);

        $this->assertInstanceOf(Inmovilizacion::class, $inmov);
        $this->assertEquals(EstadoInmovilizacion::Activa, $inmov->estado);
        $this->assertNotNull($inmov->inmovilizada_en);
    }

    /** No se puede inmovilizar dos veces la misma infracción. */
    public function test_inmovilizar_dos_veces_lanza_excepcion(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);
        $this->service->inmovilizar($infraccion, $agente);
        $infraccion->refresh();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/candado/');

        $this->service->inmovilizar($infraccion, $agente);
    }

    /** No se puede inmovilizar una infracción ya pagada. */
    public function test_inmovilizar_infraccion_pagada_lanza_excepcion(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);
        $infraccion->update(['estado' => EstadoInfraccion::Pagada]);

        $this->expectException(DomainException::class);

        $this->service->inmovilizar($infraccion->fresh(), $agente);
    }

    // ── liberar ───────────────────────────────────────────────────────────────

    /** liberar() forzado con motivo actualiza estado a liberada. */
    public function test_liberar_con_motivo_actualiza_estado(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);
        $inmov      = $this->service->inmovilizar($infraccion, $agente);

        $resultado = $this->service->liberar($inmov, 'Error de registro administrativo');

        $this->assertEquals(EstadoInmovilizacion::Liberada, $resultado->estado);
        $this->assertNotNull($resultado->liberada_en);
    }

    /** liberar() sin motivo y sin pago lanza excepción (Art. 15). */
    public function test_liberar_sin_pago_ni_motivo_lanza_excepcion(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);
        $inmov      = $this->service->inmovilizar($infraccion, $agente);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Art\. 15/');

        $this->service->liberar($inmov);
    }

    /** liberar() ya liberada lanza excepción. */
    public function test_liberar_inmovilizacion_ya_liberada_lanza_excepcion(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);
        $inmov      = $this->service->inmovilizar($infraccion, $agente);
        $this->service->liberar($inmov, 'test');

        $this->expectException(DomainException::class);

        $this->service->liberar($inmov->fresh());
    }

    // ── anular ────────────────────────────────────────────────────────────────

    /** anular() marca la infracción como anulada y guarda trazabilidad. */
    public function test_anular_marca_infraccion_y_guarda_trazabilidad(): void
    {
        $agente     = $this->agente();
        $comisario  = $this->comisario();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);

        $resultado = $this->service->anular($infraccion, $comisario, 'Registrada por error');

        $this->assertEquals(EstadoInfraccion::Anulada, $resultado->estado);
        $this->assertEquals($comisario->id, $resultado->anulada_por);
        $this->assertNotNull($resultado->anulada_en);
        $this->assertEquals('Registrada por error', $resultado->motivo_anulacion);
    }

    /** anular() con inmovilización activa también la anula. */
    public function test_anular_infraccion_anula_inmovilizacion_activa(): void
    {
        $agente     = $this->agente();
        $comisario  = $this->comisario();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);
        $inmov      = $this->service->inmovilizar($infraccion, $agente);

        $this->service->anular($infraccion->fresh(), $comisario, 'Vehículo exonerado no registrado');

        $this->assertEquals(EstadoInmovilizacion::Anulada, $inmov->fresh()->estado);
    }

    /** No se puede anular una infracción ya pagada. */
    public function test_anular_infraccion_pagada_lanza_excepcion(): void
    {
        $agente     = $this->agente();
        $comisario  = $this->comisario();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);
        $infraccion->update(['estado' => EstadoInfraccion::Pagada]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Pagada/');

        $this->service->anular($infraccion->fresh(), $comisario, 'intento');
    }

    /** Motivo vacío lanza excepción. */
    public function test_anular_sin_motivo_lanza_excepcion(): void
    {
        $agente     = $this->agente();
        $infraccion = $this->service->registrar($this->datosBase(), $agente);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/motivo/');

        $this->service->anular($infraccion, $this->comisario(), '   ');
    }
}
