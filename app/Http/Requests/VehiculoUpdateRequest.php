<?php
// app/Http/Requests/VehiculoUpdateRequest.php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validación para actualizar un vehículo desde la app móvil.
 */
class VehiculoUpdateRequest extends FormRequest
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
            'tipo_vehiculo_id' => ['sometimes', 'integer', 'exists:tipos_vehiculo,id'],
            'placa'            => ['sometimes', 'string', 'max:10', 'regex:/^[A-Z]{3}-\d{4}$/i'],
            'marca'            => ['sometimes', 'string', 'max:80'],
            'modelo'           => ['sometimes', 'string', 'max:80'],
            'anio'             => ['sometimes', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'color'            => ['sometimes', 'string', 'max:50'],
            'estado'           => ['sometimes', 'string', 'in:activo,inactivo'],
            'observaciones'    => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'placa.regex' => 'La placa debe tener el formato ecuatoriano: ABC-1234.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();
        throw new HttpResponseException(response()->json([
            'exito'   => false,
            'mensaje' => 'Errores de validación.',
            'datos'   => null,
            'errores' => $errors,
            'errors'  => $errors,
        ], 422));
    }
}
