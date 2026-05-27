<?php
// app/Services/RolService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Servicio de gestión de roles del SIMETSA.
 *
 * Centraliza la creación, actualización y eliminación de roles de Spatie
 * dentro de DB::transaction y se encarga de invalidar la caché de permisos
 * tras cada cambio (crítico para que los nuevos permisos sean visibles
 * inmediatamente en la sesión activa).
 */
class RolService
{
    /**
     * Crea un nuevo rol y le asigna los permisos indicados.
     *
     * @param  array<string, mixed>  $datos  Datos validados ['name', 'permisos']
     * @return \Spatie\Permission\Models\Role
     */
    public function crear(array $datos): Role
    {
        return DB::transaction(function () use ($datos) {
            $rol = Role::create([
                'name'       => $datos['name'],
                'guard_name' => 'web',
            ]);

            if (!empty($datos['permisos'])) {
                $rol->syncPermissions($datos['permisos']);
            }

            $this->invalidarCachePermisos();
            return $rol->fresh('permissions');
        });
    }

    /**
     * Actualiza un rol existente: nombre (si no es del sistema)
     * y sincronización completa de permisos.
     *
     * @param  \Spatie\Permission\Models\Role  $rol
     * @param  array<string, mixed>            $datos
     * @return \Spatie\Permission\Models\Role
     */
    public function actualizar(Role $rol, array $datos): Role
    {
        return DB::transaction(function () use ($rol, $datos) {
            // El controlador decide si permitir cambiar el nombre.
            // Si llega en $datos, se actualiza; si no, se preserva.
            if (isset($datos['name']) && $datos['name'] !== $rol->name) {
                $rol->update(['name' => $datos['name']]);
            }

            // Sincronización completa de permisos (agrega + quita)
            $rol->syncPermissions($datos['permisos'] ?? []);

            $this->invalidarCachePermisos();
            return $rol->fresh('permissions');
        });
    }

    /**
     * Elimina un rol. Lanza excepción si se intenta eliminar un rol del
     * sistema (última línea de defensa antes de tocar la BD).
     *
     * @param  \Spatie\Permission\Models\Role  $rol
     * @return void
     *
     * @throws \DomainException  Si se intenta eliminar un rol del Enum RolSistema.
     */
    public function eliminar(Role $rol): void
    {
        // Defensa de último recurso: bloquear roles del sistema sí o sí.
        $rolesDelSistema = array_column(\App\Enums\RolSistema::cases(), 'value');
        if (in_array($rol->name, $rolesDelSistema, true)) {
            throw new \DomainException(
                "El rol '{$rol->name}' es del sistema y no puede eliminarse."
            );
        }

        DB::transaction(function () use ($rol) {
            $rol->permissions()->detach();
            $rol->delete();
            $this->invalidarCachePermisos();
        });
    }

    /**
     * Limpia la caché de permisos de Spatie para que los cambios
     * sean visibles sin necesidad de re-loguear.
     *
     * @return void
     */
    private function invalidarCachePermisos(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}