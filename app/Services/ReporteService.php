<?php

/**
 * app/Services/ReporteService.php
 *
 * Servicio de reportes y KPIs del SIMETSA.
 * Centraliza las consultas de agregación para el dashboard y los reportes
 * de recaudación, infracciones y ocupación (Fase 8).
 */

namespace App\Services;

use App\Enums\EstadoInfraccion;
use App\Enums\EstadoInmovilizacion;
use App\Enums\EstadoTicket;
use App\Enums\EstadoTransaccion;
use App\Models\Infraccion;
use App\Models\Inmovilizacion;
use App\Models\SesionParqueo;
use App\Models\Ticket;
use App\Models\TransaccionPago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cálculos de reportes y KPIs. Framework-agnóstico: no usa Request ni Response.
 */
class ReporteService
{
    private const TTL_KPIS    = 300;   // 5 minutos
    private const TTL_GRAFICOS = 300;  // 5 minutos

    /**
     * Retorna todos los KPIs de las tarjetas del dashboard.
     *
     * @return array{
     *   tickets_activos: int,
     *   recaudacion_hoy: float,
     *   recaudacion_mes: float,
     *   infracciones_pendientes: int,
     *   plazas_ocupadas: int,
     *   agentes_hoy: int,
     * }
     */
    public function kpis(): array
    {
        return Cache::remember('dashboard.kpis', self::TTL_KPIS, function () {
            $hoy  = Carbon::today();
            $mes  = Carbon::now()->startOfMonth();

            return [
                'tickets_activos' => Ticket::whereIn('estado', [
                    EstadoTicket::Activo->value,
                    EstadoTicket::EnTolerancia->value,
                ])->count(),

                'recaudacion_hoy' => (float) TransaccionPago::where('estado', EstadoTransaccion::Completada)
                    ->whereDate('created_at', $hoy)
                    ->sum('monto'),

                'recaudacion_mes' => (float) TransaccionPago::where('estado', EstadoTransaccion::Completada)
                    ->where('created_at', '>=', $mes)
                    ->sum('monto'),

                'infracciones_pendientes' => Infraccion::where('estado', EstadoInfraccion::Pendiente)->count(),

                'plazas_ocupadas' => SesionParqueo::whereNull('fin_real_at')->count(),

                'agentes_hoy' => SesionParqueo::whereDate('inicio_at', $hoy)
                    ->whereNotNull('agente_id')
                    ->distinct('agente_id')
                    ->count('agente_id'),
            ];
        });
    }

    /**
     * Recaudación total (TransaccionPago completadas) agrupada por día para los últimos N días.
     *
     * @return array{ labels: string[], data: float[] }
     */
    public function recaudacionPorDia(int $dias = 30): array
    {
        return Cache::remember("dashboard.recaudacion_dia.{$dias}", self::TTL_GRAFICOS, function () use ($dias) {
            $desde = Carbon::today()->subDays($dias - 1);

            $rows = TransaccionPago::where('estado', EstadoTransaccion::Completada)
                ->where('created_at', '>=', $desde)
                ->selectRaw('DATE(created_at) as fecha, SUM(monto) as total')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('fecha')
                ->pluck('total', 'fecha');

            // Completar días sin datos con 0
            $labels = [];
            $data   = [];
            for ($i = $dias - 1; $i >= 0; $i--) {
                $dia      = Carbon::today()->subDays($i)->toDateString();
                $labels[] = Carbon::parse($dia)->isoFormat('D MMM');
                $data[]   = round((float) ($rows[$dia] ?? 0), 2);
            }

            return ['labels' => $labels, 'data' => $data];
        });
    }

