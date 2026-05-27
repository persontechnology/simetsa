<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudPuntoVentaStoreRequest;
use App\Http\Requests\SolicitudPuntoVentaUpdateRequest;
use App\Models\DocumentoPuntoVenta;
use App\Models\SolicitudPuntoVenta;
use App\Services\SolicitudPuntoVentaService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * CRUD y flujo de la solicitud de punto de venta (Etapa 1: documentación, Art. 31).
 */
class SolicitudPuntoVentaController extends Controller
{
    public function __construct(private readonly SolicitudPuntoVentaService $servicio)
    {
        $this->middleware('permission:puntos_venta.ver', ['only' => ['index', 'show']]);
        $this->middleware('permission:puntos_venta.crear', ['only' => ['create', 'store']]);
        $this->middleware('permission:puntos_venta.editar', ['only' => ['edit', 'update', 'aprobarDocumentacion', 'rechazar']]);
        $this->middleware('permission:puntos_venta.eliminar', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $estado = $request->query('estado');

        $solicitudes = SolicitudPuntoVenta::query()
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('solicitudes-punto-venta.index', [
            'solicitudes' => $solicitudes,
            'estadoFiltro' => $estado,
        ]);
    }

    public function create(): View
    {
        return view('solicitudes-punto-venta.create');
    }

    public function store(SolicitudPuntoVentaStoreRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['codigo'] = $this->servicio->generarCodigo();
        $datos['estado'] = SolicitudPuntoVenta::ESTADO_DOCUMENTACION;
        $datos['fecha_solicitud'] = now();
        $datos['usuario_registro_id'] = auth()->id();

        $solicitud = SolicitudPuntoVenta::create($datos);

        return redirect()
            ->route('solicitudes-punto-venta.show', $solicitud)
            ->with('success', "Solicitud {$solicitud->codigo} registrada. Cargá la documentación requerida.");
    }

    public function show(SolicitudPuntoVenta $solicitud): View
    {
        $solicitud->load('documentos.validadoPor', 'usuarioRegistro','puntoVenta');

        return view('solicitudes-punto-venta.show', [
            'solicitud' => $solicitud,
            'tiposDocumento' => DocumentoPuntoVenta::listadoTipos(),
            'requeridos' => $this->servicio->documentosRequeridos(),
            'completa' => $this->servicio->documentacionCompleta($solicitud),
        ]);
    }

    public function edit(SolicitudPuntoVenta $solicitud): View
    {
        return view('solicitudes-punto-venta.edit', ['solicitud' => $solicitud]);
    }

    public function update(SolicitudPuntoVentaUpdateRequest $request, SolicitudPuntoVenta $solicitud): RedirectResponse
    {
        $solicitud->update($request->validated());

        return redirect()
            ->route('solicitudes-punto-venta.show', $solicitud)
            ->with('success', 'Solicitud actualizada.');
    }

    public function destroy(SolicitudPuntoVenta $solicitud): RedirectResponse
    {
        $solicitud->delete();

        return redirect()
            ->route('solicitudes-punto-venta.index')
            ->with('success', 'Solicitud eliminada.');
    }

    public function aprobarDocumentacion(SolicitudPuntoVenta $solicitud): RedirectResponse
    {
        try {
            $this->servicio->aprobarDocumentacion($solicitud);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Documentación aprobada. La solicitud pasa a la etapa de contrato.');
    }

    public function rechazar(Request $request, SolicitudPuntoVenta $solicitud): RedirectResponse
    {
        $datos = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'max:500'],
        ]);

        $this->servicio->rechazar($solicitud, $datos['motivo_rechazo']);

        return back()->with('success', 'Solicitud rechazada.');
    }
}