<?php

// app/Enums/EstadoReembolso.php

namespace App\Enums;

/**
 * Estado del reembolso al cancelar un ticket pagado con proveedor digital.
 *
 * Art. 21 Ordenanza SIMETSA: los montos recaudados siguen la distribución
 * 60/40 (agentes) y 90/10 (puntos de venta); los reembolsos revierten esta
 * distribución según el proveedor utilizado.
 */
enum EstadoReembolso: string
{
    /** Pago en efectivo o ticket exonerado — no aplica reembolso digital. */
    case NoAplica  = 'no_aplica';

    /** Pago digital confirmado; reembolso pendiente de procesar en el gateway. */
    case Pendiente = 'pendiente';

    /** Reembolso emitido y confirmado por el gateway. */
    case Procesado = 'procesado';

    /** Intento de reembolso falló en el gateway; requiere intervención manual. */
    case Fallido   = 'fallido';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::NoAplica  => 'No aplica',
            self::Pendiente => 'Pendiente',
            self::Procesado => 'Procesado',
            self::Fallido   => 'Fallido',
        };
    }

    /** Color Bootstrap para badges. */
    public function color(): string
    {
        return match ($this) {
            self::NoAplica  => 'secondary',
            self::Pendiente => 'warning',
            self::Procesado => 'success',
            self::Fallido   => 'danger',
        };
    }
}