    /**
     * Cantidad de tickets emitidos por zona para una fecha dada.
     *
     * @return array{ labels: string[], data: int[] }
     */
    public function ticketsPorZona(Carbon $fecha): array
    {
        $key = "dashboard.tickets_zona.{$fecha->toDateString()}";

        return Cache::remember($key, self::TTL_GRAFICOS, function () use ($fecha) {
            $rows = Ticket::whereDate('comprado_en', $fecha)
                ->whereNotIn('tickets.estado', [EstadoTicket::Anulado->value, EstadoTicket::Cancelado->value])
                ->join('sesiones_parqueo', 'sesiones_parqueo.ticket_id', '=', 'tickets.id')
                ->join('plazas', 'plazas.id', '=', 'sesiones_parqueo.plaza_id')
                ->join('zonas', 'zonas.id', '=', 'plazas.zona_id')
                ->selectRaw('zonas.nombre as zona, COUNT(tickets.id) as total')
                ->groupBy('zonas.nombre')
                ->orderByDesc('total')
                ->pluck('total', 'zona');

            return [
                'labels' => $rows->keys()->all(),
                'data'   => $rows->values()->all(),
            ];
        });
    }

    /**
     * Distribución de recaudación por método de pago para una fecha dada.
     *
     * @return array{ labels: string[], data: float[] }
     */
    public function distribucionMetodoPago(Carbon $fecha): array
    {
        $key = "dashboard.metodo_pago.{$fecha->toDateString()}";

        return Cache::remember($key, self::TTL_GRAFICOS, function () use ($fecha) {
            $rows = TransaccionPago::where('estado', EstadoTransaccion::Completada)
                ->whereDate('created_at', $fecha)
                ->selectRaw('proveedor, SUM(monto) as total')
                ->groupBy('proveedor')
                ->orderByDesc('total')
                ->pluck('total', 'proveedor');

            $etiquetas = [
                'simulado' => 'Efectivo/Sim.',
                'deuna'    => 'Deuna',
            ];

            $labels = $rows->keys()->map(fn ($k) => $etiquetas[$k] ?? ucfirst($k))->all();
            $data   = $rows->values()->map(fn ($v) => round((float) $v, 2))->all();

            return ['labels' => $labels, 'data' => $data];
        });
    }

    // =========================================================================
    // Reporte de Recaudación (Fase 8.B)
    // =========================================================================

