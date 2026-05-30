<?php

// app/Contracts/PaymentProviderInterface.php

namespace App\Contracts;

use App\Models\TransaccionPago;
use DomainException;

/**
 * Contrato de proveedor de pagos multi-gateway.
 *
 * Cada proveedor (Deuna, Pagomedios, etc.) implementa esta interfaz.
 * El PagoManager resuelve el proveedor correcto en tiempo de ejecución.
 */
interface PaymentProviderInterface
{
    /** Identificador de proveedor (ej. 'deuna', 'pagomedios'). */
    public function nombre(): string;

    /** True si el proveedor está habilitado en el entorno actual. */
    public function estaHabilitado(): bool;

    /**
     * Inicia el cobro para una entidad Cobrable y persiste la TransaccionPago.
     *
     * En modo fake (DEUNA_MODE=fake o DEUNA_ENABLED=false): genera una
     * transacción simulada sin hacer ninguna llamada HTTP externa.
     *
     * @param  Cobrable  $concepto  Entidad a cobrar (Ticket, Multa, etc.)
     * @param  array     $opciones  Parámetros adicionales (metodo_pago, redirect_url…)
     * @return TransaccionPago      Con estado Pendiente.
     *
     * @throws DomainException  Si el proveedor no está habilitado.
     */
    public function iniciarCobro(Cobrable $concepto, array $opciones = []): TransaccionPago;

    /**
     * Consulta el estado actual de una transacción en el gateway.
     * En modo fake: devuelve la misma transacción sin llamada HTTP.
     *
     * @param  TransaccionPago  $transaccion
     * @return TransaccionPago  Con estado actualizado.
     */
    public function consultarEstado(TransaccionPago $transaccion): TransaccionPago;

    /**
     * Procesa un callback de webhook del gateway y actualiza la transacción.
     *
     * @param  array   $payload  Cuerpo del webhook parseado.
     * @param  string  $firma    Header de firma del proveedor para verificación HMAC.
     * @return TransaccionPago   Con estado actualizado.
     *
     * @throws DomainException  Si la firma es inválida (en modo real).
     */
    public function procesarWebhook(array $payload, string $firma): TransaccionPago;
}
