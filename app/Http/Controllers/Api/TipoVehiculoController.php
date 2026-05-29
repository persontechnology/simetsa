<?php
// app/Http/Controllers/Api/TipoVehiculoController.php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TipoVehiculoResource;
use App\Models\TipoVehiculo;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint de solo lectura del catálogo de tipos de vehículo para la app móvil.
 *
 * El conductor necesita este catálogo al registrar un vehículo (Art. 25 Ordenanza SIMETSA).
 */
class TipoVehiculoController extends ApiController
{
    /**
     * Devuelve todos los tipos de vehículo activos.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $tipos = TipoVehiculo::where('activo', true)->orderBy('nombre')->get();

        return $this->exito(
            TipoVehiculoResource::collection($tipos),
            'Tipos de vehículo disponibles.'
        );
    }
}
