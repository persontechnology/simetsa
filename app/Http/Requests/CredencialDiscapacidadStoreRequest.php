<?php
// app/Http/Requests/CredencialDiscapacidadStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validación para registrar una credencial CONADIS desde la app móvil (Art. 26 Ordenanza SIMETSA).
 *
 * La unicidad de credencial activa por vehículo se verifica en CredencialDiscapacidadService
 * (regla de negocio, no de formato).
 */
class CredencialDiscapacidadStoreRequest extends FormRequest
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
            'numero_conadis'          => ['required', 'string', 'max:50'],
            'nombre_beneficiario'     => ['required', 'string', 'max:200'],
            'fecha_emision'           => ['required', 'date', 'before_or_equal:today'],
            'fecha_vencimiento'       => ['nullable', 'date', 'after:fecha_emision'],
            'porcentaje_discapacidad' => ['nullable', 'integer', 'min:30', 'max:100'],
            'archivo'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observaciones'           => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'porcentaje_discapacidad.min' => 'El porcentaje de discapacidad debe ser al menos 30% (CONADIS).',
            'fecha_emision.before_or_equal' => 'La fecha de emisión no puede ser futura.',
            'archivo.mimes' => 'El archivo debe ser PDF, JPG o PNG.',
            'archivo.max'   => 'El archivo no debe superar los 5 MB.',
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
