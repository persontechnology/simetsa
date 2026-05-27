<?php
// app/Http/Requests/ZonaUpdateRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ZonaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('zonas.editar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'poligono' => $this->decodificarPoligono($this->input('poligono')),
        ]);
    }

    /**
     * Decodifica el polígono recibido (JSON string o array) a arreglo PHP.
     * Copiado de ZonaStoreRequest para evitar instanciar otro FormRequest.
     *
     * @param mixed $valor
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
        $id = $this->route('zona')?->id;

        return [
            'codigo'      => ['required', 'string', 'max:30', 'regex:/^[a-z][a-z0-9_]+$/',
                               Rule::unique('zonas', 'codigo')->ignore($id)->whereNull('deleted_at')],
            'nombre'      => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'centro_lat'  => ['required', 'numeric', 'between:-90,90'],
            'centro_lng'  => ['required', 'numeric', 'between:-180,180'],
            'zoom'        => ['required', 'integer', 'between:1,20'],
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
        return (new ZonaStoreRequest())->messages();
    }
}