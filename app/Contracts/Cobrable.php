<?php

// app/Contracts/Cobrable.php

namespace App\Contracts;

use App\Models\TransaccionPago;

/**
 * Contrato que implementa cualquier entidad que puede ser cobrada
 * por un proveedor de pagos (Ticket hoy; Multa en Fase 7, etc.).
 */
interface Cobrable
{
    /** Monto a cobrar en USD. */
    public function montoCobrable(): float;

    /** Descripción del concepto para el gateway y el comprobante. */
    public function descripcionCobro(): string;

    /**
     * Acredita el cobro al completarse la transacción confirmada.
     * Cada concepto decide qué estado/acción tomar al recibir pago confirmado.
     *
     * @param  TransaccionPago  $transaccion  Transacción con estado Completada.
     * @return void
     */
    public function acreditar(TransaccionPago $transaccion): void;
}
