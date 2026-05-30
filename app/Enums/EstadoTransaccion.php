<?php

// app/Enums/EstadoTransaccion.php

namespace App\Enums;

/**
 * Estados de ciclo de vida de una TransaccionPago.
 */
enum EstadoTransaccion: string
{
    /** Transacción creada, esperando confirmación del gateway. */
    case Pendiente   = 'pendiente';

    /** El gateway está procesando el pago. */
    case Procesando  = 'procesando';

    /** Pago confirmado exitosamente. */
    case Completada  = 'completada';

    /** El pago falló o fue rechazado. */
    case Fallida     = 'fallida';

    /** El pago fue reembolsado. */
    case Reembolsada = 'reembolsada';

    /** Etiqueta legible para humanos. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente   => 'Pendiente',
            self::Procesando  => 'Procesando',
            self::Completada  => 'Completada',
            self::Fallida     => 'Fallida',
            self::Reembolsada => 'Reembolsada',
        };
    }

    /** Color Bootstrap para badges. */
    public function color(): string
    {
        return match ($this) {
            self::Pendiente   => 'secondary',
            self::Procesando  => 'info',
            self::Completada  => 'success',
            self::Fallida     => 'danger',
            self::Reembolsada => 'warning',
        };
    }

    /** True si la transacción está en un estado terminal (no cambia más). */
    public function esTerminal(): bool
    {
        return in_array($this, [self::Completada, self::Fallida, self::Reembolsada], true);
    }
}
