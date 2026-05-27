<?php
// app/Http/Requests/PlazaUpdateRequest.php

namespace App\Http\Requests;

use App\Models\Plaza;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlazaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plazas.editar') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('plaza')?->id;

        return [
            'zona_id'       => ['required', 'integer', 'exists:zonas,id'],
            'calle_id'      => ['nullable', 'integer', 'exists:calles,id'],
            'manzana_id'    => ['nullable', 'integer', 'exists:manzanas,id'],
            'tipo_plaza_id' => ['required', 'integer', 'exists:tipos_plaza,id'],

            'codigo'        => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9_\-]*$/',
                                 Rule::unique('plazas', 'codigo')->ignore($id)->whereNull('deleted_at')],
            'numero'        => ['nullable', 'string', 'max:20'],

            'latitud'       => ['nullable', 'required_with:longitud', 'numeric', 'between:-90,90'],
            'longitud'      => ['nullable', 'required_with:latitud', 'numeric', 'between:-180,180'],

            'ancho_metros'  => ['nullable', 'numeric', 'between:2.20,2.50'],
            'largo_metros'  => ['nullable', 'numeric', 'between:3.00,15.00'],
            'orientacion'   => ['required', Rule::in(array_keys(Plaza::listadoOrientaciones()))],
            'activo'        => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return (new PlazaStoreRequest())->messages();
    }
}