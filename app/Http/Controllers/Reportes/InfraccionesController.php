<?php

/**
 * app/Http/Controllers/Reportes/InfraccionesController.php
 *
 * Reporte de infracciones: listado filtrable + exportación Excel y PDF. (Fase 8.C)
 * Arts. 15, 17, 18, 28-30 — Ordenanza SIMETSA.
 * Acceso: permiso reportes.ver (super_admin, comisario, director_seguridad).
 * Exportación: permiso reportes.exportar.
 */

namespace App\Http\Controllers\Reportes;

use App\Enums\EstadoInfraccion;
use App\Enums\TipoInfraccion;
use App\Exports\InfraccionesExport;
use App\Http\Controllers\Controller;
use App\Models\AgenteParqueo;
use App\Models\Zona;
use App\Services\ReporteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador del reporte de infracciones.
 */
class InfraccionesController extends Controller
{
    public function __construct(private readonly ReporteService $servicio)
    {
        $this->middleware('permission:reportes.ver')->only(['index']);
        $this->middleware('permission:reportes.exportar')->only(['excel', 'pdf']);
    }

    /**
     * Listado paginado de infracciones con filtros aplicados.
     */
    public function index(Request $request): View
    {
        $filtros = $this->filtrosValidados($request);

        return view('reportes.infracciones.index', [
            'infracciones' => $this->servicio->infracciones($filtros),
            'totales'      => $this->servicio->infraccionesTotales($filtros),
            'zonas'        => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'agentes'      => AgenteParqueo::with('user')->orderBy('codigo')->get(),
            'tiposInfr'    => TipoInfraccion::cases(),
            'estadosInfr'  => EstadoInfraccion::cases(),
        ]);
    }

    /**
     * Descarga del reporte en formato Excel (.xlsx).
     */
    public function excel(Request $request): BinaryFileResponse
    {
        $filtros = $this->filtrosValidados($request);
        $filas   = $this->servicio->infraccionesParaExport($filtros);
        $nombre  = 'infracciones_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InfraccionesExport($filas), $nombre);
    }

    /**
     * Vista imprimible del reporte (PDF via Ctrl+P).
     */
    public function pdf(Request $request): View
    {
        $filtros = $this->filtrosValidados($request);

        return view('reportes.infracciones.pdf', [
            'infracciones' => $this->servicio->infraccionesParaExport($filtros),
            'totales'      => $this->servicio->infraccionesTotales($filtros),
        ]);
    }

    /**
     * Extrae y normaliza los filtros del request.
     *
     * @return array{fecha_desde: ?string, fecha_hasta: ?string, estado: ?string, tipo_infraccion: ?string, zona_id: ?int, agente_parqueo_id: ?int}
     */
    private function filtrosValidados(Request $request): array
    {
        $estados = array_column(EstadoInfraccion::cases(), 'value');
        $tipos   = array_column(TipoInfraccion::cases(), 'value');

        return [
            'fecha_desde'       => $request->input('fecha_desde') ?: null,
            'fecha_hasta'       => $request->input('fecha_hasta') ?: null,
            'estado'            => in_array($request->input('estado'), $estados, true) ? $request->input('estado') : null,
            'tipo_infraccion'   => in_array($request->input('tipo_infraccion'), $tipos, true) ? $request->input('tipo_infraccion') : null,
            'zona_id'           => $request->filled('zona_id') ? (int) $request->input('zona_id') : null,
            'agente_parqueo_id' => $request->filled('agente_parqueo_id') ? (int) $request->input('agente_parqueo_id') : null,
        ];
    }
}
