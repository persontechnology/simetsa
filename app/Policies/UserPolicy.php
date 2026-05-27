<?php
// app/Policies/UserPolicy.php

namespace App\Policies;

use App\Enums\RolSistema;
use App\Models\User;

/**
 * Policy de autorización para el modelo User.
 *
 * Jerarquía de evaluación:
 *  1) before() evalúa primero las REGLAS UNIVERSALES que aplican a TODOS
 *     los roles, incluyendo super_admin (auto-eliminación, auto-asignación
 *     de roles). Esto previene quedar sin administradores y bloquea
 *     escaladas/desescaladas de privilegios por parte del propio usuario.
 *  2) Si pasan las reglas universales y el usuario es super_admin,
 *     before() aprueba (bypass del resto de comprobaciones).
 *  3) Caso contrario, se ejecuta el método específico de la policy.
 *
 * Cumple con el principio LOPDP de control de acceso granular (mínimo
 * privilegio) y con la buena práctica de evitar que cualquier usuario
 * pueda autoeliminarse o degradarse a sí mismo.
 */
class UserPolicy
{
    /**
     * Hook que se ejecuta ANTES de cualquier método de la policy.
     *
     * Primero aplica reglas universales (válidas incluso para super_admin)
     * y luego, si corresponde, el bypass del super_admin.
     *
     * @param  \App\Models\User  $user     Usuario autenticado
     * @param  string            $ability  Habilidad solicitada (viewAny, view, ...)
     * @param  mixed             ...$args  Argumentos adicionales (típicamente el modelo)
     * @return bool|null                   true=aprobar, false=denegar, null=evaluar método
     */
    public function before(User $user, string $ability, mixed ...$args): ?bool
    {
        // ===== Reglas universales (aplican a TODOS los roles) =====

        // U1) Nadie puede eliminarse a sí mismo (ni siquiera super_admin).
        //     Evita quedar sin administradores en el sistema.
        if ($ability === 'delete' && $this->actuaSobreSiMismo($user, $args)) {
            return false;
        }

        // U2) Nadie puede modificar sus propios roles (ni siquiera super_admin).
        //     Bloquea escalada de privilegios y, sobre todo, evita que un
        //     super_admin se quite el rol y deje al sistema sin admin.
        if ($ability === 'assignRole' && $this->actuaSobreSiMismo($user, $args)) {
            return false;
        }

        // ===== Bypass del super_admin =====
        if ($user->hasRole(RolSistema::SuperAdmin->value)) {
            return true;
        }

        return null;
    }

    /**
     * Verifica si la acción se está ejecutando contra el propio usuario.
     * Inspecciona el primer argumento esperando una instancia de User.
     *
     * @param  \App\Models\User    $user  Usuario que ejecuta la acción
     * @param  array<int, mixed>   $args  Argumentos pasados a la habilidad
     * @return bool
     */
    private function actuaSobreSiMismo(User $user, array $args): bool
    {
        return isset($args[0])
            && $args[0] instanceof User
            && $args[0]->id === $user->id;
    }

    /**
     * ¿Puede ver el listado de usuarios?
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.ver');
    }

    /**
     * ¿Puede ver el detalle de un usuario específico?
     * Todo usuario puede verse a sí mismo aunque no tenga el permiso.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return bool
     */
    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }
        return $user->can('usuarios.ver');
    }

    /**
     * ¿Puede crear nuevos usuarios?
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('usuarios.crear');
    }

    /**
     * ¿Puede editar a $model?
     *
     * - Todo usuario puede editarse a sí mismo (perfil propio).
     * - Nadie distinto a super_admin puede editar a un super_admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        if ($model->hasRole(RolSistema::SuperAdmin->value)) {
            return false;
        }

        return $user->can('usuarios.editar');
    }

    /**
     * ¿Puede eliminar a $model?
     *
     * - La auto-eliminación ya está bloqueada en before() para TODOS los roles
     *   (defensa centralizada). Se mantiene también aquí como defense-in-depth
     *   por si se invoca la policy directamente sin pasar por Gate.
     * - Nadie distinto a super_admin puede eliminar a un super_admin.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        // Defense-in-depth: la regla principal está en before()
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->hasRole(RolSistema::SuperAdmin->value)) {
            return false;
        }

        return $user->can('usuarios.eliminar');
    }

    /**
     * ¿Puede asignar o cambiar los roles de $model?
     *
     * - La auto-modificación de roles ya está bloqueada en before() para
     *   TODOS los roles. Se mantiene también aquí como defense-in-depth.
     * - Nadie distinto a super_admin puede tocar los roles de un super_admin.
     *
     * NOTA: la restricción "solo super_admin puede otorgar el rol super_admin"
     * se controla con el Gate 'asignar-rol-super-admin' en AppServiceProvider.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return bool
     */
    public function assignRole(User $user, User $model): bool
    {
        // Defense-in-depth: la regla principal está en before()
        if ($user->id === $model->id) {
            return false;
        }

        if ($model->hasRole(RolSistema::SuperAdmin->value)) {
            return false;
        }

        return $user->can('roles.asignar');
    }
}