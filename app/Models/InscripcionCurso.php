<?php
// app/Models/InscripcionCurso.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo InscripcionCurso — postulante inscrito en una edición del curso.
 */
class InscripcionCurso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inscripciones_curso';

    public const ESTADO_INSCRITO  = 'inscrito';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_REPROBADO = 'reprobado';

    protected $fillable = [
        'curso_capacitacion_id', 'solicitud_agente_id',
        'fecha_inscripcion', 'estado', 'promedio_final', 'observacion',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'date',
        'promedio_final'    => 'decimal:2',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(CursoCapacitacion::class, 'curso_capacitacion_id');
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAgente::class, 'solicitud_agente_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(CalificacionCurso::class);
    }

    /**
     * Nota cargada para una temática (o null si aún no existe).
     */
    public function notaPorTematica(string $tematica): ?float
    {
        $cal = $this->calificaciones->firstWhere('tematica', $tematica);
        return $cal ? (float) $cal->nota : null;
    }

    /**
     * @return array<string, string>
     */
    public static function listadoEstados(): array
    {
        return [
            self::ESTADO_INSCRITO  => 'Inscrito',
            self::ESTADO_APROBADO  => 'Aprobado',
            self::ESTADO_REPROBADO => 'Reprobado',
        ];
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::listadoEstados()[$this->estado] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_APROBADO  => 'success',
            self::ESTADO_REPROBADO => 'danger',
            default                => 'secondary',
        };
    }
}