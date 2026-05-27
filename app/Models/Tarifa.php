<?php
// app/Models/Tarifa.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Tarifa — define el costo por hora aplicable a un TipoPlaza
 * en un rango de fechas.
 *
 * Estados derivados (accessor `estado`):
 *  - vigente:  hoy está dentro del rango y `activo = true`.
 *  - futura:   vigente_desde es posterior a hoy.
 *  - expirada: vigente_hasta es anterior a hoy.
 *  - inactiva: `activo = false`.
 */
class Tarifa extends Model
{
    use HasFactory, SoftDeletes;

    /** @var string */
    protected $table = 'tarifas';

    /** @var array<int, string> */
    protected $fillable = [
        'tipo_plaza_id',
        'nombre',
        'valor_hora',
        'vigente_desde',
        'vigente_hasta',
        'descripcion',
        'activo',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'valor_hora'    => 'decimal:4',
        'activo'        => 'boolean',
    ];

    /**
     * Tipo de plaza al que aplica esta tarifa.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tipoPlaza(): BelongsTo
    {
        return $this->belongsTo(TipoPlaza::class);
    }

    /**
     * Devuelve el estado computado de la tarifa según la fecha actual.
     *
     * @return string
     */
    public function getEstadoAttribute(): string
    {
        if (!$this->activo) {
            return 'inactiva';
        }
        $hoy = today();
        if ($this->vigente_desde->gt($hoy)) {
            return 'futura';
        }
        if ($this->vigente_hasta && $this->vigente_hasta->lt($hoy)) {
            return 'expirada';
        }
        return 'vigente';
    }

    /**
     * Color de badge Bootstrap apropiado para cada estado.
     *
     * @return string
     */
    public function getColorBadgeAttribute(): string
    {
        return match ($this->estado) {
            'vigente'  => 'success',
            'futura'   => 'info',
            'expirada' => 'secondary',
            'inactiva' => 'danger',
        };
    }

    /**
     * Etiqueta legible del estado.
     *
     * @return string
     */
    public function getEstadoEtiquetaAttribute(): string
    {
        return match ($this->estado) {
            'vigente'  => 'Vigente',
            'futura'   => 'Futura',
            'expirada' => 'Expirada',
            'inactiva' => 'Inactiva',
        };
    }

    /**
     * Scope: solo tarifas activas (no soft-deleted ni activo=false).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}