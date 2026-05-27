<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DiaFeriado;
class StoreDiaFeriadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('feriados.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha'       => ['required', 'date', 'unique:dias_feriado,fecha'],
            'nombre'      => ['required', 'string', 'max:150'],
            'tipo'        => ['required', Rule::in(array_keys(DiaFeriado::listadoTipos()))],
            'recurrente'  => ['required', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'activo'      => ['required', 'boolean'],
        ];
    }
}
