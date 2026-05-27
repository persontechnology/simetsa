<?php
// app/Http/Requests/PlazaStoreRequest.php

namespace App\Http\Requests;

use App\Models\Plaza;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para crear una Plaza.
 *
 * La ubicación llega como dos inputs numéricos (latitud/longitud) que el
 * selector de mapa sincroniza; si se envía uno, el otro es obligatorio.
 * El ancho se valida en el rango del Art. 6 (2.20-2.50 m).
 */
class PlazaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plazas.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'zona_id'       => ['required', 'integer', 'exists:zonas,id'],
            'calle_id'      => ['nullable', 'integer', 'exists:calles,id'],
            'manzana_id'    => ['nullable', 'integer', 'exists:manzanas,id'],
            'tipo_plaza_id' => ['required', 'integer', 'exists:tipos_plaza,id'],

            'codigo'        => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9_\-]*$/', 'unique:plazas,codigo'],
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
        return [
            'zona_id.required'       => 'Debe seleccionar una zona.',
            'tipo_plaza_id.required' => 'Debe seleccionar el tipo de plaza.',
            'codigo.regex'           => 'El código admite letras, números, guion y guion bajo (ej: VL-01).',
            'codigo.unique'          => 'Ya existe una plaza con este código.',
            'ancho_metros.between'   => 'El ancho debe estar entre 2.20 y 2.50 metros (Art. 6).',
            'largo_metros.between'   => 'El largo debe estar entre 3.00 y 15.00 metros.',
            'latitud.required_with'  => 'Si indica la longitud, también debe indicar la latitud.',
            'longitud.required_with' => 'Si indica la latitud, también debe indicar la longitud.',
        ];
    }
}