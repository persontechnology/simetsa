<?php
// app/Http/Controllers/DocumentoAgenteController.php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentoAgenteStoreRequest;
use App\Models\DocumentoAgente;
use App\Models\SolicitudAgente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de documentos de una solicitud de agente.
 *
 * Carga (Storage local), valida y elimina documentos, y permite descargarlos.
 */
class DocumentoAgenteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:agentes.ver')->only('descargar');
        $this->middleware('permission:agentes.editar')->only(['store', 'validar', 'destroy']);
    }

    /**
     * Carga un documento para una solicitud y lo guarda en el disco public.
     */
    public function store(DocumentoAgenteStoreRequest $request, SolicitudAgente $solicitud): RedirectResponse
    {
        try {
            $archivo = $request->file('archivo');
            // Guardado en storage/app/public/documentos_agente/{id}/
            $ruta = $archivo->store("documentos_agente/{$solicitud->id}", 'public');

            DocumentoAgente::create([
                'solicitud_agente_id' => $solicitud->id,
                'tipo'                => $request->input('tipo'),
                'nombre_archivo'      => $archivo->getClientOriginalName(),
                'ruta_archivo'        => $ruta,
                'validado'            => false,
            ]);

            return back()->with('success', 'Documento cargado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al cargar documento de agente', [
                'solicitud_id' => $solicitud->id,
                'error'        => $e->getMessage(),
            ]);
            return back()->with('error', 'No se pudo cargar el documento. Intentá nuevamente.');
        }
    }

    /**
     * Marca un documento como validado.
     */
    public function validar(DocumentoAgente $documento): RedirectResponse
    {
        $documento->update([
            'validado'         => true,
            'fecha_validacion' => now(),
            'validado_por'     => auth()->id(),
        ]);

        return back()->with('success', "Documento '{$documento->tipo_label}' validado.");
    }

    /**
     * Elimina (soft delete) un documento.
     */
    public function destroy(DocumentoAgente $documento): RedirectResponse
    {
        $documento->delete();
        return back()->with('success', 'Documento eliminado.');
    }

    /**
     * Descarga el archivo del documento.
     */
    public function descargar(DocumentoAgente $documento): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($documento->ruta_archivo), 404);

        return Storage::disk('public')->download($documento->ruta_archivo, $documento->nombre_archivo);
    }
}