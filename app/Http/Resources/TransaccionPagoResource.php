<?php

// app/Http/Resources/TransaccionPagoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON de una TransaccionPago para la app móvil.
 *
 * @mixin \App\Models\TransaccionPago
 */
class TransaccionPagoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'proveedor'          => $this->proveedor->value,
            'proveedor_label'    => $this->proveedor->etiqueta(),
            'monto'              => (float) $this->monto,
            'moneda'             => $this->moneda,
            'estado'             => $this->estado->value,
            'estado_label'       => $this->estado->etiqueta(),
            'payment_url'        => $this->payment_url,
            'qr_payload'         => $this->qr_payload,
            'external_reference' => $this->external_reference,
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
