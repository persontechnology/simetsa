<?php

// app/Policies/InmovilizacionPolicy.php

namespace App\Policies;

use App\Models\Inmovilizacion;
use App\Models\User;

/**
 * Policy para inmovilizaciones de vehículos.
 *
 * La inmovilización la ejecuta el agente autorizado; el retiro del candado
 * requiere pago previo de la infracción (Art. 15).
 */
class InmovilizacionPolicy
{
    /**
     * Bypass para roles administrativos.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['super_admin', 'comisario', 'director_seguridad'])) {
            return true;
        }

        return null;
    }

    /**
     * Ver inmovilización — agente solo ve las suyas; conductor si está registrado.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inmovilizaciones.ver');
    }

    /**
     * Ver una inmovilización específica.
     */
    public function view(User $user, Inmovilizacion $inmovilizacion): bool
    {
        if (! $user->can('inmovilizaciones.ver')) {
            return false;
        }

        if ($user->hasRole('agente_parqueo')) {
            return $inmovilizacion->agente->user_id === $user->id;
        }

        return true;
    }

    /**
     * Aplicar candado (crear inmovilización) — solo agentes (Art. 15).
     */
    public function create(User $user): bool
    {
        return $user->can('inmovilizaciones.aplicar');
    }

    /**
     * Retirar candado — solo agentes o admin (Art. 15: tras pago confirmado).
     */
    public function retirar(User $user, Inmovilizacion $inmovilizacion): bool
    {
        return $user->can('inmovilizaciones.retirar');
    }
}
