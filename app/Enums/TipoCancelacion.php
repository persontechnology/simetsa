<?php

/**
 * app/Enums/TipoCancelacion.php
 *
 * Discrimina el actor que origina la cancelación de un ticket:
 * el conductor de forma voluntaria o un administrador (comisario/super_admin).
 */

namespace App\Enums;

enum TipoCancelacion: string
{
    /** Cancelación voluntaria del conductor antes de que inicie la sesión. */
    case Conductor = 'conductor';

    /** Anulación administrativa por comisario o super_admin. */
    case Admin     = 'admin';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Conductor => 'Cancelación del conductor',
            self::Admin     => 'Anulación administrativa',
        };
    }
}
