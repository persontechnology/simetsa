<?php
// app/Traits/TienePerfilUsuario.php

namespace App\Traits;

use App\Models\PerfilUsuario;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait que se agrega al modelo User para exponer su PerfilUsuario.
 *
 * Mantiene aislada la extensión del SIMETSA respecto al modelo User base,
 * sin modificar la tabla `users` ni la lógica de autenticación de Breeze.
 */
trait TienePerfilUsuario
{
    /**
     * Relación 1:1 con PerfilUsuario.
     * Permite acceder a los datos extendidos vía `$user->perfil`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function perfil(): HasOne
    {
        return $this->hasOne(PerfilUsuario::class, 'user_id');
    }

    /**
     * Verifica si el usuario tiene perfil completo y consintió los términos
     * (requisito LOPDP para operar dentro del SIMETSA).
     *
     * @return bool
     */
    public function tienePerfilCompleto(): bool
    {
        return $this->perfil !== null && $this->perfil->acepta_terminos;
    }

    /**
     * Devuelve la cédula del usuario o null si aún no tiene perfil.
     * Atajo cómodo para vistas y reportes.
     *
     * @return string|null
     */
    public function cedula(): ?string
    {
        return $this->perfil?->cedula;
    }
}