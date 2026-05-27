<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HorarioOperacionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('horarios.editar') ?? false;
    }

    public function rules(): array
    {
        return [
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin'    => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'activo'      => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'hora_fin.after' => 'La hora de cierre debe ser posterior a la hora de inicio.',
            'date_format'    => 'El formato de hora debe ser HH:MM.',
        ];
    }
}