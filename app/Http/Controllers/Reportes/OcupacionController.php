<?php

/**
 * app/Http/Controllers/Reportes/OcupacionController.php
 *
 * Reporte de ocupación de plazas por hora/día/zona. (Fase 8.D)
 * Acceso: permiso reportes.ver (super_admin, comisario, director_seguridad).
 */

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Services\ReporteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controlador del reporte de ocupación.
 */
class OcupacionController extends Controller
{
    public function __construct(private readonly ReporteService $servicio)
    {
        $this->middleware('permission:reportes.ver');
    }

    /**
     * Vista principal con totales y gráficos de ocupación.
     */
    public function index(Request $request): View
    {
        $filtros = [
            'fecha_desde' => $request->input('fecha_desde') ?: null,
            'fecha_hasta' => $request->input('fecha_hasta') ?: null,
            'zona_id'     => $request->filled('zona_id') ? (int) $request->input('zona_id') : null,
        ];

        $datos = $this->servicio->ocupacion($filtros);

        return view('reportes.ocupacion.index', [
            'totales'    => $datos['totales'],
            'porDia'     => $datos['por_dia'],
            'porHora'    => $datos['por_hora'],
            'porZona'    => $datos['por_zona'],
            'zonas'      => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'fechaDesde' => $filtros['fecha_desde'],
            'fechaHasta' => $filtros['fecha_hasta'],
        ]);
    }
}
