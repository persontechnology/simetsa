<?php
// app/Models/AgenteParqueo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo AgenteParqueo — agente de parqueo autorizado (Art. 36).
 */
class AgenteParqueo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agentes_parqueo';

    public const ESTADO_ACTIVO     = 'activo';
    public const ESTADO_SUSPENDIDO = 'suspendido';
    public const ESTADO_TERMINADO  = 'terminado'; // Art. 40.c

    protected $fillable = [
        'codigo', 'solicitud_agente_id', 'user_id',
        'numero_credencial', 'numero_oficio_comisario',
        'carta_compromiso_firmada', 'fecha_autorizacion', 'estado', 'observaciones',
    ];

    protected $casts = [
        'carta_compromiso_firmada' => 'boolean',
        'fecha_autorizacion'       => 'date',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudAgente::class, 'solicitud_agente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function expediente(): HasOne
    {
        return $this->hasOne(ExpedienteAgente::class);
    }

    /**
     * @return array<string, string>
     */
    public static function listadoEstados(): array
    {
        return [
            self::ESTADO_ACTIVO     => 'Activo',
            self::ESTADO_SUSPENDIDO => 'Suspendido',
            self::ESTADO_TERMINADO  => 'Terminado',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->user?->name ?? $this->solicitud?->nombre_completo ?? 'Agente';
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::listadoEstados()[$this->estado] ?? $this->estado;
    }

    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_ACTIVO     => 'success',
            self::ESTADO_SUSPENDIDO => 'warning',
            self::ESTADO_TERMINADO  => 'danger',
            default                 => 'secondary',
        };
    }

    
    /**
     * Zonas asignadas al agente (Art. 16).
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionZona::class)->orderByDesc('fecha_inicio');
    }

    /**
     * Horarios rotativos del agente (Art. 37.4).
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioRotativo::class)->orderBy('dia_semana');
    }

    /**
     * Amonestaciones del agente (Art. 40).
     */
    public function amonestaciones(): HasMany
    {
        return $this->hasMany(AmonestacionAgente::class)->orderBy('numero_falta');
    }
}