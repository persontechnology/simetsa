<?php
// app/Models/CursoCapacitacion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo CursoCapacitacion — edición del curso para agentes (Art. 33.5).
 */
class CursoCapacitacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cursos_capacitacion';

    public const ESTADO_PLANIFICADO = 'planificado';
    public const ESTADO_EN_CURSO    = 'en_curso';
    public const ESTADO_FINALIZADO  = 'finalizado';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion',
        'fecha_inicio', 'fecha_fin', 'cupo', 'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    /**
     * Inscripciones de postulantes en este curso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionCurso::class);
    }

    /**
     * @return array<string, string>
     */
    public static function listadoEstados(): array
    {
        return [
            self::ESTADO_PLANIFICADO => 'Planificado',
            self::ESTADO_EN_CURSO    => 'En curso',
            self::ESTADO_FINALIZADO  => 'Finalizado',
        ];
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::listadoEstados()[$this->estado] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PLANIFICADO => 'secondary',
            self::ESTADO_EN_CURSO    => 'primary',
            self::ESTADO_FINALIZADO  => 'success',
            default                  => 'secondary',
        };
    }
}