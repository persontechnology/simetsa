<?php

// app/Http/Resources/SesionParqueoResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON de la sesión de parqueo para la app móvil.
 *
 * @mixin \App\Models\SesionParqueo
 */
class SesionParqueoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'ticket_id'          => $this->ticket_id,
            'estado'             => $this->estado->value,
            'estado_label'       => $this->estado->etiqueta(),
            'estado_color'       => $this->estado->color(),
            'lat_inicio'         => $this->lat_inicio,
            'lng_inicio'         => $this->lng_inicio,
            'inicio_at'          => $this->inicio_at?->toIso8601String(),
            'fin_programado_at'  => $this->fin_programado_at?->toIso8601String(),
            'fin_real_at'        => $this->fin_real_at?->toIso8601String(),

            'agente' => $this->whenLoaded('agente', fn () => $this->agente ? [
                'id'     => $this->agente->id,
                'codigo' => $this->agente->codigo,
            ] : null),
            'plaza' => $this->whenLoaded('plaza', fn () => $this->plaza ? [
                'id'     => $this->plaza->id,
                'codigo' => $this->plaza->codigo,
            ] : null),
        ];
    }
}
