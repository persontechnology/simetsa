<?php
// app/Models/Zona.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Zona — zona tarifada del SIMETSA.
 *
 * El polígono se expone como arreglo PHP (cast 'array') de pares [lat, lng].
 * En las fases 2.D.2 y 2.D.3 se agregarán las relaciones calles() y
 * manzanas().
 */
class Zona extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'zonas';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion',
        'centro_lat', 'centro_lng', 'zoom',
        'poligono', 'color', 'activo',
    ];

    protected $casts = [
        'poligono'   => 'array',
        'centro_lat' => 'float',
        'centro_lng' => 'float',
        'zoom'       => 'integer',
        'activo'     => 'boolean',
    ];

    /**
     * Scope: solo zonas activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * ¿La zona tiene un polígono dibujado válido (al menos 3 vértices)?
     */
    public function tieneGeometria(): bool
    {
        return is_array($this->poligono) && count($this->poligono) >= 3;
    }

    /**
     * Centro de la zona como par [lat, lng] listo para Leaflet.
     *
     * @return array{0: float, 1: float}
     */
    public function centroParaMapa(): array
    {
        return [$this->centro_lat, $this->centro_lng];
    }

    /** Atajo de conveniencia para tests/seeders. */
    public static function porCodigoCentro(): ?self
    {
        return static::where('codigo', 'centro')->first();
    }
    
    /**Calles tarifadas que pertenecen a esta zona (Art. 16). */
    public function calles(): HasMany
    {
        return $this->hasMany(Calle::class)->orderBy('nombre');
    }

    /**  Manzanas (codificación urbana) que pertenecen a esta zona (Art. 10).     */
    public function manzanas(): HasMany
    {
        return $this->hasMany(Manzana::class)->orderBy('codigo');
    }
    /** Plazas individuales asociadas.*/
    public function plazas(): HasMany
    {
        return $this->hasMany(Plaza::class)->orderBy('codigo');
    }
}