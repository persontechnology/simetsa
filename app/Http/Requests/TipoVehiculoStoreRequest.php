<?php
// app/Http/Requests/TipoVehiculoStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para crear un tipo de vehículo (Art. 25 Ordenanza SIMETSA).
 */
class TipoVehiculoStoreRequest extends FormRequest
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
            'codigo'        => ['required', 'string', 'max:30', 'regex:/^[a-z_]+$/', 'unique:tipos_vehiculo,codigo'],
            'nombre'        => ['required', 'string', 'max:100'],
            'descripcion'   => ['nullable', 'string', 'max:500'],
            'aplica_tarifa' => ['boolean'],
            'activo'        => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un tipo de vehículo con ese código.',
            'codigo.regex'  => 'El código debe estar en snake_case (solo letras minúsculas y guiones bajos).',
        ];
    }
}
