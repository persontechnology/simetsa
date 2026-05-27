<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Solicitud de punto de venta del SIMETSA (Art. 31).
 *
 * Es el trámite con estados (documentacion → contrato → activa/rechazada).
 * El resultado autorizado es el modelo PuntoVenta (Fase 3.E.2).
 */
class SolicitudPuntoVenta extends Model
{
    use HasFactory, SoftDeletes;

    public const ESTADO_DOCUMENTACION = 'documentacion';
    public const ESTADO_CONTRATO = 'contrato';
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_RECHAZADA = 'rechazada';

    protected $table = 'solicitudes_punto_venta';

    protected $fillable = [
        'codigo', 'cedula', 'nombres', 'apellidos', 'telefono', 'telefono_celular',
        'email', 'nombre_comercial', 'ruc', 'direccion', 'direccion_local',
        'referencia_ubicacion', 'latitud', 'longitud', 'estado', 'observaciones',
        'motivo_rechazo', 'fecha_solicitud', 'usuario_registro_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoPuntoVenta::class);
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    // 3.E.2: contrato() y puntoVenta()
    public function puntoVenta(): HasOne
    {
        return $this->hasOne(PuntoVenta::class, 'solicitud_punto_venta_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_DOCUMENTACION => 'En documentación',
            self::ESTADO_CONTRATO => 'En contrato',
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_RECHAZADA => 'Rechazada',
            default => $this->estado,
        };
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_DOCUMENTACION => 'secondary',
            self::ESTADO_CONTRATO => 'info',
            self::ESTADO_ACTIVA => 'success',
            self::ESTADO_RECHAZADA => 'danger',
            default => 'secondary',
        };
    }

    public function tieneUbicacion(): bool
    {
        return $this->latitud !== null && $this->longitud !== null;
    }

    public function enEtapaDocumentacion(): bool
    {
        return $this->estado === self::ESTADO_DOCUMENTACION;
    }
}