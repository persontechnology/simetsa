<?php

// app/Http/Controllers/Api/SesionParqueoController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IniciarSesionRequest;
use App\Http\Resources\SesionParqueoResource;
use App\Models\AgenteParqueo;
use App\Models\SesionParqueo;
use App\Services\SesionParqueoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de sesiones de parqueo desde la app del agente en calle.
 *
 * El permiso sesiones_parqueo.iniciar se aplica en routes/api.php.
 */
class SesionParqueoController extends ApiController
{
    public function __construct(private readonly SesionParqueoService $servicio)
    {
    }

    /**
     * Inicia una sesión de parqueo cuando el agente confirma que el vehículo está estacionado.
     *
     * @param  \App\Http\Requests\IniciarSesionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(IniciarSesionRequest $request): JsonResponse
    {
        $agente = AgenteParqueo::where('user_id', $request->user()->id)->first();

        if (! $agente) {
            return $this->error('El usuario autenticado no es un agente de parqueo.', null, 403);
        }

        $ticket = \App\Models\Ticket::findOrFail($request->validated()['ticket_id']);

        try {
            $sesion = $this->servicio->iniciar($ticket, $agente, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new SesionParqueoResource($sesion->load(['agente', 'plaza'])),
            'Sesión de parqueo iniciada.',
            201,
        );
    }

    /**
     * Devuelve el detalle de una sesión de parqueo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SesionParqueo $sesion
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, SesionParqueo $sesion): JsonResponse
    {
        $this->authorize('view', $sesion);

        return $this->exito(
            new SesionParqueoResource($sesion->load(['agente', 'plaza', 'ticket'])),
            'Detalle de la sesión de parqueo.',
        );
    }
}
