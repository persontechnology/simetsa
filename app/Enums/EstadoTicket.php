<?php

/**
 * app/Enums/EstadoTicket.php
 *
 * Máquina de estados del Ticket digital SIMETSA.
 * Art. 13 (tolerancia), Art. 14 (máx 2h), Art. 17 (infracciones).
 */

namespace App\Enums;

enum EstadoTicket: string
{
    /** Ticket comprado, sesión de parqueo aún no iniciada por el agente. */
    case Pendiente    = 'pendiente';

    /** Sesión iniciada y tiempo vigente. */
    case Activo       = 'activo';

    /** Tiempo vencido pero dentro del margen de 5 min (Art. 13). */
    case EnTolerancia = 'en_tolerancia';

    /** Tiempo vencido y fuera del margen de tolerancia. */
    case Expirado     = 'expirado';

    /** Cancelado voluntariamente por el conductor antes de iniciar sesión. */
    case Cancelado    = 'cancelado';

    /** Anulado administrativamente por comisario o super_admin. */
    case Anulado      = 'anulado';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente    => 'Pendiente',
            self::Activo       => 'Activo',
            self::EnTolerancia => 'En tolerancia',
            self::Expirado     => 'Expirado',
            self::Cancelado    => 'Cancelado',
            self::Anulado      => 'Anulado',
        };
    }

    /** Color Bootstrap para badges. */
    public function color(): string
    {
        return match ($this) {
            self::Pendiente    => 'secondary',
            self::Activo       => 'success',
            self::EnTolerancia => 'warning',
            self::Expirado     => 'danger',
            self::Cancelado    => 'dark',
            self::Anulado      => 'danger',
        };
    }

    /** Indica si el ticket puede ser cancelado por el conductor (antes de sesión). */
    public function esCancelable(): bool
    {
        return $this === self::Pendiente;
    }

    /** Indica si el ticket puede ser anulado administrativamente. */
    public function esAnulable(): bool
    {
        return in_array($this, [self::Pendiente, self::Activo, self::EnTolerancia]);
    }
}
