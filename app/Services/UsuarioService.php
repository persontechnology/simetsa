<?php
// app/Services/UsuarioService.php

namespace App\Services;

use App\Models\PerfilUsuario;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio de gestión de usuarios del SIMETSA.
 *
 * Centraliza las operaciones multi-tabla User + PerfilUsuario + Spatie roles
 * dentro de transacciones de BD, manteniendo el UsuarioController delgado.
 *
 * Convención: todos los métodos públicos retornan el User fresco con
 * sus relaciones cargadas para consumo inmediato por el controlador.
 */
class UsuarioService
{
    /**
     * Crea un nuevo usuario con su PerfilUsuario y le asigna el rol indicado.
     *
     * @param  array<string, mixed>  $datos  Datos validados de UsuarioStoreRequest
     * @return \App\Models\User              Usuario creado con perfil y rol
     */
    public function crear(array $datos): User
    {
        return DB::transaction(function () use ($datos) {

            // 1) Crear el User
            $user = User::create([
                'name'              => $datos['name'],
                'email'             => $datos['email'],
                'password'          => Hash::make($datos['password']),
                // Auto-verificado: lo crea un administrador, no requiere
                // confirmación por email.
                'email_verified_at' => now(),
            ]);

            // 2) Procesar foto de perfil si viene en los datos
            $rutaFoto = $this->procesarFotoPerfil($datos['foto_perfil'] ?? null);

            // 3) Crear el PerfilUsuario asociado
            $this->crearPerfil($user, $datos);
            
            // 4) Asignar el rol Spatie (uno solo en SIMETSA)
            $user->syncRoles($datos['roles']);

            return $user->fresh(['perfil', 'roles']);
        });
    }

    /**
     * Crea el `PerfilUsuario` para un `User` existente.
     *
     * @param  \App\Models\User  $user
     * @param  array<string,mixed>  $datos
     * @return \App\Models\PerfilUsuario
     */
    public function crearPerfil(User $user, array $datos): PerfilUsuario
    {
        if (PerfilUsuario::where('cedula', $datos['cedula'])->exists()) {
            throw new DomainException('Ya existe un perfil asociado a esa cédula.');
        }

        $rutaFoto = $this->procesarFotoPerfil($datos['foto_perfil'] ?? null);

        return $user->perfil()->create([
            'cedula' => $datos['cedula'],
            'telefono' => $datos['telefono'] ?? null,
            'telefono_celular' => $datos['telefono_celular'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
            'genero' => $datos['genero'] ?? null,
            'foto_perfil' => $rutaFoto,
            'acepta_terminos' => $datos['acepta_terminos'] ?? false,
            'fecha_aceptacion_terminos' => ($datos['acepta_terminos'] ?? false) ? now() : null,
            'activo' => $datos['activo'] ?? true,
        ]);
    }

    /**
     * Actualiza un usuario existente, su perfil y opcionalmente su rol.
     * La contraseña se actualiza solo si viene en los datos.
     *
     * @param  \App\Models\User      $user
     * @param  array<string, mixed>  $datos
     * @return \App\Models\User
     */
    public function actualizar(User $user, array $datos): User
    {
        return DB::transaction(function () use ($user, $datos) {
            $perfil = $this->perfilConTrashed($user);

            // 1) Actualizar campos del User
            $datosUser = [
                'name'  => $datos['name'],
                'email' => $datos['email'],
            ];
            if (!empty($datos['password'])) {
                $datosUser['password'] = Hash::make($datos['password']);
            }
            $user->update($datosUser);

            // 2) Procesar foto de perfil (reemplazo de la anterior si aplica)
            $rutaFoto = $perfil->foto_perfil;
            if (isset($datos['foto_perfil'])) {
                $rutaFoto = $this->procesarFotoPerfil($datos['foto_perfil'], $perfil->foto_perfil);
            }

            // 3) Actualizar el PerfilUsuario asociado
            $perfil->update([
                'cedula'           => $datos['cedula'],
                'telefono'         => $datos['telefono'] ?? null,
                'telefono_celular' => $datos['telefono_celular'] ?? null,
                'direccion'        => $datos['direccion'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
                'genero'           => $datos['genero'] ?? null,
                'foto_perfil'      => $rutaFoto,
            ]);

            // 4) Sincronizar rol si viene en los datos
            if (!empty($datos['roles'])) {
                $user->syncRoles($datos['roles']);
            }

            return $user->fresh(['perfil', 'roles']);
        });
    }

    /**
     * Desactiva un usuario (soft delete del perfil + marcar como inactivo).
     * 
     * NO elimina físicamente el registro de `users` para preservar
     * integridad referencial con auditoría, tickets e historial de accesos.
     * 
     * El soft delete del perfil marca deleted_at, y el flag activo=false
     * proporciona validación redundante para consultas comunes.
     *
     * @param  \App\Models\User  $user
     * @return void
     * @throws \Exception Si el usuario no tiene perfil asociado
     */
    public function desactivar(User $user): void
    {
        DB::transaction(function () use ($user) {
            $perfil = $this->perfilConTrashed($user);

            // Actualizar activo y hacer soft delete en una sola operación
            $perfil->update(['activo' => false]);
            $perfil->delete();
        });
    }

    /**
     * Reactiva un usuario previamente desactivado.
     * 
     * Restaura el perfil (deshace soft delete) y lo marca como activo.
     * Ambas operaciones son idempotentes dentro de la transacción.
     *
     * @param  \App\Models\User  $user
     * @return void
     * @throws \Exception Si el usuario no tiene un perfil eliminado (soft deleted)
     */
    public function reactivar(User $user): void
    {
        DB::transaction(function () use ($user) {
            $perfil = PerfilUsuario::onlyTrashed()
                ->where('user_id', $user->id)
                ->first();

            if (!$perfil) {
                throw new \Exception(
                    "El usuario no puede ser reactivado: no tiene un perfil eliminado."
                );
            }

            // Restaurar el perfil (deshace soft delete)
            $perfil->restore();
            
            // Marcar como activo
            $perfil->update(['activo' => true]);
        });
    }
    
    /**
     * Verifica si un usuario está activo (no está soft deleted y activo=true).
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function estaActivo(User $user): bool
    {
        $perfil = $user->perfil;
        return $perfil && !$perfil->trashed() && $perfil->activo;
    }

    /**
     * Obtiene el PerfilUsuario asociado incluso cuando está soft deleted.
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\PerfilUsuario
     * @throws \DomainException Si el usuario no tiene perfil registrado
     */
    private function perfilConTrashed(User $user): PerfilUsuario
    {
        $perfil = $user->perfil()->withTrashed()->first();

        if (!$perfil) {
            throw new DomainException('El usuario no tiene un perfil asociado.');
        }

        return $perfil;
    }

    /**
     * Guarda el archivo de foto subido y devuelve la ruta relativa.
     * Si se está reemplazando una foto previa, la elimina del storage.
     *
     * @param  mixed         $archivo        Archivo subido (Illuminate UploadedFile) o null
     * @param  string|null   $rutaAnterior   Ruta de la foto anterior a eliminar (opcional)
     * @return string|null                   Ruta relativa guardada o null si no hay archivo
     */
    private function procesarFotoPerfil($archivo, ?string $rutaAnterior = null): ?string
    {
        if (!$archivo) {
            return $rutaAnterior;
        }

        // Elimina la foto anterior si existía
        if ($rutaAnterior && Storage::disk('public')->exists($rutaAnterior)) {
            Storage::disk('public')->delete($rutaAnterior);
        }

        // Guarda la nueva foto en storage/app/public/perfiles
        return $archivo->store('perfiles', 'public');
    }
}