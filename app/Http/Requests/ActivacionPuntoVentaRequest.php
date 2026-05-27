<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivacionPuntoVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El service decide si crea o vincula la cuenta, por eso 'email' no lleva 'unique'.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'numero_contrato' => ['required', 'string', 'max:100'],
            'fecha_firma' => ['required', 'date'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'elaborado_por' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}