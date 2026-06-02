<?php

/**
 * app/Http/Controllers/Reportes/DashboardController.php
 *
 * Dashboard del SIMETSA. Accesible a todos los usuarios autenticados.
 * Las secciones de KPIs y gráficos se muestran solo con permiso kpi.ver.
 * El endpoint JSON /dashboard/kpis requiere kpi.ver (para el polling AJAX). (Fase 8.A)
 */

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Controlador del dashboard principal de reportes y KPIs.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly ReporteService $servicio)
    {
        $this->middleware('permission:kpi.ver')->only(['kpis']);
    }

    /**
     * Vista principal del dashboard. Los datos de KPIs solo se calculan
     * cuando el usuario tiene el permiso kpi.ver.
     */
    public function index(): View
    {
        $tieneKpi = Gate::allows('kpi.ver');
        $hoy      = Carbon::today();

        return view('reportes.dashboard', [
            'tieneKpi'       => $tieneKpi,
            'kpis'           => $tieneKpi ? $this->servicio->kpis() : [],
            'recaudacionDia' => $tieneKpi ? $this->servicio->recaudacionPorDia(30) : ['labels' => [], 'data' => []],
            'ticketsPorZona' => $tieneKpi ? $this->servicio->ticketsPorZona($hoy) : ['labels' => [], 'data' => []],
            'metodoPago'     => $tieneKpi ? $this->servicio->distribucionMetodoPago($hoy) : ['labels' => [], 'data' => []],
        ]);
    }

    /**
     * Endpoint JSON de KPIs para el polling AJAX del dashboard.
     * Requiere permiso kpi.ver.
     * JSON_PRESERVE_ZERO_FRACTION asegura que los montos lleguen como float (ej. 1.0, no 1).
     */
    public function kpis(): JsonResponse
    {
        return response()->json($this->servicio->kpis(), 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
