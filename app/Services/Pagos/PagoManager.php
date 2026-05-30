<?php

// app/Services/Pagos/PagoManager.php

namespace App\Services\Pagos;

use App\Contracts\PaymentProviderInterface;
use DomainException;

/**
 * Resuelve y gestiona los proveedores de pago registrados.
 *
 * Se registra como singleton en AppServiceProvider.
 * Los servicios que necesitan cobrar inyectan este manager por constructor.
 */
class PagoManager
{
    /** @param  array<string, PaymentProviderInterface>  $providers */
    public function __construct(private readonly array $providers)
    {
    }

    /**
     * Devuelve el proveedor por nombre (ej. 'deuna', 'pagomedios').
     *
     * @throws DomainException  Si el proveedor no está registrado.
     */
    public function proveedor(string $nombre): PaymentProviderInterface
    {
        if (! isset($this->providers[$nombre])) {
            throw new DomainException("Proveedor de pago '{$nombre}' no está registrado.");
        }

        return $this->providers[$nombre];
    }

    /**
     * Devuelve el proveedor predeterminado según config('pagos.default_provider').
     *
     * @throws DomainException  Si el proveedor predeterminado no está registrado.
     */
    public function predeterminado(): PaymentProviderInterface
    {
        return $this->proveedor(config('pagos.default_provider', 'deuna'));
    }

    /** Lista de nombres de proveedores registrados. */
    public function nombresRegistrados(): array
    {
        return array_keys($this->providers);
    }
}
