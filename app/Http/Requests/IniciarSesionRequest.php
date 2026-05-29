<?php

// app/Http/Requests/IniciarSesionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de formato para el inicio de una sesión de parqueo por parte del agente.
 */
class IniciarSesionRequest extends FormRequest
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
            'ticket_id' => ['required', 'integer', 'exists:tickets,id'],
            'plaza_id'  => ['nullable', 'integer', 'exists:plazas,id'],
            'latitud'   => ['nullable', 'numeric', 'between:-90,90'],
            'longitud'  => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ticket_id.required' => 'Debe indicar el ID del ticket.',
            'ticket_id.exists'   => 'El ticket indicado no existe.',
            'plaza_id.exists'    => 'La plaza indicada no existe.',
            'latitud.between'    => 'Latitud fuera del rango válido.',
            'longitud.between'   => 'Longitud fuera del rango válido.',
        ];
    }
}
