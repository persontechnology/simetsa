<?php

// app/Policies/SesionParqueoPolicy.php

namespace App\Policies;

use App\Models\SesionParqueo;
use App\Models\User;

/**
 * Policy para sesiones de parqueo.
 *
 * Solo agentes, comisario, director y super_admin pueden acceder.
 * Los conductores no tienen acceso directo a las sesiones.
 */
class SesionParqueoPolicy
{
    /**
     * Bypass para roles con visibilidad total del sistema.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['super_admin', 'comisario', 'director_seguridad'])) {
            return true;
        }

        return null;
    }

    /**
     * Listar sesiones — agentes y supervisores.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('sesiones_parqueo.ver');
    }

    /**
     * Ver una sesión — agentes y supervisores.
     */
    public function view(User $user, SesionParqueo $sesionParqueo): bool
    {
        return $user->can('sesiones_parqueo.ver');
    }

    /**
     * Iniciar sesión de parqueo — solo agentes activos en calle.
     */
    public function create(User $user): bool
    {
        return $user->can('sesiones_parqueo.iniciar');
    }
}
