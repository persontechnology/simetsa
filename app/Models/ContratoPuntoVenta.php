<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contrato del punto de venta con Procuraduría Síndica (Art. 31).
 * Registra el descuento del 10% (Art. 31 / Art. 21).
 */
class ContratoPuntoVenta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contratos_punto_venta';

    protected $fillable = [
        'punto_venta_id', 'numero_contrato', 'fecha_firma', 'fecha_inicio',
        'fecha_fin', 'porcentaje_descuento', 'elaborado_por', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_firma' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'porcentaje_descuento' => 'decimal:2',
        ];
    }

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class);
    }
}