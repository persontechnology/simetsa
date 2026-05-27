<?php
// app/Models/TipoPlaza.php

namespace App\Models;

use App\Models\Tarifa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo TipoPlaza — catálogo de tipos de plaza de estacionamiento.
 *
 * Cada Plaza (Fase 2.E) referenciará un TipoPlaza por id, lo que define
 * sus reglas: si paga tarifa, si requiere credencial CONADIS, su color
 * para visualización en el mapa, etc.
 */
class TipoPlaza extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_plaza';

    public const COD_NORMAL       = 'normal';
    public const COD_DISCAPACIDAD = 'discapacidad';
    public const COD_TAXI         = 'taxi';
    public const COD_CARGA        = 'carga';
    public const COD_AUTORIDAD    = 'autoridad';
    /* para moto */
    public const COD_MOTO         = 'moto';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion',
        'requiere_credencial', 'es_pagado',
        'color_mapa', 'icono', 'activo',
        'ancho_sugerido', 'largo_sugerido',

    ];

    protected $casts = [
        'requiere_credencial' => 'boolean',
        'es_pagado'           => 'boolean',
        'activo'              => 'boolean',
        'ancho_sugerido'      => 'decimal:2',
        'largo_sugerido'      => 'decimal:2',
    ];

    /**
     * Scope: solo tipos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Historial completo de tarifas de este tipo de plaza.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class)->orderBy('vigente_desde', 'desc');
    }

    /**
     * Helper estático para localizar un tipo por su código snake_case.
     */
    public static function porCodigo(string $codigo): ?self
    {
        return static::where('codigo', $codigo)->first();
    }

    /**
     * Plazas que usan este tipo.
     */
    public function plazas(): HasMany
    {
        return $this->hasMany(Plaza::class);
    }

    /**
     * Etiqueta legible de las dimensiones sugeridas (ancho × largo).
     *
     * @return string
     */
    public function getDimensionesSugeridasAttribute(): string
    {
        if (!$this->ancho_sugerido && !$this->largo_sugerido) {
            return '—';
        }
        $a = $this->ancho_sugerido ? number_format((float) $this->ancho_sugerido, 2) : '?';
        $l = $this->largo_sugerido ? number_format((float) $this->largo_sugerido, 2) : '?';
        return "{$a} × {$l} m";
    }
}