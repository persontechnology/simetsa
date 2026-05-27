<?php
// app/Policies/RolPolicy.php

namespace App\Policies;

use App\Enums\RolSistema;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Policy de autorización para Spatie\Permission\Models\Role.
 *
 * Reglas:
 *  - viewAny / view → requieren permiso 'roles.ver'.
 *  - create → requiere permiso 'roles.crear'.
 *  - update → requiere 'roles.editar' Y el rol NO debe ser super_admin.
 *  - delete → requiere 'roles.eliminar' Y el rol NO debe ser super_admin
 *             Y el rol NO debe ser uno de los 6 del Enum (roles del sistema)
 *             Y el rol NO debe tener usuarios asignados.
 *  - super_admin tiene bypass general EXCEPTO sobre el propio rol super_admin
 *    para acciones destructivas (update/delete), donde la regla aplica a todos.
 */
class RolPolicy
{
    /**
     * Hook previo. super_admin tiene bypass para acciones de SOLO LECTURA
     * y para CREATE. Para acciones destructivas o de modificación (update,
     * delete) NUNCA se hace bypass — el método específico evalúa las reglas
     * universales del sistema (no eliminar roles del sistema, no editar
     * super_admin, no eliminar roles con usuarios asignados).
     *
     * @param  \App\Models\User  $user
     * @param  string            $ability
     * @param  mixed             ...$args
     * @return bool|null
     */
    public function before(User $user, string $ability, mixed ...$args): ?bool
    {
        if (!$user->hasRole(RolSistema::SuperAdmin->value)) {
            return null;
        }

        // Acciones destructivas/de modificación: NO hay bypass.
        // Dejá que update() y delete() apliquen las reglas universales.
        if (in_array($ability, ['update', 'delete'], true)) {
            return null;
        }

        // Bypass para viewAny, view, create y cualquier otra acción de lectura.
        return true;
    }

    /**
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.ver');
    }

    /**
     * @param  \App\Models\User                       $user
     * @param  \Spatie\Permission\Models\Role         $rol
     * @return bool
     */
    public function view(User $user, Role $rol): bool
    {
        return $user->can('roles.ver');
    }

    /**
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('roles.crear');
    }

    /**
     * Solo se puede editar si:
     *  - el usuario tiene 'roles.editar'.
     *  - el rol no es super_admin.
     *
     * @param  \App\Models\User                $user
     * @param  \Spatie\Permission\Models\Role  $rol
     * @return bool
     */
    public function update(User $user, Role $rol): bool
    {
        if ($rol->name === RolSistema::SuperAdmin->value) {
            return false;
        }
        return $user->can('roles.editar');
    }

    /**
     * Solo se puede eliminar si:
     *  - el usuario tiene 'roles.eliminar'.
     *  - el rol no es super_admin.
     *  - el rol no es uno de los 6 del Enum (roles del sistema, referenciados en código).
     *  - no hay usuarios con ese rol asignado.
     *
     * @param  \App\Models\User                $user
     * @param  \Spatie\Permission\Models\Role  $rol
     * @return bool
     */
    public function delete(User $user, Role $rol): bool
    {
        if ($this->esRolDelSistema($rol->name)) {
            return false;
        }
        if ($rol->users()->count() > 0) {
            return false;
        }
        return $user->can('roles.eliminar');
    }

    /**
     * Verifica si un nombre de rol corresponde a uno de los roles
     * fijos del sistema (referenciados en el Enum RolSistema).
     *
     * @param  string  $nombre
     * @return bool
     */
    private function esRolDelSistema(string $nombre): bool
    {
        return in_array(
            $nombre,
            array_column(RolSistema::cases(), 'value'),
            true
        );
    }
}