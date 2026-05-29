<?php
// app/Http/Requests/VehiculoExoneradoUpdateRequest.php

namespace App\Http\Requests;

use App\Models\VehiculoExonerado;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para actualizar un vehículo exonerado (Art. 27 Ordenanza SIMETSA).
 */
class VehiculoExoneradoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->placa) {
            $this->merge(['placa' => strtoupper(trim($this->placa))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'placa'               => ['required', 'string', 'max:10'],
            'institucion'         => ['required', 'string', 'max:200'],
            'tipo_exoneracion'    => ['required', 'string', 'in:' . implode(',', array_keys(VehiculoExonerado::listadoTipos()))],
            'nombre_funcionario'  => ['nullable', 'string', 'max:200'],
            'numero_oficio'       => ['nullable', 'string', 'max:100'],
            'tiempo_maximo_horas' => ['nullable', 'integer', 'min:1', 'max:2'],
            'observaciones'       => ['nullable', 'string', 'max:500'],
            'fecha_registro'      => ['required', 'date'],
            'activo'              => ['nullable', 'boolean'],
        ];
    }
}
