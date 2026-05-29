<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Enums\RolSistema;
use App\Http\Requests\LoginConductorRequest;
use App\Http\Requests\RegistroConductorRequest;
use App\Http\Resources\ConductorResource;
use App\Models\Conductor;
use App\Models\User;
use App\Services\ConductorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Autenticación de conductores para la app móvil (Fase 4).
 *
 * Maneja el registro público (con consentimiento LOPDP), el inicio y cierre
 * de sesión por tokens Sanctum, y la consulta del perfil del conductor
 * autenticado.
 */
class AuthController extends ApiController
{
    public function __construct(private ConductorService $servicio)
    {
    }

    /**
     * Registra un nuevo conductor y devuelve su token de acceso.
     *
     * Crea la cuenta de usuario, el rol 'conductor', el perfil (con el
     * consentimiento de tratamiento de datos — Art. 7 LOPDP) y el registro
     * de conductor, todo de forma atómica en el servicio.
     *
     * @param  \App\Http\Requests\RegistroConductorRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrar(RegistroConductorRequest $request): JsonResponse
    {
        try {
            $resultado = $this->servicio->registrar($request->validated());

            return $this->exito([
                'token'      => $resultado['token'],
                'tipo_token' => 'Bearer',
                'conductor'  => new ConductorResource($resultado['conductor']->load('user.perfil')),
            ], 'Registro exitoso. Bienvenido al SIMETSA.', 201);
        } catch (\Throwable $e) {
            Log::error('Error en registro de conductor', ['error' => $e->getMessage()]);

            return $this->error('No se pudo completar el registro. Intentá nuevamente.', null, 500);
        }
    }

    /**
     * Inicia sesión de un conductor y emite un token personal.
     *
     * Valida credenciales y verifica que la cuenta tenga el rol 'conductor'
     * (un agente o punto de venta no inicia sesión por la app de conductores).
     *
     * @param  \App\Http\Requests\LoginConductorRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginConductorRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $user  = User::where('email', $datos['email'])->first();

        // Credenciales inválidas: mismo mensaje para usuario inexistente o clave errónea
        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            return $this->error('Credenciales incorrectas.', null, 401);
        }

        if (! $user->hasRole(RolSistema::Conductor->value)) {
            return $this->error('Esta cuenta no corresponde a un conductor.', null, 403);
        }

        $conductor = Conductor::where('user_id', $user->id)->first();

        if (! $conductor) {
            return $this->error('Conductor no encontrado.', null, 403);
        }
        if($conductor->estado !== Conductor::ESTADO_ACTIVO) {
            return $this->error('Conductor no está activo.', null, 403);
        }

        $token = $user->createToken('movil')->plainTextToken;

        return $this->exito([
            'token'      => $token,
            'tipo_token' => 'Bearer',
            'conductor'  => new ConductorResource($conductor->load('user.perfil')),
        ], 'Sesión iniciada.');
    }

    /**
     * Cierra la sesión revocando el token con el que se autenticó la petición.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        
        //Intentamos encontrar y eliminar el token que se usó para esta petición.
        $plain = $request->bearerToken();
        if ($plain) {
            $token = PersonalAccessToken::findToken($plain);
            Log::info('Logout token lookup', ['token' => $plain, 'found' => (bool) $token, 'token_id' => $token?->id]);
            if ($token) {
                $token->delete();
                Log::info('Logout token deleted', ['token_id' => $token->id]);
            }
        }

        Log::info('Logout request headers', $request->headers->all());

        // Aseguramos que los tokens sean revocados — eliminamos cualquier token de acceso personal restante.
        try {
            $request->user()->tokens()->delete();
        } catch (\Throwable $e) {
            Log::warning('Failed to delete user tokens on logout', ['error' => $e->getMessage()]);
        }

        //También invalidamos la sesión y cerramos cualquier guardia web para evitar que la autenticación basada en sesión persista entre solicitudes de prueba.   
        try {
            auth()->guard('web')->logout();
        } catch (\Throwable $e) {
            // ignore if guard not available
        }

        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Throwable $e) {
            //la sesión puede no estar iniciada en el contexto de API; ignorar de forma segura
        }

        return $this->exito(null, 'Sesión cerrada correctamente.');
    }

    /**
     * Devuelve los datos del conductor autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function perfil(Request $request): JsonResponse
    {
        
        // Requiere un token de acceso personal válido para acceder al perfil de API. Esto asegura que solo los tokens emitidos por Sanctum (y no, por ejemplo, una sesión web) puedan acceder a este endpoint.
        $plain = $request->bearerToken();
        if (! $plain || ! PersonalAccessToken::findToken($plain)) {
            return $this->error('Token inválido o expirado.', null, 401);
        }

        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        return $this->exito(new ConductorResource($conductor->load('user.perfil')), 'Perfil del conductor.');
    }
}