<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         * Registro de alias de middleware del SIMETSA.
         *
         * - role, permission, role_or_permission: provistos por Spatie.
         *   Uso en rutas: ->middleware('role:super_admin')
         *                 ->middleware('permission:usuarios.crear')
         *                 ->middleware('role_or_permission:comisario|usuarios.editar')
         *
         * - perfil.completo: middleware propio que exige perfil con
         *   consentimiento LOPDP aceptado.
         */
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'perfil.completo'    => \App\Http\Middleware\VerificarPerfilCompleto::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 422: errores de validación en formato envelope para la app móvil
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'exito'   => false,
                    'mensaje' => 'Los datos enviados no son válidos.',
                    'datos'   => null,
                    'errores' => $e->errors(),
                ], 422);
            }
        });

        // 401: no autenticado (token ausente o inválido) en formato envelope
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'exito'   => false,
                    'mensaje' => 'No autenticado.',
                    'datos'   => null,
                    'errores' => null,
                ], 401);
            }
        });
    })->create();
