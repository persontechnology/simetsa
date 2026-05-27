<?php
// app/Services/CapacitacionService.php

namespace App\Services;

use App\Models\CalificacionCurso;
use App\Models\CursoCapacitacion;
use App\Models\InscripcionCurso;
use App\Models\Parametro;
use App\Models\SolicitudAgente;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de la Etapa 2 — Capacitación (Art. 33.5).
 *
 * Gestiona la inscripción de postulantes, el registro de notas por temática,
 * el cálculo del promedio y la evaluación contra la nota mínima. Al aprobar,
 * mueve la solicitud a la etapa de autorización (Etapa 3).
 */
class CapacitacionService
{
    /**
     * Genera el folio correlativo del curso (CUR-0001, ...).
     */
    public function generarCodigoCurso(): string
    {
        $ultimoId = CursoCapacitacion::withTrashed()->max('id') ?? 0;
        return 'CUR-' . str_pad((string) ($ultimoId + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Nota mínima de aprobación (Art. 33.5.b), parametrizable.
     */
    public function notaMinima(): float
    {
        return (float) Parametro::obtener('nota_minima_curso_agente', 70);
    }

    /**
     * Inscribe una solicitud (en etapa de capacitación) en un curso.
     *
     * @throws \DomainException
     */
    public function inscribir(CursoCapacitacion $curso, SolicitudAgente $solicitud): InscripcionCurso
    {
        if ($solicitud->estado !== SolicitudAgente::ESTADO_CAPACITACION) {
            throw new DomainException('Solo se pueden inscribir solicitudes en etapa de capacitación.');
        }

        $inscripcion = InscripcionCurso::withTrashed()
            ->where('curso_capacitacion_id', $curso->id)
            ->where('solicitud_agente_id', $solicitud->id)
            ->first();

        if ($inscripcion) {
            if (! $inscripcion->trashed()) {
                throw new DomainException('El postulante ya está inscrito en este curso.');
            }

            $inscripcion->restore();
            $inscripcion->update([
                'fecha_inscripcion' => now()->toDateString(),
                'estado'            => InscripcionCurso::ESTADO_INSCRITO,
                'promedio_final'    => null,
                'observacion'       => null,
            ]);
            $inscripcion->calificaciones()->delete();

            return $inscripcion;
        }

        return InscripcionCurso::create([
            'curso_capacitacion_id' => $curso->id,
            'solicitud_agente_id'   => $solicitud->id,
            'fecha_inscripcion'     => now()->toDateString(),
            'estado'                => InscripcionCurso::ESTADO_INSCRITO,
        ]);
    }

    /**
     * Guarda las notas por temática y evalúa el resultado de forma atómica.
     *
     * @param  array<string, float|string>  $notas  [tematica => nota]
     */
    public function calificarYEvaluar(InscripcionCurso $inscripcion, array $notas): void
    {
        DB::transaction(function () use ($inscripcion, $notas) {
            foreach ($notas as $tematica => $nota) {
                CalificacionCurso::updateOrCreate(
                    ['inscripcion_curso_id' => $inscripcion->id, 'tematica' => $tematica],
                    ['nota' => $nota]
                );
            }
            $this->evaluar($inscripcion->fresh('calificaciones'));
        });
    }

    /**
     * Calcula el promedio y, si están las 3 notas, define aprobado/reprobado.
     * Al aprobar, avanza la solicitud a la etapa de autorización (Etapa 3).
     */
    public function evaluar(InscripcionCurso $inscripcion): void
    {
        $tematicas = array_keys(CalificacionCurso::listadoTematicas());
        $notas = $inscripcion->calificaciones->pluck('nota', 'tematica');

        // Solo se evalúa cuando están cargadas las 3 temáticas
        if (count(array_intersect($tematicas, $notas->keys()->all())) < count($tematicas)) {
            return;
        }

        $promedio = round((float) $notas->map(fn ($n) => (float) $n)->avg(), 2);
        $aprobado = $promedio >= $this->notaMinima();

        $inscripcion->update([
            'promedio_final' => $promedio,
            'estado'         => $aprobado ? InscripcionCurso::ESTADO_APROBADO : InscripcionCurso::ESTADO_REPROBADO,
        ]);

        if ($aprobado) {
            $solicitud = $inscripcion->solicitud;
            if ($solicitud && $solicitud->estado === SolicitudAgente::ESTADO_CAPACITACION) {
                $solicitud->update(['estado' => SolicitudAgente::ESTADO_AUTORIZACION]);
            }
        }
    }
}