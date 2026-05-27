<?php
// app/Listeners/RegistrarAccesoListener.php

namespace App\Listeners;

use App\Models\RegistroAcceso;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Listener que registra cada evento de autenticación en la tabla
 * `registros_acceso` para fines de auditoría LOPDP.
 *
 * Métodos:
 *  - handleLogin   → escucha Illuminate\Auth\Events\Login
 *  - handleLogout  → escucha Illuminate\Auth\Events\Logout
 *  - handleFailed  → escucha Illuminate\Auth\Events\Failed (credenciales inválidas)
 *  - handleLockout → escucha Illuminate\Auth\Events\Lockout (throttling)
 *
 * Diseño resiliente: las fallas de escritura no rompen el flujo de auth
 * (se loguean a stderr/laravel.log y continúan).
 */
class RegistrarAccesoListener
{
    /**
     * Login exitoso: registra el evento con el user_id.
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handleLogin(Login $event): void
    {
        $this->registrar(
            evento: RegistroAcceso::EVENTO_LOGIN,
            user:   $event->user,
        );
    }

    /**
     * Logout: registra el evento con el user_id.
     *
     * @param  \Illuminate\Auth\Events\Logout  $event
     * @return void
     */
    public function handleLogout(Logout $event): void
    {
        $this->registrar(
            evento: RegistroAcceso::EVENTO_LOGOUT,
            user:   $event->user,
        );
    }

    /**
     * Intento fallido: registra el email intentado.
     * $event->user puede ser null si el email no coincide con ningún usuario.
     *
     * @param  \Illuminate\Auth\Events\Failed  $event
     * @return void
     */
    public function handleFailed(Failed $event): void
    {
        $this->registrar(
            evento:       RegistroAcceso::EVENTO_FALLIDO,
            user:         $event->user,
            emailIntento: $event->credentials['email'] ?? null,
        );
    }

    /**
     * Bloqueo por throttling (tras N intentos fallidos consecutivos).
     * Captura el email desde el request actual.
     *
     * @param  \Illuminate\Auth\Events\Lockout  $event
     * @return void
     */
    public function handleLockout(Lockout $event): void
    {
        $this->registrar(
            evento:       RegistroAcceso::EVENTO_BLOQUEO,
            emailIntento: $event->request->input('email'),
        );
    }

    /**
     * Inserta el registro en BD. Tolerante a fallos: si la BD no responde,
     * loguea pero no propaga la excepción para no romper la autenticación.
     *
     * @param  string                                            $evento
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string|null                                       $emailIntento
     * @return void
     */
    private function registrar(
        string $evento,
        ?Authenticatable $user = null,
        ?string $emailIntento = null
    ): void {
        try {
            RegistroAcceso::create([
                'user_id'       => $user?->getAuthIdentifier(),
                'email_intento' => $emailIntento ?? $user?->getAuthIdentifierName() === 'email'
                                    ? ($emailIntento ?? $user?->email)
                                    : $emailIntento,
                'evento'        => $evento,
                'ip'            => request()?->ip(),
                'user_agent'    => request()?->userAgent(),
                'ocurrido_en'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail-safe: nunca interrumpir el flujo de auth por una falla
            // en la auditoría. Loguear y continuar.
            logger()->error('Error al registrar acceso en auditoría', [
                'evento'        => $evento,
                'user_id'       => $user?->getAuthIdentifier(),
                'email_intento' => $emailIntento,
                'exception'     => $e->getMessage(),
            ]);
        }
    }
}