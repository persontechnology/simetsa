<?php
// app/Http/Requests/SolicitudAgenteUpdateRequest.php

namespace App\Http\Requests;

use App\Models\SolicitudAgente;
use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitudAgenteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agentes.editar') ?? false;
    }

    public function rules(): array
    {
        $fechaMaxima = now()->subYears(SolicitudAgente::EDAD_MINIMA)->format('Y-m-d');

        return [
            'cedula'           => ['required', 'string', 'size:10', new CedulaEcuatoriana()],
            'nombres'          => ['required', 'string', 'max:100'],
            'apellidos'        => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:' . $fechaMaxima],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:150'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'nivel_educacion'  => ['required', Rule::in(array_keys(SolicitudAgente::listadoNivelesEducacion()))],
            'observaciones'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_nacimiento.before_or_equal' => 'El postulante debe ser mayor de 18 años (Art. 33).',
        ];
    }
}
