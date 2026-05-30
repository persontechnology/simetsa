<?php

// app/Models/TransaccionPago.php

namespace App\Models;

use App\Enums\EstadoTransaccion;
use App\Enums\ProveedorPago;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transacción de pago con un proveedor gateway digital (Art. 21 Ordenanza SIMETSA).
 *
 * Polimórfica: el campo concepto_type/concepto_id apunta a la entidad
 * cobrada (Ticket hoy; Multa en Fase 7, etc.).
 *
 * @property int                 $id
 * @property string              $concepto_type
 * @property int                 $concepto_id
 * @property ProveedorPago       $proveedor
 * @property float               $monto
 * @property string              $moneda
 * @property string|null         $external_reference
 * @property string|null         $payment_url
 * @property string|null         $qr_payload
 * @property EstadoTransaccion   $estado
 * @property array|null          $payload_request
 * @property array|null          $payload_response
 * @property \Carbon\Carbon|null $callback_recibido_en
 */
class TransaccionPago extends Model
{
    /** @use HasFactory<\Database\Factories\TransaccionPagoFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'transacciones_pago';

    protected $fillable = [
        'concepto_type', 'concepto_id',
        'proveedor', 'monto', 'moneda',
        'external_reference', 'payment_url', 'qr_payload',
        'estado',
        'payload_request', 'payload_response',
        'callback_recibido_en',
    ];

    protected $casts = [
        'proveedor'            => ProveedorPago::class,
        'estado'               => EstadoTransaccion::class,
        'monto'                => 'decimal:2',
        'payload_request'      => 'array',
        'payload_response'     => 'array',
        'callback_recibido_en' => 'datetime',
    ];

    /** Entidad cobrada (Ticket, Multa, etc.) — relación polimórfica. */
    public function concepto(): MorphTo
    {
        return $this->morphTo();
    }
}
