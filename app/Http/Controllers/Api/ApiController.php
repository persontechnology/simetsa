<?php
// app/Http/Controllers/Api/ApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Controlador base de la API v1 del SIMETSA.
 *
 * Centraliza el formato de respuesta JSON acordado para la app móvil:
 * { "exito": bool, "mensaje": string, "datos": mixed|null, "errores": mixed|null }.
 * Todos los controladores de la API deben extender esta clase.
 */
abstract class ApiController extends Controller
{
    /**
     * Respuesta exitosa con el envelope estándar.
     *
     * @param  mixed   $datos    Carga útil (recurso, colección, etc.).
     * @param  string  $mensaje  Mensaje legible para el usuario.
     * @param  int     $codigo   Código HTTP (200, 201, ...).
     * @return \Illuminate\Http\JsonResponse
     */
    protected function exito(mixed $datos = null, string $mensaje = 'Operación realizada correctamente.', int $codigo = 200): JsonResponse
    {
        return response()->json([
            'exito'   => true,
            'mensaje' => $mensaje,
            'datos'   => $datos,
            'errores' => null,
        ], $codigo);
    }

    /**
     * Respuesta de error con el envelope estándar.
     *
     * @param  string  $mensaje  Mensaje legible para el usuario.
     * @param  mixed   $errores  Detalle de errores (campo => [mensajes]) o null.
     * @param  int     $codigo   Código HTTP (400, 401, 403, 500, ...).
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $mensaje, mixed $errores = null, int $codigo = 400): JsonResponse
    {
        return response()->json([
            'exito'   => false,
            'mensaje' => $mensaje,
            'datos'   => null,
            'errores' => $errores,
        ], $codigo);
    }
}