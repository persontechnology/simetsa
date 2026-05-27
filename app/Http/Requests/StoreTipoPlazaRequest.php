<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoPlazaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tipos_plaza.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo'              => ['required', 'string', 'max:30', 'regex:/^[a-z][a-z0-9_]+$/', 'unique:tipos_plaza,codigo'],
            'nombre'              => ['required', 'string', 'max:100'],
            'descripcion'         => ['nullable', 'string'],
            'requiere_credencial' => ['required', 'boolean'],
            'es_pagado'           => ['required', 'boolean'],
            'color_mapa'          => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icono'               => ['nullable', 'string', 'max:50'],
            'activo'              => ['required', 'boolean'],
            'ancho_sugerido'      => ['nullable', 'numeric', 'between:2.20,2.50'],
            'largo_sugerido'      => ['nullable', 'numeric', 'between:3.00,15.00'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex'     => 'El código debe estar en formato snake_case (minúsculas, números y guion bajo).',
            'codigo.unique'    => 'Ya existe un tipo de plaza con este código.',
            'color_mapa.regex' => 'El color debe estar en formato hexadecimal #RRGGBB.',
            'ancho_sugerido.between' => 'El ancho sugerido debe estar entre 2.20 y 2.50 metros (Art. 6).',
            'largo_sugerido.between' => 'El largo sugerido debe estar entre 3.00 y 15.00 metros.',
        ];
    }
}
