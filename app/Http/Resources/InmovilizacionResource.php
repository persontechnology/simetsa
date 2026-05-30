<?php

// app/Http/Resources/InmovilizacionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación JSON del candado inmovilizador.
 * Art. 15: queda sin efecto al pagar la infracción.
 *
 * @mixin \App\Models\Inmovilizacion
 */
class InmovilizacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'infraccion_id'  => $this->infraccion_id,
            'estado'         => $this->estado->value,
            'estado_label'   => $this->estado->etiqueta(),
            'estado_color'   => $this->estado->color(),
            'foto_candado'   => $this->foto_candado
                ? asset('storage/' . $this->foto_candado)
                : null,
            'notas'          => $this->notas,
            'inmovilizada_en' => $this->inmovilizada_en?->toIso8601String(),
            'liberada_en'    => $this->liberada_en?->toIso8601String(),

            'agente' => $this->whenLoaded('agente', fn () => [
                'id'     => $this->agente->id,
                'codigo' => $this->agente->codigo,
            ]),
        ];
    }
}
