<?php
// app/Http/Requests/VehiculoStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validación para registrar un vehículo desde la app móvil (Art. 25 Ordenanza SIMETSA).
 *
 * Valida formato de placa ecuatoriana, año y existencia del tipo de vehículo.
 * La unicidad de placa entre conductores activos se valida en VehiculoService (regla de negocio).
 */
class VehiculoStoreRequest extends FormRequest
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
            'tipo_vehiculo_id' => ['required', 'integer', 'exists:tipos_vehiculo,id'],
            'placa'            => ['required', 'string', 'max:10', 'regex:/^[A-Z]{3}-\d{4}$/i'],
            'marca'            => ['required', 'string', 'max:80'],
            'modelo'           => ['required', 'string', 'max:80'],
            'anio'             => ['required', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'color'            => ['required', 'string', 'max:50'],
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
            'anio.min'    => 'El año del vehículo debe ser 1990 o posterior.',
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
