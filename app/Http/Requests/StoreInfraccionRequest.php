<?php

// app/Http/Requests/StoreInfraccionRequest.php

namespace App\Http\Requests;

use App\Enums\TipoInfraccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de formato para el registro de una infracción por el agente (Arts. 17, 18).
 * Las reglas de negocio (agente activo, minutos mínimos, SBU) las verifica InfraccionService.
 */
class StoreInfraccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'placa'             => ['required', 'string', 'max:10'],
            'tipo_infraccion'   => ['required', Rule::enum(TipoInfraccion::class)],
            'zona_id'           => ['required', 'integer', 'exists:zonas,id'],
            'calle_id'          => ['nullable', 'integer', 'exists:calles,id'],
            'ticket_id'         => ['nullable', 'integer', 'exists:tickets,id'],
            'conductor_id'      => ['nullable', 'integer', 'exists:conductores,id'],
            'minutos_excedidos' => ['nullable', 'integer', 'min:1'],
            'descripcion'       => ['nullable', 'string', 'max:1000'],
            'foto_evidencia'    => ['nullable', 'string', 'max:500'],
            'latitud'           => ['nullable', 'numeric', 'between:-90,90'],
            'longitud'          => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'placa.required'           => 'La placa del vehículo es obligatoria.',
            'placa.max'                => 'La placa no puede superar 10 caracteres.',
            'tipo_infraccion.required' => 'Debe indicar el tipo de infracción.',
            'tipo_infraccion.enum'     => 'El tipo de infracción indicado no es válido.',
            'zona_id.required'         => 'Debe indicar la zona de la infracción.',
            'zona_id.exists'           => 'La zona indicada no existe.',
            'ticket_id.exists'         => 'El ticket indicado no existe.',
            'conductor_id.exists'      => 'El conductor indicado no existe.',
            'minutos_excedidos.min'    => 'Los minutos excedidos deben ser al menos 1.',
            'latitud.between'          => 'Latitud fuera del rango válido (-90 a 90).',
            'longitud.between'         => 'Longitud fuera del rango válido (-180 a 180).',
        ];
    }
}
