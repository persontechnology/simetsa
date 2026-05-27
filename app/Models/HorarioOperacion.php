<?php
// app/Models/HorarioOperacion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioOperacion extends Model
{
    use HasFactory;

    protected $table = 'horarios_operacion';

    protected $fillable = ['dia_semana', 'hora_inicio', 'hora_fin', 'activo'];

    protected $casts = [
        'dia_semana' => 'integer',
        'activo'     => 'boolean',
    ];

    /**
     * Etiquetas de días de la semana (0=domingo … 6=sábado).
     *
     * @return array<int, string>
     */
    public static function nombresDias(): array
    {
        return [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];
    }

    public function getNombreDiaAttribute(): string
    {
        return self::nombresDias()[$this->dia_semana] ?? 'Desconocido';
    }

    /**
     * Verifica si el SIMETSA opera ahora mismo.
     * Considera día de la semana + horario + feriados.
     */
    public static function estaOperativoAhora(): bool
    {
        $ahora = now();

        // Si es feriado, no opera
        if (DiaFeriado::esFeriado($ahora)) {
            return false;
        }

        $diaSemana = (int) $ahora->dayOfWeek; // 0=domingo
        $horario   = self::where('dia_semana', $diaSemana)->first();

        if (!$horario || !$horario->activo) return false;

        $horaActual = $ahora->format('H:i:s');
        return $horaActual >= $horario->hora_inicio && $horaActual <= $horario->hora_fin;
    }
}