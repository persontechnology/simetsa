<?php

/**
 * app/Http/Controllers/Reportes/RecaudacionController.php
 *
 * Reporte de recaudación: listado filtrable + exportación Excel y PDF. (Fase 8.B)
 * Acceso: permiso reportes.ver (super_admin, comisario, director_seguridad).
 * Exportación: permiso reportes.exportar.
 */

namespace App\Http\Controllers\Reportes;

use App\Enums\ProveedorPago;
use App\Exports\RecaudacionExport;
use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Services\ReporteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador del reporte de recaudación.
 */
class RecaudacionController extends Controller
{
    public function __construct(private readonly ReporteService $servicio)
    {
        $this->middleware('permission:reportes.ver')->only(['index']);
        $this->middleware('permission:reportes.exportar')->only(['excel', 'pdf']);
    }

    /**
     * Listado paginado de transacciones con filtros aplicados.
     */
    public function index(Request $request): View
    {
        $filtros = $this->filtrosValidados($request);

        return view('reportes.recaudacion.index', [
            'transacciones' => $this->servicio->recaudacion($filtros),
            'totales'       => $this->servicio->recaudacionTotales($filtros),
            'zonas'         => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'proveedores'   => ProveedorPago::cases(),
        ]);
    }

    /**
     * Descarga del reporte en formato Excel (.xlsx).
     */
    public function excel(Request $request): BinaryFileResponse
    {
        $filtros = $this->filtrosValidados($request);
        $filas   = $this->servicio->recaudacionParaExport($filtros);
        $export  = new RecaudacionExport($filas);

        $nombre = 'recaudacion_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download($export, $nombre);
    }

    /**
     * Vista imprimible del reporte (PDF via Ctrl+P en el browser).
     */
    public function pdf(Request $request): View
    {
        $filtros = $this->filtrosValidados($request);
        $filas   = $this->servicio->recaudacionParaExport($filtros);
        $totales = $this->servicio->recaudacionTotales($filtros);

        return view('reportes.recaudacion.pdf', [
            'transacciones' => $filas,
            'totales'       => $totales,
        ]);
    }

    /**
     * Extrae y normaliza los filtros del request.
     *
     * @return array{fecha_desde: ?string, fecha_hasta: ?string, proveedor: ?string, tipo: ?string, zona_id: ?int}
     */
    private function filtrosValidados(Request $request): array
    {
        return [
            'fecha_desde' => $request->input('fecha_desde') ?: null,
            'fecha_hasta' => $request->input('fecha_hasta') ?: null,
            'proveedor'   => $request->input('proveedor')   ?: null,
            'tipo'        => in_array($request->input('tipo'), ['ticket', 'infraccion'], true)
                ? $request->input('tipo') : null,
            'zona_id'     => $request->filled('zona_id') ? (int) $request->input('zona_id') : null,
        ];
    }
}
