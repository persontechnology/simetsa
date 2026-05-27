<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentoPuntoVentaStoreRequest;
use App\Models\DocumentoPuntoVenta;
use App\Models\SolicitudPuntoVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Carga, validación y descarga de documentos de una solicitud de punto de venta.
 */
class DocumentoPuntoVentaController extends Controller
{
    
   public function __construct()
    {
        $this->middleware('permission:puntos_venta.editar', ['only' => ['store', 'validar', 'destroy']]);
        $this->middleware('permission:puntos_venta.ver', ['only' => ['descargar']]);    
    }

    public function store(DocumentoPuntoVentaStoreRequest $request, SolicitudPuntoVenta $solicitud): RedirectResponse
    {
        try {
            $archivo = $request->file('archivo');
            $ruta = $archivo->store("documentos_punto_venta/{$solicitud->id}", 'public');

            $solicitud->documentos()->create([
                'tipo' => $request->validated('tipo'),
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'ruta_archivo' => $ruta,
                'observacion' => $request->validated('observacion'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al subir documento de punto de venta', [
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo subir el documento. Intentá nuevamente.');
        }

        return back()->with('success', 'Documento cargado.');
    }

    public function validar(DocumentoPuntoVenta $documento): RedirectResponse
    {
        $nuevoEstado = ! $documento->validado;

        $documento->update([
            'validado' => $nuevoEstado,
            'fecha_validacion' => $nuevoEstado ? now() : null,
            'validado_por' => $nuevoEstado ? auth()->id() : null,
        ]);

        return back()->with('success', $nuevoEstado ? 'Documento validado.' : 'Validación retirada.');
    }

    public function destroy(DocumentoPuntoVenta $documento): RedirectResponse
    {
        $documento->delete();

        return back()->with('success', 'Documento eliminado.');
    }

    public function descargar(DocumentoPuntoVenta $documento): StreamedResponse
    {
        return Storage::disk('public')->download($documento->ruta_archivo, $documento->nombre_archivo);
    }
}