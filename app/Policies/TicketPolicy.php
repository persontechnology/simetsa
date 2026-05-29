<?php

// app/Policies/TicketPolicy.php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * Policy para tickets digitales de parqueo.
 *
 * Regla de ownership: el conductor solo puede acceder a sus propios tickets.
 * Comisario, director_seguridad y super_admin tienen visibilidad total.
 */
class TicketPolicy
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
     * Listar tickets — conductor solo ve los suyos (filtrado en el controller).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.ver');
    }

    /**
     * Ver un ticket — conductor solo puede ver los suyos.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if (! $user->can('tickets.ver')) {
            return false;
        }

        if ($user->hasRole('conductor')) {
            return $ticket->conductor->user_id === $user->id;
        }

        return true;
    }

    /**
     * Comprar un ticket (crear) — solo conductores activos.
     */
    public function create(User $user): bool
    {
        return $user->can('tickets.comprar');
    }

    /**
     * Cancelar ticket — solo el conductor propietario antes de iniciar sesión.
     */
    public function cancelar(User $user, Ticket $ticket): bool
    {
        if (! $user->can('tickets.cancelar')) {
            return false;
        }

        return $ticket->conductor->user_id === $user->id;
    }

    /**
     * Anular ticket administrativamente — comisario y super_admin (bypass en before()).
     */
    public function anular(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.anular');
    }
}
