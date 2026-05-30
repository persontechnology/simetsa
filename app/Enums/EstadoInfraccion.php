<?php

/**
 * app/Enums/EstadoInfraccion.php
 *
 * Ciclo de vida de una infracción SIMETSA.
 * Arts. 15, 28, 29, 30.
 */

namespace App\Enums;

enum EstadoInfraccion: string
{
    /** Infracción registrada; multa pendiente de pago. */
    case Pendiente = 'pendiente';

    /** Multa pagada; inmovilización (si existía) liberada (Art. 15). */
    case Pagada = 'pagada';

    /** Anulada administrativamente por comisario o super_admin. */
    case Anulada = 'anulada';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de pago',
            self::Pagada    => 'Pagada',
            self::Anulada   => 'Anulada',
        };
    }

    /** Color Bootstrap para badges. */
    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Pagada    => 'success',
            self::Anulada   => 'secondary',
        };
    }

    /** Indica si aún se puede pagar la multa. */
    public function esPagable(): bool
    {
        return $this === self::Pendiente;
    }

    /** Indica si puede ser anulada administrativamente. */
    public function esAnulable(): bool
    {
        return $this === self::Pendiente;
    }
}
