<?php
// app/Models/Calle.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Calle — vía tarifada perteneciente a una Zona (Art. 16).
 *
 * La polilínea se expone como arreglo PHP de pares [lat, lng].
 */
class Calle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'calles';

    public const SENTIDO_UNICO = 'unico';
    public const SENTIDO_DOBLE = 'doble';

    public const LADO_DERECHO    = 'derecho';
    public const LADO_IZQUIERDO  = 'izquierdo';
    public const LADO_AMBOS      = 'ambos';

    protected $fillable = [
        'zona_id', 'codigo', 'nombre',
        'desde', 'hasta',
        'sentido', 'lado_estacionamiento',
        'polilinea', 'activo',
    ];

    protected $casts = [
        'polilinea' => 'array',
        'activo'    => 'boolean',
    ];

    /**
     * Zona a la que pertenece la calle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * Listado de sentidos para selects.
     *
     * @return array<string, string>
     */
    public static function listadoSentidos(): array
    {
        return [
            self::SENTIDO_UNICO => 'Sentido único',
            self::SENTIDO_DOBLE => 'Doble sentido',
        ];
    }

    /**
     * Listado de costados de estacionamiento para selects.
     *
     * @return array<string, string>
     */
    public static function listadoLados(): array
    {
        return [
            self::LADO_DERECHO   => 'Costado derecho',
            self::LADO_IZQUIERDO => 'Costado izquierdo',
            self::LADO_AMBOS     => 'Ambos costados',
        ];
    }

    public function getSentidoEtiquetaAttribute(): string
    {
        return self::listadoSentidos()[$this->sentido] ?? $this->sentido;
    }

    public function getLadoEtiquetaAttribute(): string
    {
        return self::listadoLados()[$this->lado_estacionamiento] ?? $this->lado_estacionamiento;
    }

    /**
     * ¿La calle tiene una polilínea válida (al menos 2 vértices)?
     */
    public function tieneGeometria(): bool
    {
        return is_array($this->polilinea) && count($this->polilinea) >= 2;
    }

    /* Scope: solo calles activas, opcionalmente filtrando por zona. */
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