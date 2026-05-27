<?php
// app/Http/Requests/ManzanaStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para crear una Manzana.
 *
 * El polígono llega como string JSON del editor de mapa; se decodifica en
 * prepareForValidation. El código admite mayúsculas y guiones (formato de
 * codificación urbana, ej: M01, MZ-03).
 */
class ManzanaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manzanas.crear') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'poligono' => $this->decodificarPoligono($this->input('poligono')),
        ]);
    }

    /**
     * @param  mixed  $valor
     * @return array<int, array<int, float>>|null
     */
    public function decodificarPoligono($valor): ?array
    {
        if (is_array($valor)) {
            return $valor;
        }
        if (is_string($valor) && $valor !== '') {
            $decodificado = json_decode($valor, true);
            return is_array($decodificado) ? $decodificado : null;
        }
        return null;
    }

    public function rules(): array
    {
        return [
            'zona_id'     => ['required', 'integer', 'exists:zonas,id'],
            'codigo'      => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9_\-]*$/', 'unique:manzanas,codigo'],
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
        return [
            'zona_id.required' => 'Debe seleccionar una zona.',
            'codigo.regex'     => 'El código admite letras, números, guion y guion bajo (ej: M01, MZ-03).',
            'codigo.unique'    => 'Ya existe una manzana con este código.',
            'color.regex'      => 'El color debe estar en formato hexadecimal #RRGGBB.',
            'poligono.min'     => 'El polígono debe tener al menos 3 vértices.',
        ];
    }
}