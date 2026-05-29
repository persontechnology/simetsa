<?php
// app/Http/Resources/TipoVehiculoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialización de TipoVehiculo para la API móvil.
 */
class TipoVehiculoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'codigo'        => $this->codigo,
            'nombre'        => $this->nombre,
            'descripcion'   => $this->descripcion,
            'aplica_tarifa' => $this->aplica_tarifa,
        ];
    }
}
