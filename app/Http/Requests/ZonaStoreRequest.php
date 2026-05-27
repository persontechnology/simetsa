<?php
// app/Http/Requests/ZonaStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para crear una Zona.
 *
 * El polígono llega como string JSON desde un input oculto del editor de
 * mapa; prepareForValidation lo decodifica a arreglo antes de validar.
 */
class ZonaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('zonas.crear') ?? false;
    }

    /**
     * Decodifica el polígono (JSON string del editor) a arreglo PHP.
     */
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
    protected function decodificarPoligono($valor): ?array
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
            'codigo'      => ['required', 'string', 'max:30', 'regex:/^[a-z][a-z0-9_]+$/', 'unique:zonas,codigo'],
            'nombre'      => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'centro_lat'  => ['required', 'numeric', 'between:-90,90'],
            'centro_lng'  => ['required', 'numeric', 'between:-180,180'],
            'zoom'        => ['required', 'integer', 'between:1,20'],
            'color'       => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'activo'      => ['required', 'boolean'],

            // Polígono: opcional, pero si viene debe ser arreglo de pares [lat,lng]
            'poligono'     => ['nullable', 'array', 'min:3'],
            'poligono.*'   => ['array', 'size:2'],
            'poligono.*.0' => ['numeric', 'between:-90,90'],   // lat
            'poligono.*.1' => ['numeric', 'between:-180,180'], // lng
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex'   => 'El código debe estar en formato snake_case.',
            'codigo.unique'  => 'Ya existe una zona con este código.',
            'color.regex'    => 'El color debe estar en formato hexadecimal #RRGGBB.',
            'poligono.min'   => 'El polígono debe tener al menos 3 vértices.',
            'poligono.*.size' => 'Cada vértice debe tener exactamente latitud y longitud.',
        ];
    }
}