    /**
     * Construye la base de la query de recaudación aplicando los filtros.
     *
     * Filtros aceptados:
     *   fecha_desde   string YYYY-MM-DD
     *   fecha_hasta   string YYYY-MM-DD
     *   proveedor     string (valor de ProveedorPago)
     *   tipo          'ticket'|'infraccion'|null (ambos)
     *   zona_id       int|null
     *
     * @param array<string, mixed> $filtros
     */
    private function baseRecaudacion(array $filtros): Builder
    {
        return TransaccionPago::with(['concepto'])
            ->where('estado', EstadoTransaccion::Completada)
            ->when($filtros['fecha_desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtros['fecha_hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filtros['proveedor'] ?? null, fn ($q, $v) => $q->where('proveedor', $v))
            ->when($filtros['tipo'] ?? null, function ($q, $v) {
                $map = ['ticket' => Ticket::class, 'infraccion' => Infraccion::class];
                if (isset($map[$v])) {
                    $q->where('concepto_type', $map[$v]);
                }
            })
            ->when($filtros['zona_id'] ?? null, fn ($q, $v) =>
                $q->whereHasMorph('concepto', [Ticket::class, Infraccion::class], fn ($m) => $m->where('zona_id', $v))
            );
    }

    /**
     * Lista paginada de transacciones completadas con filtros aplicados (para la vista index).
     *
     * @param  array<string, mixed> $filtros
     * @param  int                  $perPage
     */
    public function recaudacion(array $filtros, int $perPage = 50): LengthAwarePaginator
    {
        return $this->baseRecaudacion($filtros)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Totales de recaudación agrupados para las tarjetas de resumen.
     *
     * @param  array<string, mixed> $filtros
     * @return array{total: float, total_tickets: float, total_infracciones: float, cantidad: int}
     */
    public function recaudacionTotales(array $filtros): array
    {
        $base = $this->baseRecaudacion($filtros);

        $total         = (float) (clone $base)->sum('monto');
        $totalTickets  = (float) (clone $base)->where('concepto_type', Ticket::class)->sum('monto');
        $totalInfracc  = (float) (clone $base)->where('concepto_type', Infraccion::class)->sum('monto');
        $cantidad      = (clone $base)->count();

        return [
            'total'              => round($total, 2),
            'total_tickets'      => round($totalTickets, 2),
            'total_infracciones' => round($totalInfracc, 2),
            'cantidad'           => $cantidad,
        ];
    }

    /**
     * Colección completa sin paginado — usada para exportación Excel y PDF.
     *
     * @param  array<string, mixed> $filtros
     */
    public function recaudacionParaExport(array $filtros): Collection
    {
        return $this->baseRecaudacion($filtros)
            ->orderByDesc('created_at')
            ->get();
    }

    // =========================================================================
    // Reporte de Infracciones (Fase 8.C)
    // =========================================================================

    /**
     * Base de la query de infracciones con filtros aplicados.
     *
     * Filtros aceptados:
     *   fecha_desde        string YYYY-MM-DD
     *   fecha_hasta        string YYYY-MM-DD
     *   estado             string (valor de EstadoInfraccion)
     *   tipo_infraccion    string (valor de TipoInfraccion)
     *   zona_id            int|null
     *   agente_parqueo_id  int|null
     *
     * @param  array<string, mixed> $filtros
     */
    private function baseInfracciones(array $filtros): Builder
    {
        return Infraccion::with(['zona', 'agente', 'inmovilizacion'])
            ->when($filtros['fecha_desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtros['fecha_hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filtros['estado'] ?? null, fn ($q, $v) => $q->where('estado', $v))
            ->when($filtros['tipo_infraccion'] ?? null, fn ($q, $v) => $q->where('tipo_infraccion', $v))
            ->when($filtros['zona_id'] ?? null, fn ($q, $v) => $q->where('zona_id', $v))
            ->when($filtros['agente_parqueo_id'] ?? null, fn ($q, $v) => $q->where('agente_parqueo_id', $v));
    }

    /**
     * Lista paginada de infracciones con filtros (para la vista index).
     *
     * @param  array<string, mixed> $filtros
     */
    public function infracciones(array $filtros, int $perPage = 50): LengthAwarePaginator
    {
        return $this->baseInfracciones($filtros)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Totales del reporte de infracciones.
     *
     * @param  array<string, mixed> $filtros
     * @return array{
     *   total_multas: float, total_cobrado: float, pendiente_cobro: float,
     *   cantidad: int, inmovilizaciones_activas: int, inmovilizaciones_total: int
     * }
     */
    public function infraccionesTotales(array $filtros): array
    {
        $base = $this->baseInfracciones($filtros);

        $ids = (clone $base)->pluck('id');

        return [
            'total_multas'            => round((float) (clone $base)->sum('monto_multa'), 2),
            'total_cobrado'           => round((float) (clone $base)->where('estado', EstadoInfraccion::Pagada)->sum('monto_multa'), 2),
            'pendiente_cobro'         => round((float) (clone $base)->where('estado', EstadoInfraccion::Pendiente)->sum('monto_multa'), 2),
            'cantidad'                => (clone $base)->count(),
            'inmovilizaciones_activas'=> Inmovilizacion::whereIn('infraccion_id', $ids)
                ->where('estado', EstadoInmovilizacion::Activa)->count(),
            'inmovilizaciones_total'  => Inmovilizacion::whereIn('infraccion_id', $ids)->count(),
        ];
    }

    /**
     * Colección completa sin paginado para exportación Excel y PDF.
     *
     * @param  array<string, mixed> $filtros
     */
    public function infraccionesParaExport(array $filtros): Collection
    {
        return $this->baseInfracciones($filtros)
            ->orderByDesc('created_at')
            ->get();
    }

    // =========================================================================
    // Reporte de Ocupación (Fase 8.D)
    // =========================================================================

    /**
     * Totales y series de datos para el reporte de ocupación.
     *
     * Filtros aceptados:
     *   fecha_desde  string YYYY-MM-DD  (default: hoy - 29 días)
     *   fecha_hasta  string YYYY-MM-DD  (default: hoy)
     *   zona_id      int|null
     *
     * @param  array<string, mixed> $filtros
     * @return array{
     *   totales: array{total_sesiones: int, duracion_promedio_min: float, hora_pico: int|null, sesiones_activas: int},
     *   por_dia:  array{labels: string[], data: int[]},
     *   por_hora: array{labels: string[], data: int[]},
     *   por_zona: array{labels: string[], data: int[]},
     * }
     */
    public function ocupacion(array $filtros): array
    {
        $desde   = Carbon::parse($filtros['fecha_desde'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $hasta   = Carbon::parse($filtros['fecha_hasta'] ?? now()->toDateString())->endOfDay();
        $zonaId  = $filtros['zona_id'] ?? null;

        $base = SesionParqueo::whereBetween('inicio_at', [$desde, $hasta]);

        if ($zonaId) {
            $base->join('plazas', 'plazas.id', '=', 'sesiones_parqueo.plaza_id')
                ->where('plazas.zona_id', $zonaId)
                ->select('sesiones_parqueo.*');
        }

        // ── Totales ──────────────────────────────────────────────────────────
        $total = (clone $base)->count();

        $duracionPromedio = (clone $base)
            ->whereNotNull('fin_real_at')
            ->selectRaw("AVG(EXTRACT(EPOCH FROM (fin_real_at - inicio_at)) / 60) as promedio")
            ->value('promedio');

        $sesionesActivas = SesionParqueo::whereNull('fin_real_at')->count();

        // ── Por hora ─────────────────────────────────────────────────────────
        $porHoraRaw = (clone $base)
            ->selectRaw("EXTRACT(HOUR FROM inicio_at)::int as hora, COUNT(*) as total")
            ->groupByRaw("EXTRACT(HOUR FROM inicio_at)::int")
            ->orderBy('hora')
            ->pluck('total', 'hora');

        $horaPico  = $porHoraRaw->isEmpty() ? null : (int) $porHoraRaw->sortDesc()->keys()->first();
        $horaLabels = array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23));
        $horaData   = array_map(fn ($h) => (int) ($porHoraRaw[$h] ?? 0), range(0, 23));

        // ── Por día ──────────────────────────────────────────────────────────
        $dias = (int) $desde->diffInDays($hasta) + 1;
        $porDiaRaw = (clone $base)
            ->selectRaw("DATE(inicio_at) as dia, COUNT(*) as total")
            ->groupByRaw("DATE(inicio_at)")
            ->orderBy('dia')
            ->pluck('total', 'dia');

        $diaLabels = [];
        $diaData   = [];
        for ($i = 0; $i < $dias; $i++) {
            $fecha       = $desde->copy()->addDays($i)->toDateString();
            $diaLabels[] = Carbon::parse($fecha)->isoFormat('D MMM');
            $diaData[]   = (int) ($porDiaRaw[$fecha] ?? 0);
        }

        // ── Por zona ─────────────────────────────────────────────────────────
        $porZonaRaw = SesionParqueo::whereBetween('sesiones_parqueo.inicio_at', [$desde, $hasta])
            ->join('plazas', 'plazas.id', '=', 'sesiones_parqueo.plaza_id')
            ->join('zonas', 'zonas.id', '=', 'plazas.zona_id')
            ->when($zonaId, fn ($q) => $q->where('zonas.id', $zonaId))
            ->selectRaw("zonas.nombre as zona, COUNT(*) as total")
            ->groupBy('zonas.nombre')
            ->orderByDesc('total')
            ->pluck('total', 'zona');

        return [
            'totales' => [
                'total_sesiones'       => $total,
                'duracion_promedio_min'=> round((float) $duracionPromedio, 1),
                'hora_pico'            => $horaPico,
                'sesiones_activas'     => $sesionesActivas,
            ],
            'por_dia'  => ['labels' => $diaLabels,  'data' => $diaData],
            'por_hora' => ['labels' => $horaLabels, 'data' => $horaData],
            'por_zona' => [
                'labels' => $porZonaRaw->keys()->all(),
                'data'   => $porZonaRaw->values()->map(fn ($v) => (int) $v)->all(),
            ],
        ];
    }
}
