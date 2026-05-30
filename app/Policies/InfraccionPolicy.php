<?php

// app/Policies/InfraccionPolicy.php

namespace App\Policies;

use App\Models\Infraccion;
use App\Models\User;

/**
 * Policy para infracciones SIMETSA.
 *
 * Roles con visibilidad total (super_admin, comisario, director_seguridad)
 * hacen bypass vía before(). El agente solo ve sus propias infracciones;
 * el conductor solo ve las de sus vehículos.
 *
 * Arts. 4 (administración), 15 (inmovilización), 38 (obligaciones del agente).
 */
class InfraccionPolicy
{
    /**
     * Bypass para roles administrativos con visibilidad total.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['super_admin', 'comisario', 'director_seguridad'])) {
            return true;
        }

        return null;
    }

    /**
     * Ver listado — agente ve las suyas; conductor ve las de su placa (filtrado en el controller).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('infracciones.ver');
    }

    /**
     * Ver una infracción — agente solo ve las suyas; conductor solo las de su placa.
     */
    public function view(User $user, Infraccion $infraccion): bool
    {
        if (! $user->can('infracciones.ver')) {
            return false;
        }

        if ($user->hasRole('agente_parqueo')) {
            return $infraccion->agente->user_id === $user->id;
        }

        if ($user->hasRole('conductor')) {
            return $infraccion->conductor_id !== null
                && $infraccion->conductor->user_id === $user->id;
        }

        return true;
    }

    /**
     * Registrar infracción — solo agentes activos (Art. 38.l).
     */
    public function create(User $user): bool
    {
        return $user->can('infracciones.registrar');
    }

    /**
     * Anular infracción — solo comisario y super_admin (bypass en before()).
     */
    public function anular(User $user, Infraccion $infraccion): bool
    {
        return $user->can('infracciones.registrar');
    }
}
