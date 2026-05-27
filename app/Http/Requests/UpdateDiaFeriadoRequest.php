<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DiaFeriado;
class UpdateDiaFeriadoRequest extends FormRequest
{
     public function authorize(): bool
    {
        return $this->user()?->can('feriados.editar') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('dia_feriado')?->id;
        return [
            'fecha'       => ['required', 'date', Rule::unique('dias_feriado', 'fecha')->ignore($id)->whereNull('deleted_at')],
            'nombre'      => ['required', 'string', 'max:150'],
            'tipo'        => ['required', Rule::in(array_keys(DiaFeriado::listadoTipos()))],
            'recurrente'  => ['required', 'boolean'],
            'descripcion' => ['nullable', 'string'],
            'activo'      => ['required', 'boolean'],
        ];
    }
}
