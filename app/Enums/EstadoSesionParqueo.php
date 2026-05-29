<?php

/**
 * app/Enums/EstadoSesionParqueo.php
 *
 * Estado de la sesión de parqueo iniciada por el agente en calle.
 */

namespace App\Enums;

enum EstadoSesionParqueo: string
{
    /** El agente inició la sesión y el tiempo aún no ha vencido. */
    case Activa     = 'activa';

    /** La sesión finalizó dentro del tiempo comprado. */
    case Finalizada = 'finalizada';

    /** El vehículo permaneció más allá del tiempo comprado (Art. 17). */
    case Excedida   = 'excedida';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa     => 'Activa',
            self::Finalizada => 'Finalizada',
            self::Excedida   => 'Excedida',
        };
    }

    /** Color Bootstrap para badges. */
    public function color(): string
    {
        return match ($this) {
            self::Activa     => 'success',
            self::Finalizada => 'secondary',
            self::Excedida   => 'danger',
        };
    }
}
