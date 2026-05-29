<?php
// app/Policies/VehiculoExoneradoPolicy.php

namespace App\Policies;

use App\Models\User;
use App\Models\VehiculoExonerado;

/**
 * Policy de vehículos exonerados (Art. 27 Ordenanza SIMETSA).
 *
 * Solo comisario, director y super_admin pueden gestionar exoneraciones.
 * El conductor no tiene acceso a este módulo.
 */
class VehiculoExoneradoPolicy
{
    /**
     * Bypass para super_admin y comisario (gestión total del sistema).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('comisario')) {
            return true;
        }

        return null;
    }

    /**
     * @see Art. 27 Ordenanza SIMETSA.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehiculos_exonerados.ver');
    }

    /**
     * @see Art. 27 Ordenanza SIMETSA.
     */
    public function create(User $user): bool
    {
        return $user->can('vehiculos_exonerados.crear');
    }

    /**
     * @see Art. 27 Ordenanza SIMETSA.
     */
    public function update(User $user, VehiculoExonerado $vehiculoExonerado): bool
    {
        return $user->can('vehiculos_exonerados.editar');
    }

    /**
     * @see Art. 27 Ordenanza SIMETSA.
     */
    public function delete(User $user, VehiculoExonerado $vehiculoExonerado): bool
    {
        return $user->can('vehiculos_exonerados.eliminar');
    }
}
