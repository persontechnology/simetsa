<?php
// app/Models/Manzana.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Manzana — unidad de codificación urbana de una Zona (Art. 10).
 *
 * El polígono se expone como arreglo PHP de pares [lat, lng].
 */
class Manzana extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'manzanas';

    protected $fillable = [
        'zona_id', 'codigo', 'nombre',
        'descripcion', 'poligono', 'color', 'activo',
    ];

    protected $casts = [
        'poligono' => 'array',
        'activo'   => 'boolean',
    ];

    /**
     * Zona a la que pertenece la manzana.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * ¿La manzana tiene un polígono válido (al menos 3 vértices)?
     */
    public function tieneGeometria(): bool
    {
        return is_array($this->poligono) && count($this->poligono) >= 3;
    }

    /* Scope: solo manzanas activas, opcionalmente filtrando por zona. */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /** Plazas individuales asociadas.*/
    public function plazas(): HasMany
    {
        return $this->hasMany(Plaza::class)->orderBy('codigo');
    }
}