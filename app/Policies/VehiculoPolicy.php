<?php
// app/Policies/VehiculoPolicy.php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehiculo;

/**
 * Policy para vehículos de conductores (Art. 25 Ordenanza SIMETSA).
 *
 * Regla de ownership: el conductor solo puede acceder a sus propios vehículos.
 * Comisario y super_admin pueden ver todos los vehículos (bypass en before()).
 */
class VehiculoPolicy
{
    /**
     * Bypass para super_admin y comisario (visibilidad total del sistema).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('comisario')) {
            return true;
        }

        return null;
    }

    /**
     * Listar vehículos — el conductor solo ve los suyos (filtrado en el controller).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehiculos.ver');
    }

    /**
     * Ver un vehículo — conductor solo puede ver los suyos.
     */
    public function view(User $user, Vehiculo $vehiculo): bool
    {
        if (! $user->can('vehiculos.ver')) {
            return false;
        }

        // Los conductores solo ven sus propios vehículos.
        if ($user->hasRole('conductor')) {
            return $vehiculo->conductor->user_id === $user->id;
        }

        return true;
    }

    /**
     * Crear vehículo — solo conductores con su propia cuenta activa.
     */
    public function create(User $user): bool
    {
        return $user->can('vehiculos.crear');
    }

    /**
     * Editar vehículo — conductor solo puede editar los suyos.
     */
    public function update(User $user, Vehiculo $vehiculo): bool
    {
        if (! $user->can('vehiculos.editar')) {
            return false;
        }

        if ($user->hasRole('conductor')) {
            return $vehiculo->conductor->user_id === $user->id;
        }

        return true;
    }

    /**
     * Eliminar vehículo — conductor solo puede eliminar los suyos (soft delete).
     */
    public function delete(User $user, Vehiculo $vehiculo): bool
    {
        if (! $user->can('vehiculos.eliminar')) {
            return false;
        }

        if ($user->hasRole('conductor')) {
            return $vehiculo->conductor->user_id === $user->id;
        }

        return true;
    }
}
