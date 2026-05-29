<?php
// app/Http/Controllers/Api/VehiculoController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\VehiculoStoreRequest;
use App\Http\Requests\VehiculoUpdateRequest;
use App\Http\Resources\VehiculoResource;
use App\Models\Conductor;
use App\Models\Vehiculo;
use App\Services\VehiculoService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de vehículos del conductor desde la app móvil (Art. 25 Ordenanza SIMETSA).
 *
 * Un conductor puede registrar, listar, ver, actualizar y eliminar sus propios
 * vehículos. La autorización de ownership se verifica mediante VehiculoPolicy.
 */
class VehiculoController extends ApiController
{
    public function __construct(private readonly VehiculoService $servicio)
    {
    }

    /**
     * Lista los vehículos del conductor autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        $vehiculos = $conductor->vehiculos()
            ->with('tipoVehiculo')
            ->orderByDesc('created_at')
            ->get();

        return $this->exito(VehiculoResource::collection($vehiculos), 'Vehículos del conductor.');
    }

    /**
     * Registra un nuevo vehículo para el conductor autenticado.
     *
     * @see Art. 25 Ordenanza SIMETSA.
     *
     * @param  \App\Http\Requests\VehiculoStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(VehiculoStoreRequest $request): JsonResponse
    {
        $conductor = Conductor::where('user_id', $request->user()->id)->first();

        if (! $conductor) {
            return $this->error('El usuario autenticado no es un conductor.', null, 403);
        }

        $this->authorize('create', Vehiculo::class);

        try {
            $vehiculo = $this->servicio->registrar($conductor, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new VehiculoResource($vehiculo->load('tipoVehiculo')),
            'Vehículo registrado correctamente.',
            201,
        );
    }

    /**
     * Devuelve los datos de un vehículo.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Vehiculo $vehiculo): JsonResponse
    {
        $this->authorize('view', $vehiculo);

        return $this->exito(
            new VehiculoResource($vehiculo->load('tipoVehiculo')),
            'Detalle del vehículo.',
        );
    }

    /**
     * Actualiza los datos de un vehículo del conductor.
     *
     * @param  \App\Http\Requests\VehiculoUpdateRequest  $request
     * @param  \App\Models\Vehiculo                      $vehiculo
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(VehiculoUpdateRequest $request, Vehiculo $vehiculo): JsonResponse
    {
        $this->authorize('update', $vehiculo);

        try {
            $vehiculo = $this->servicio->actualizar($vehiculo, $request->validated());
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), null, 422);
        }

        return $this->exito(
            new VehiculoResource($vehiculo->load('tipoVehiculo')),
            'Vehículo actualizado correctamente.',
        );
    }

    /**
     * Elimina (soft delete) un vehículo del conductor.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Vehiculo $vehiculo): JsonResponse
    {
        $this->authorize('delete', $vehiculo);

        $this->servicio->eliminar($vehiculo);

        return $this->exito(null, 'Vehículo eliminado correctamente.');
    }
}
