<?php
// app/Models/DiaFeriado.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiaFeriado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dias_feriado';

    public const TIPO_NACIONAL = 'nacional';
    public const TIPO_CIVICO   = 'civico';
    public const TIPO_CANTONAL = 'cantonal';
    public const TIPO_MOVIL    = 'movil';

    protected $fillable = ['fecha', 'nombre', 'tipo', 'recurrente', 'descripcion', 'activo'];

    protected $casts = [
        'fecha'       => 'date',
        'recurrente'  => 'boolean',
        'activo'      => 'boolean',
    ];

    public static function listadoTipos(): array
    {
        return [
            self::TIPO_NACIONAL => 'Nacional',
            self::TIPO_CIVICO   => 'Cívico',
            self::TIPO_CANTONAL => 'Cantonal',
            self::TIPO_MOVIL    => 'Móvil',
        ];
    }

    public function getTipoEtiquetaAttribute(): string
    {
        return self::listadoTipos()[$this->tipo] ?? $this->tipo;
    }

    public function getColorBadgeAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_NACIONAL => 'primary',
            self::TIPO_CIVICO   => 'warning',
            self::TIPO_CANTONAL => 'success',
            self::TIPO_MOVIL    => 'info',
            default             => 'secondary',
        };
    }

    /**
     * Verifica si una fecha dada es feriado (considerando recurrentes).
     */
    public static function esFeriado(\DateTimeInterface $fecha): bool
    {
        $fechaStr = $fecha->format('Y-m-d');
        $mesDia   = $fecha->format('m-d');

        // Coincidencia exacta de fecha (no recurrentes y recurrentes del año en curso)
        if (self::where('activo', true)->whereDate('fecha', $fechaStr)->exists()) {
            return true;
        }

        // Recurrentes: comparar mes-día
        return self::where('activo', true)
            ->where('recurrente', true)
            ->whereRaw("TO_CHAR(fecha, 'MM-DD') = ?", [$mesDia])
            ->exists();
    }

    public function scopeActivos($query) { return $query->where('activo', true); }
    public function scopeAno($query, int $ano) { return $query->whereYear('fecha', $ano); }
    public function scopeTipo($query, string $tipo) { return $query->where('tipo', $tipo); }
}