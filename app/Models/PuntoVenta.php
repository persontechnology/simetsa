<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Punto de venta autorizado del SIMETSA (Art. 31).
 *
 * Es el resultado de activar una solicitud con su contrato firmado.
 * Tiene una cuenta de usuario asociada (rol punto_venta).
 */
class PuntoVenta extends Model
{
    use HasFactory, SoftDeletes;

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_SUSPENDIDO = 'suspendido';
    public const ESTADO_INACTIVO = 'inactivo';

    protected $table = 'puntos_venta';

    protected $fillable = [
        'codigo', 'solicitud_punto_venta_id', 'user_id', 'nombre_comercial',
        'direccion_local', 'referencia_ubicacion', 'latitud', 'longitud',
        'estado', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function listadoEstados(): array
    {
        return [
            self::ESTADO_ACTIVO => 'Activo',
            self::ESTADO_SUSPENDIDO => 'Suspendido',
            self::ESTADO_INACTIVO => 'Inactivo',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudPuntoVenta::class, 'solicitud_punto_venta_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contrato(): HasOne
    {
        return $this->hasOne(ContratoPuntoVenta::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function tieneUbicacion(): bool
    {
        return $this->latitud !== null && $this->longitud !== null;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::listadoEstados()[$this->estado] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_ACTIVO => 'success',
            self::ESTADO_SUSPENDIDO => 'warning',
            self::ESTADO_INACTIVO => 'secondary',
            default => 'secondary',
        };
    }
}