<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoPlazaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tipos_plaza.editar') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('tipo_plaza')?->id;

        return [
            'codigo'              => ['required', 'string', 'max:30', 'regex:/^[a-z][a-z0-9_]+$/',
                                       Rule::unique('tipos_plaza', 'codigo')->ignore($id)->whereNull('deleted_at')],
            'nombre'              => ['required', 'string', 'max:100'],
            'descripcion'         => ['nullable', 'string'],
            'requiere_credencial' => ['required', 'boolean'],
            'es_pagado'           => ['required', 'boolean'],
            'color_mapa'          => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icono'               => ['nullable', 'string', 'max:50'],
            'activo'              => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return (new StoreTipoPlazaRequest())->messages();
    }
}
