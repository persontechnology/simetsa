<?php
// app/Http/Resources/VehiculoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON de un vehículo para la app móvil (Art. 25 Ordenanza SIMETSA).
 */
class VehiculoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tipo_vehiculo_id' => $this->tipo_vehiculo_id,
            'tipo_vehiculo'    => $this->whenLoaded('tipoVehiculo', fn () => [
                'id'     => $this->tipoVehiculo->id,
                'codigo' => $this->tipoVehiculo->codigo,
                'nombre' => $this->tipoVehiculo->nombre,
            ]),
            'placa'            => $this->placa,
            'marca'            => $this->marca,
            'modelo'           => $this->modelo,
            'anio'             => $this->anio,
            'color'            => $this->color,
            'estado'           => $this->estado,
            'observaciones'    => $this->observaciones,
            'fecha_registro'   => $this->created_at?->toDateString(),
        ];
    }
}
