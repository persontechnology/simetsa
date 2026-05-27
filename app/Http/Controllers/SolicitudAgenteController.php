<?php
// app/Http/Controllers/SolicitudAgenteController.php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudAgenteStoreRequest;
use App\Http\Requests\SolicitudAgenteUpdateRequest;
use App\Models\DocumentoAgente;
use App\Models\SolicitudAgente;
use App\Services\SolicitudAgenteService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del trámite de Agente de Parqueo — Etapa 1 (Art. 32-35).
 *
 * Gestiona el CRUD de solicitudes y las transiciones de estado de la
 * documentación (aprobar → pasa a capacitación; rechazar → cierra el trámite).
 */
class SolicitudAgenteController extends Controller
{
    public function __construct(private SolicitudAgenteService $servicio)
    {
        $this->middleware('permission:agentes.ver')->only(['index', 'show']);
        $this->middleware('permission:agentes.crear')->only(['create', 'store']);
        $this->middleware('permission:agentes.editar')->only(['edit', 'update', 'aprobarDocumentacion', 'rechazar']);
        $this->middleware('permission:agentes.eliminar')->only('destroy');
    }

    public function index(Request $request): View
    {
        $estado = $request->input('estado');

        $solicitudes = SolicitudAgente::query()
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_solicitud')
            ->paginate(20)->withQueryString();

        return view('solicitudes-agente.index', [
            'solicitudes' => $solicitudes,
            'estados'     => SolicitudAgente::metaEstados(),
            'estado'      => $estado,
        ]);
    }

    public function create(): View
    {
        return view('solicitudes-agente.create', [
            'solicitud' => null,
            'niveles'   => SolicitudAgente::listadoNivelesEducacion(),
        ]);
    }

    public function store(SolicitudAgenteStoreRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['codigo']              = $this->servicio->generarCodigo();
        $datos['estado']              = SolicitudAgente::ESTADO_DOCUMENTACION;
        $datos['fecha_solicitud']     = now()->toDateString();
        $datos['usuario_registro_id'] = $request->user()->id;

        $solicitud = SolicitudAgente::create($datos);

        return redirect()->route('solicitudes-agente.show', $solicitud)
            ->with('success', "Solicitud {$solicitud->codigo} registrada. Cargá los documentos requeridos.");
    }

    public function show(SolicitudAgente $solicitud): View
    {
        $solicitud->load('documentos.validadoPor');

        return view('solicitudes-agente.show', [
            'solicitud'             => $solicitud,
            'documentosRequeridos'  => $this->servicio->documentosRequeridos(),
            'documentacionCompleta' => $this->servicio->documentacionCompleta($solicitud),
            'tiposDocumento'        => DocumentoAgente::listadoTipos(),
        ]);
    }

    public function edit(SolicitudAgente $solicitud): View
    {
        return view('solicitudes-agente.edit', [
            'solicitud' => $solicitud,
            'niveles'   => SolicitudAgente::listadoNivelesEducacion(),
        ]);
    }

    public function update(SolicitudAgenteUpdateRequest $request, SolicitudAgente $solicitud): RedirectResponse
    {
        $solicitud->update($request->validated());

        return redirect()->route('solicitudes-agente.show', $solicitud)
            ->with('success', "Solicitud {$solicitud->codigo} actualizada.");
    }

    public function destroy(SolicitudAgente $solicitud): RedirectResponse
    {
        $solicitud->delete(); // soft delete
        return redirect()->route('solicitudes-agente.index')
            ->with('success', "Solicitud {$solicitud->codigo} eliminada (soft delete).");
    }

    /**
     * Aprueba la documentación (Etapa 1 → Etapa 2).
     */
    public function aprobarDocumentacion(SolicitudAgente $solicitud): RedirectResponse
    {
        try {
            $this->servicio->aprobarDocumentacion($solicitud);
            return back()->with('success', 'Documentación aprobada. La solicitud pasa a la etapa de capacitación.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Rechaza la solicitud con un motivo.
     */
    public function rechazar(Request $request, SolicitudAgente $solicitud): RedirectResponse
    {
        $request->validate(['motivo_rechazo' => ['required', 'string', 'max:500']]);

        $this->servicio->rechazar($solicitud, $request->input('motivo_rechazo'));
        return back()->with('success', "Solicitud {$solicitud->codigo} rechazada.");
    }
}