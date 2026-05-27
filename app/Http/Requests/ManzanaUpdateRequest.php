<?php
// app/Http/Requests/ManzanaUpdateRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManzanaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manzanas.editar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'poligono' => (new ManzanaStoreRequest())->decodificarPoligono($this->input('poligono')),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('manzana')?->id;

        return [
            'zona_id'     => ['required', 'integer', 'exists:zonas,id'],
            'codigo'      => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9_\-]*$/',
                               Rule::unique('manzanas', 'codigo')->ignore($id)->whereNull('deleted_at')],
            'nombre'      => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'color'       => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'activo'      => ['required', 'boolean'],

            'poligono'     => ['nullable', 'array', 'min:3'],
            'poligono.*'   => ['array', 'size:2'],
            'poligono.*.0' => ['numeric', 'between:-90,90'],
            'poligono.*.1' => ['numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return (new ManzanaStoreRequest())->messages();
    }
}