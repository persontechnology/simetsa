<?php
// app/Policies/TipoVehiculoPolicy.php

namespace App\Policies;

use App\Models\TipoVehiculo;
use App\Models\User;

/**
 * Policy para el catálogo de tipos de vehículo (Art. 25 Ordenanza SIMETSA).
 *
 * Gestión del catálogo: super_admin y director_seguridad.
 * Lectura: cualquier rol autenticado (incluyendo conductor desde la app).
 */
class TipoVehiculoPolicy
{
    /**
     * Bypass para super_admin: acceso total.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('tipos_vehiculo.ver');
    }

    public function view(User $user, TipoVehiculo $tipoVehiculo): bool
    {
        return $user->can('tipos_vehiculo.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('tipos_vehiculo.crear');
    }

    public function update(User $user, TipoVehiculo $tipoVehiculo): bool
    {
        return $user->can('tipos_vehiculo.editar');
    }

    public function delete(User $user, TipoVehiculo $tipoVehiculo): bool
    {
        return $user->can('tipos_vehiculo.eliminar');
    }
}
