<?php
// app/Models/Plaza.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Plaza — espacio individual de estacionamiento.
 *
 * Pertenece a una Zona y un TipoPlaza (obligatorios), opcionalmente a una
 * Calle y una Manzana. Su posición es un punto [latitud, longitud].
 */
class Plaza extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plazas';

    public const ORIENTACION_PARALELO     = 'paralelo';
    public const ORIENTACION_PERPENDICULAR = 'perpendicular';
    public const ORIENTACION_BANDERA       = 'bandera';

    protected $fillable = [
        'zona_id', 'calle_id', 'manzana_id', 'tipo_plaza_id',
        'codigo', 'numero',
        'latitud', 'longitud',
        'ancho_metros', 'orientacion', 'activo','largo_metros',
    ];

    protected $casts = [
        'latitud'      => 'float',
        'longitud'     => 'float',
        'ancho_metros' => 'decimal:2',
        'activo'       => 'boolean',
        'largo_metros' => 'decimal:2',
    ];

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function calle(): BelongsTo
    {
        return $this->belongsTo(Calle::class);
    }

    public function manzana(): BelongsTo
    {
        return $this->belongsTo(Manzana::class);
    }

    public function tipoPlaza(): BelongsTo
    {
        return $this->belongsTo(TipoPlaza::class);
    }

    /**
     * Listado de orientaciones para selects.
     *
     * @return array<string, string>
     */
    public static function listadoOrientaciones(): array
    {
        return [
            self::ORIENTACION_PARALELO      => 'Paralelo a la acera',
            self::ORIENTACION_PERPENDICULAR => 'Perpendicular',
            self::ORIENTACION_BANDERA       => 'En bandera',
        ];
    }

    public function getOrientacionEtiquetaAttribute(): string
    {
        return self::listadoOrientaciones()[$this->orientacion] ?? $this->orientacion;
    }

    /**
     * ¿La plaza tiene una ubicación puntual definida?
     */
    public function tieneUbicacion(): bool
    {
        return $this->latitud !== null && $this->longitud !== null;
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Etiqueta legible de dimensiones (ancho × largo).
     * Devuelve '—' si no hay datos.
     *
     * @return string
     */
    public function getDimensionesAttribute(): string
    {
        if (!$this->ancho_metros && !$this->largo_metros) {
            return '—';
        }
        $ancho = $this->ancho_metros ? number_format((float) $this->ancho_metros, 2) : '?';
        $largo = $this->largo_metros ? number_format((float) $this->largo_metros, 2) : '?';
        return "{$ancho} × {$largo} m";
    }
}