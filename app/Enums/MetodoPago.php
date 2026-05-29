<?php

/**
 * app/Enums/MetodoPago.php
 *
 * Métodos de pago disponibles en el sistema de tickets.
 * PayPhone se integra en Fase 6; aquí se define la estructura.
 */

namespace App\Enums;

enum MetodoPago: string
{
    /** Pago en efectivo al agente o punto de venta. */
    case Efectivo     = 'efectivo';

    /** Pago simulado para pruebas y desarrollo (Fase 5). */
    case PagoSimulado = 'pago_simulado';

    /** PayPhone — tarjeta (Fase 6). */
    case Payphone     = 'payphone';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo     => 'Efectivo',
            self::PagoSimulado => 'Pago simulado',
            self::Payphone     => 'PayPhone',
        };
    }

    /** Métodos activos en Fase 5 (antes de integración PayPhone). */
    public static function disponibles(): array
    {
        return [self::Efectivo, self::PagoSimulado];
    }
}
