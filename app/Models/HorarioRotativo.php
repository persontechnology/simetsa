<?php
// app/Models/HorarioRotativo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo HorarioRotativo — turno rotativo del agente (Art. 37.4).
 */
class HorarioRotativo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'horarios_rotativos';

    protected $fillable = [
        'agente_parqueo_id', 'zona_id', 'dia_semana',
        'hora_inicio', 'hora_fin', 'vigente_desde', 'vigente_hasta', 'activo', 'observacion',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'activo'        => 'boolean',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * Días de la semana (0=domingo). El SIMETSA opera mar-vie y dom (Art. 12).
     *
     * @return array<int, string>
     */
    public static function listadoDias(): array
    {
        return [
            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
        ];
    }

    public function getDiaLabelAttribute(): string
    {
        return self::listadoDias()[$this->dia_semana] ?? (string) $this->dia_semana;
    }
}