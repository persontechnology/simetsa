<?php

// app/Http/Requests/CancelarTicketRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para la cancelación voluntaria de un ticket por parte del conductor.
 */
class CancelarTicketRequest extends FormRequest
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
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe indicar el motivo de la cancelación.',
            'motivo.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}
