<?php
// app/Http/Controllers/Api/CredencialDiscapacidadController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CredencialDiscapacidadStoreRequest;
use App\Http\Resources\CredencialDiscapacidadResource;
use App\Models\Conductor;
use App\Models\CredencialDiscapacidad;
use App\Models\Vehiculo;
use App\Services\CredencialDiscapacidadService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de credenciales CONADIS del conductor desde la app móvil (Art. 26 Ordenanza SIMETSA).
 *
 * El conductor registra su credencial; el comisario o director la aprueba o rechaza
 * desde el backoffice web (CredencialDiscapacidadController).
 */
class CredencialDiscapacidadController extends ApiController
{
    public function __construct(private readonly CredencialDiscapacidadService $servicio)
    {
    }

    /**
     * Registra una solicitud de credencial CONADIS para un vehículo del conductor.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\CredencialDiscapacidadStoreRequest  $request
     * @param  \App\Models\Vehiculo                                   $vehiculo
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CredencialDiscapacidadStoreRequest $request, Vehiculo $vehiculo): JsonResponse
    {
        $this->authorize('create', CredencialDiscapacidad::class);

        // Conductores solo pueden registrar credenciales de sus propios vehículos.
        if ($request->user()->hasRole('conductor')) {
            $conductor = Conductor::where('user_id', $request->user()->id)->first();

            if (! $conductor || $vehiculo->conductor_id !== $conductor->id) {
                return $this->error('No tienes acceso a este vehículo.', null, 403);
            }
        }

        try {
            $credencial = $this->servicio->solicitar($vehiculo, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new CredencialDiscapacidadResource($credencial),
            'Credencial enviada para revisión.',
            201,
        );
    }

    /**
     * Devuelve la credencial más reciente del vehículo.
     *
     * @see Art. 26 Ordenanza SIMETSA.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vehiculo      $vehiculo
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Vehiculo $vehiculo): JsonResponse
    {
        if (! $request->user()->can('credenciales_discapacidad.ver')) {
            return $this->error('No autorizado.', null, 403);
        }

        // Conductores solo pueden ver credenciales de sus propios vehículos.
        if ($request->user()->hasRole('conductor')) {
            $conductor = Conductor::where('user_id', $request->user()->id)->first();

            if (! $conductor || $vehiculo->conductor_id !== $conductor->id) {
                return $this->error('No tienes acceso a este vehículo.', null, 403);
            }
        }

        $credencial = $vehiculo->credencial;

        if (! $credencial) {
            return $this->error('Este vehículo no tiene credencial CONADIS registrada.', null, 404);
        }

        return $this->exito(new CredencialDiscapacidadResource($credencial), 'Credencial del vehículo.');
    }
}
