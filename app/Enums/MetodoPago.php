<?php

/**
 * app/Enums/MetodoPago.php
 *
 * Medios de pago disponibles en el sistema de tickets (canal neutro).
 * El proveedor gateway se registra por separado en el campo `proveedor`
 * (ver App\Enums\ProveedorPago).
 *
 * Fase 6: se eliminó Payphone; se agregan Link, Qr, Tarjeta, Saldo.
 * PagoSimulado permanece con gate de entorno (solo local/testing/staging).
 */

namespace App\Enums;

enum MetodoPago: string
{
    /** Pago en efectivo al agente o punto de venta. */
    case Efectivo     = 'efectivo';

    /** URL de pago generada por el gateway (Deuna link de cobro). */
    case Link         = 'link';

    /** Código QR generado por el gateway. */
    case Qr           = 'qr';

    /** Tarjeta de crédito/débito vía gateway (reservado). */
    case Tarjeta      = 'tarjeta';

    /** Saldo de billetera digital (reservado). */
    case Saldo        = 'saldo';

    /** Pago simulado para desarrollo y tests — solo en entornos no-productivos. */
    case PagoSimulado = 'pago_simulado';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo     => 'Efectivo',
            self::Link         => 'Link de pago',
            self::Qr           => 'Código QR',
            self::Tarjeta      => 'Tarjeta',
            self::Saldo        => 'Saldo digital',
            self::PagoSimulado => 'Pago simulado',
        };
    }

    /**
     * Métodos disponibles para mostrar en la app y validar en requests.
     *
     * PagoSimulado está gateado por entorno: solo en local, testing y staging.
     *
     * @return self[]
     */
    public static function disponibles(): array
    {
        $base = [self::Efectivo, self::Link, self::Qr];

        if (in_array(app()->environment(), ['local', 'testing', 'staging'], true)) {
            $base[] = self::PagoSimulado;
        }

        return $base;
    }

    /** True si el método requiere un proveedor digital externo. */
    public function requiereGateway(): bool
    {
        return in_array($this, [self::Link, self::Qr, self::Tarjeta, self::Saldo], true);
    }
}
