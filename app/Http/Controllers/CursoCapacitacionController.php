<?php
// app/Http/Controllers/CursoCapacitacionController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCursoCapacitacionRequest;
use App\Http\Requests\UpdateCursoCapacitacionRequest;
use App\Models\CalificacionCurso;
use App\Models\CursoCapacitacion;
use App\Models\SolicitudAgente;
use App\Services\CapacitacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador de cursos de capacitación — Etapa 2 (Art. 33.5).
 *
 * El show es el hub: muestra el curso, sus inscripciones ordenadas por
 * promedio (ranking, Art. 33.5.d) y los postulantes disponibles para inscribir.
 */
class CursoCapacitacionController extends Controller
{
    public function __construct(private CapacitacionService $servicio)
    {
        $this->middleware('permission:agentes.ver')->only(['index', 'show']);
        $this->middleware('permission:agentes.crear')->only(['create', 'store']);
        $this->middleware('permission:agentes.editar')->only(['edit', 'update']);
        $this->middleware('permission:agentes.eliminar')->only('destroy');
    }

    public function index(): View
    {
        $cursos = CursoCapacitacion::withCount('inscripciones')
            ->orderByDesc('fecha_inicio')->paginate(20);

        return view('cursos-capacitacion.index', compact('cursos'));
    }

    public function create(): View
    {
        return view('cursos-capacitacion.create', [
            'curso'   => null,
            'estados' => CursoCapacitacion::listadoEstados(),
        ]);
    }

    public function store(StoreCursoCapacitacionRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['codigo'] = $this->servicio->generarCodigoCurso();
        $curso = CursoCapacitacion::create($datos);

        return redirect()->route('cursos-capacitacion.show', $curso)
            ->with('success', "Curso {$curso->codigo} creado. Inscribí a los postulantes.");
    }

    public function show(CursoCapacitacion $curso): View
    {
        $curso->load(['inscripciones' => fn ($q) => $q
            ->with(['solicitud', 'calificaciones'])
            ->orderByDesc('promedio_final')]);

        // Postulantes en capacitación que aún no están inscritos en este curso
        $inscritas = $curso->inscripciones->pluck('solicitud_agente_id')->all();
        $disponibles = SolicitudAgente::where('estado', SolicitudAgente::ESTADO_CAPACITACION)
            ->whereNotIn('id', $inscritas)->orderBy('nombres')->get();

        return view('cursos-capacitacion.show', [
            'curso'       => $curso,
            'disponibles' => $disponibles,
            'tematicas'   => CalificacionCurso::listadoTematicas(),
            'notaMinima'  => $this->servicio->notaMinima(),
        ]);
    }

    public function edit(CursoCapacitacion $curso): View
    {
        return view('cursos-capacitacion.edit', [
            'curso'   => $curso,
            'estados' => CursoCapacitacion::listadoEstados(),
        ]);
    }

    public function update(UpdateCursoCapacitacionRequest $request, CursoCapacitacion $curso): RedirectResponse
    {
        $curso->update($request->validated());
        return redirect()->route('cursos-capacitacion.show', $curso)
            ->with('success', "Curso {$curso->codigo} actualizado.");
    }

    public function destroy(CursoCapacitacion $curso): RedirectResponse
    {
        $curso->delete();
        return redirect()->route('cursos-capacitacion.index')
            ->with('success', "Curso {$curso->codigo} eliminado (soft delete).");
    }
}