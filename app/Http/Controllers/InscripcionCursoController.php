<?php
// app/Http/Controllers/InscripcionCursoController.php

namespace App\Http\Controllers;

use App\Models\CursoCapacitacion;
use App\Models\InscripcionCurso;
use App\Models\SolicitudAgente;
use App\Services\CapacitacionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador de inscripciones y calificaciones del curso (Etapa 2).
 */
class InscripcionCursoController extends Controller
{
    public function __construct(private CapacitacionService $servicio)
    {
        $this->middleware('permission:agentes.editar')->only(['store', 'calificar', 'destroy']);
    }

    /**
     * Inscribe un postulante en el curso.
     */
    public function store(Request $request, CursoCapacitacion $curso): RedirectResponse
    {
        $request->validate([
            'solicitud_agente_id' => ['required', 'integer', 'exists:solicitudes_agente,id'],
        ]);

        try {
            $solicitud = SolicitudAgente::findOrFail($request->input('solicitud_agente_id'));
            $this->servicio->inscribir($curso, $solicitud);
            return back()->with('success', 'Postulante inscrito en el curso.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Registra las 3 notas por temática y evalúa el resultado.
     */
    public function calificar(Request $request, InscripcionCurso $inscripcion): RedirectResponse
    {
        $datos = $request->validate([
            'notas'                   => ['required', 'array'],
            'notas.atencion_cliente'  => ['required', 'numeric', 'between:0,100'],
            'notas.primeros_auxilios' => ['required', 'numeric', 'between:0,100'],
            'notas.educacion_vial'    => ['required', 'numeric', 'between:0,100'],
        ]);

        $this->servicio->calificarYEvaluar($inscripcion, $datos['notas']);
        $inscripcion->refresh();

        $mensaje = $inscripcion->estado === InscripcionCurso::ESTADO_APROBADO
            ? "Aprobado con promedio {$inscripcion->promedio_final}. La solicitud pasa a autorización."
            : "Notas registradas. Promedio {$inscripcion->promedio_final} (no alcanza la nota mínima).";

        return back()->with('success', $mensaje);
    }

    /**
     * Elimina (soft delete) una inscripción.
     */
    public function destroy(InscripcionCurso $inscripcion): RedirectResponse
    {
        $inscripcion->delete();
        return back()->with('success', 'Inscripción eliminada.');
    }
}