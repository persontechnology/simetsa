<?php

/**
 * app/Enums/EstadoInmovilizacion.php
 *
 * Estado del candado inmovilizador (Art. 15).
 */

namespace App\Enums;

enum EstadoInmovilizacion: string
{
    /** Candado colocado; vehículo inmovilizado. */
    case Activa = 'activa';

    /** Candado retirado tras pago de la infracción (Art. 15). */
    case Liberada = 'liberada';

    /** Inmovilización anulada administrativamente (error, improcedente). */
    case Anulada = 'anulada';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa   => 'Activa',
            self::Liberada => 'Liberada',
            self::Anulada  => 'Anulada',
        };
    }

    /** Color Bootstrap para badges. */
    public function color(): string
    {
        return match ($this) {
            self::Activa   => 'danger',
            self::Liberada => 'success',
            self::Anulada  => 'secondary',
        };
    }
}
