<?php
// app/Models/AmonestacionAgente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo AmonestacionAgente — sanción al agente (Art. 40).
 *
 * Escalada: verbal (1.ª) → escrita (2.ª) → terminación (3.ª).
 */
class AmonestacionAgente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'amonestaciones_agente';

    public const TIPO_VERBAL      = 'verbal';
    public const TIPO_ESCRITA     = 'escrita';
    public const TIPO_TERMINACION = 'terminacion';

    protected $fillable = [
        'agente_parqueo_id', 'tipo', 'numero_falta', 'motivo', 'fecha', 'registrada_por',
    ];

    protected $casts = ['fecha' => 'date'];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(AgenteParqueo::class, 'agente_parqueo_id');
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'registrada_por');
    }

    /**
     * @return array<string, string>
     */
    public static function listadoTipos(): array
    {
        return [
            self::TIPO_VERBAL      => 'Amonestación verbal',
            self::TIPO_ESCRITA     => 'Amonestación escrita',
            self::TIPO_TERMINACION => 'Terminación de autorización',
        ];
    }

    public function getTipoLabelAttribute(): string
    {
        return self::listadoTipos()[$this->tipo] ?? $this->tipo;
    }

    public function getTipoColorAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_VERBAL      => 'warning',
            self::TIPO_ESCRITA     => 'danger',
            self::TIPO_TERMINACION => 'dark',
            default                => 'secondary',
        };
    }
}