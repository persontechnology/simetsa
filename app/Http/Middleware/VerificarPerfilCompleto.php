<?php
// app/Http/Middleware/VerificarPerfilCompleto.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica que el usuario autenticado tenga PerfilUsuario
 * completo y haya aceptado los términos LOPDP.
 *
 * Comportamiento:
 *  - Si no hay usuario autenticado: deja pasar (la auth se maneja con `auth`).
 *  - Si el perfil está completo: deja pasar.
 *  - Si la petición espera JSON (app móvil / API): responde 403 con el formato
 *    estándar del SIMETSA.
 *  - Si es web: redirige a la ruta `perfil.completar`, excepto cuando ya está
 *    en esa ruta o cerrando sesión (para evitar loops).
 *
 * Cumple con el Art. 7 LOPDP: ningún dato personal puede procesarse sin
 * consentimiento informado.
 */
class VerificarPerfilCompleto
{
    /**
     * Rutas que SIEMPRE deben dejarse pasar aunque el perfil esté incompleto,
     * para que el usuario pueda completarlo o cerrar sesión.
     *
     * @var array<int, string>
     */
    private array $rutasPermitidas = [
        'perfil.*',
        'logout',
    ];

    /**
     * Maneja la petición.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario, dejamos que `auth` maneje
        if (!$user) {
            return $next($request);
        }

        // Si tiene perfil completo y aceptó términos, todo bien
        if ($user->tienePerfilCompleto()) {
            return $next($request);
        }

        // Respuesta para clientes JSON / API (estándar del proyecto)
        if ($request->expectsJson()) {
            return response()->json([
                'exito'   => false,
                'mensaje' => 'Debe completar su perfil y aceptar los términos LOPDP.',
                'datos'   => null,
                'errores' => ['perfil' => 'incompleto'],
            ], Response::HTTP_FORBIDDEN);
        }

        // Permitir rutas de auto-completar perfil o cerrar sesión
        foreach ($this->rutasPermitidas as $patron) {
            if ($request->routeIs($patron)) {
                return $next($request);
            }
        }

        // Redirección al formulario de completar perfil.
        // Defensivo: si la ruta aún no existe (1.E pendiente), dejamos pasar
        // con un log de warning para no romper el sistema.
        if (Route::has('perfil.completar')) {
            return redirect()->route('perfil.completar')->with(
                'warning',
                'Para usar el sistema debe completar su perfil y aceptar los términos.'
            );
        }

        // Fallback temporal: dejar pasar pero loguear el caso
        logger()->warning('Ruta perfil.completar no definida. Usuario sin perfil completo accedió a: '
            . $request->fullUrl(), ['user_id' => $user->id]);
        return $next($request);
    }
}