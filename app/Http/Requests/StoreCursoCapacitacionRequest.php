<?php

namespace App\Http\Requests;

use App\Models\CursoCapacitacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para crear una edición del curso de capacitación (Art. 33.5).
 * El folio (codigo) se genera automáticamente.
 */
class StoreCursoCapacitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agentes.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre'       => ['required', 'string', 'max:150'],
            'descripcion'  => ['nullable', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'cupo'         => ['nullable', 'integer', 'min:1', 'max:1000'],
            'estado'       => ['required', Rule::in(array_keys(CursoCapacitacion::listadoEstados()))],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
        ];
    }
}