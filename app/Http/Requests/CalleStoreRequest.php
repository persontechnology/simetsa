<?php
// app/Http/Requests/CalleStoreRequest.php

namespace App\Http\Requests;

use App\Models\Calle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para crear una Calle.
 *
 * La polilínea llega como string JSON del editor de mapa; se decodifica
 * en prepareForValidation. El código permite empezar con dígito porque
 * calles como "24 de Mayo" generan códigos numéricos.
 */
class CalleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('calles.crear') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'polilinea' => $this->decodificarPolilinea($this->input('polilinea')),
        ]);
    }

    /**
     * @param  mixed  $valor
     * @return array<int, array<int, float>>|null
     */
    public function decodificarPolilinea($valor): ?array
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
            'zona_id'              => ['required', 'integer', 'exists:zonas,id'],
            'codigo'               => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_]*$/', 'unique:calles,codigo'],
            'nombre'               => ['required', 'string', 'max:150'],
            'desde'                => ['nullable', 'string', 'max:150'],
            'hasta'                => ['nullable', 'string', 'max:150'],
            'sentido'              => ['required', Rule::in(array_keys(Calle::listadoSentidos()))],
            'lado_estacionamiento' => ['required', Rule::in(array_keys(Calle::listadoLados()))],
            'activo'               => ['required', 'boolean'],

            // Polilínea: opcional, pero si viene necesita ≥ 2 vértices
            'polilinea'     => ['nullable', 'array', 'min:2'],
            'polilinea.*'   => ['array', 'size:2'],
            'polilinea.*.0' => ['numeric', 'between:-90,90'],
            'polilinea.*.1' => ['numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'zona_id.required' => 'Debe seleccionar una zona.',
            'codigo.regex'     => 'El código debe ser snake_case (minúsculas, números y guion bajo).',
            'codigo.unique'    => 'Ya existe una calle con este código.',
            'polilinea.min'    => 'El trazado de la calle debe tener al menos 2 puntos.',
        ];
    }
}