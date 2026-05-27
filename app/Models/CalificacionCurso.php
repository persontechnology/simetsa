<?php
// app/Models/CalificacionCurso.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo CalificacionCurso — nota por temática de una inscripción (Art. 33.5).
 *
 * Las 3 temáticas son fijas: Atención al Cliente, Primeros Auxilios y
 * Educación Vial. La nota admite 2 decimales (Art. 33.5.c).
 */
class CalificacionCurso extends Model
{
    use HasFactory;

    protected $table = 'calificaciones_curso';

    public const TEMATICA_ATENCION          = 'atencion_cliente';
    public const TEMATICA_PRIMEROS_AUXILIOS = 'primeros_auxilios';
    public const TEMATICA_EDUCACION_VIAL    = 'educacion_vial';

    protected $fillable = ['inscripcion_curso_id', 'tematica', 'nota'];

    protected $casts = ['nota' => 'decimal:2'];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(InscripcionCurso::class, 'inscripcion_curso_id');
    }

    /**
     * Temáticas del curso (Art. 33.5).
     *
     * @return array<string, string>
     */
    public static function listadoTematicas(): array
    {
        return [
            self::TEMATICA_ATENCION          => 'Atención al Cliente',
            self::TEMATICA_PRIMEROS_AUXILIOS => 'Primeros Auxilios',
            self::TEMATICA_EDUCACION_VIAL    => 'Educación Vial',
        ];
    }

    public function getTematicaLabelAttribute(): string
    {
        return self::listadoTematicas()[$this->tematica] ?? $this->tematica;
    }
}