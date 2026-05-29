<?php
// app/Http/Resources/ConductorResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON del conductor para la app móvil (Fase 4).
 *
 * Espera que la relación 'user.perfil' esté cargada (load('user.perfil')).
 */
class ConductorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'codigo'           => $this->codigo,
            'estado'           => $this->estado,
            'nombre'           => $this->user?->name,
            'email'            => $this->user?->email,
            'cedula'           => $this->user?->perfil?->cedula,
            'telefono_celular' => $this->user?->perfil?->telefono_celular,
            'fecha_registro'   => $this->created_at?->toDateString(),
            'total_vehiculos'  => $this->whenLoaded('vehiculos', fn () => $this->vehiculos->count()),
        ];
    }
}