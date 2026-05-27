<?php
// app/Http/Requests/AutorizacionAgenteRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para autorizar un agente (Art. 36).
 *
 * Exige correo único (cuenta nueva), credencial y la carta compromiso firmada.
 */
class AutorizacionAgenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agentes.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'email'                    => ['required', 'email', 'max:150'],
            'numero_credencial'        => ['required', 'string', 'max:50'],
            'numero_oficio_comisario'  => ['nullable', 'string', 'max:100'],
            'carta_compromiso_firmada' => ['accepted'], // Art. 36
        ];
    }

    public function messages(): array
    {
        return [
            'carta_compromiso_firmada.accepted' => 'Debe confirmar la firma de la carta compromiso (Art. 36).',
        ];
    }
}