<?php
// app/Http/Requests/TarifaStoreRequest.php

namespace App\Http\Requests;

use App\Services\TarifaService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para crear una nueva Tarifa.
 *
 * Aplica una validación custom de solapamiento: no se permite que dos
 * tarifas activas del mismo tipo de plaza cubran rangos de fecha que
 * se intersecten.
 */
class TarifaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tarifas.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo_plaza_id' => ['required', 'integer', 'exists:tipos_plaza,id'],
            'nombre'        => ['required', 'string', 'max:100'],
            'valor_hora'    => ['required', 'numeric', 'min:0', 'max:999.9999'],

            'vigente_desde' => [
                'required', 'date',
                // Validación de solapamiento delegada al service
                function (string $attribute, mixed $value, \Closure $fail) {
                    $service = app(TarifaService::class);
                    if ($service->existeSolapamiento(
                        (int) $this->input('tipo_plaza_id'),
                        $value,
                        $this->input('vigente_hasta'),
                    )) {
                        $fail('Las fechas se solapan con otra tarifa activa del mismo tipo de plaza.');
                    }
                },
            ],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],

            'descripcion'   => ['nullable', 'string'],
            'activo'        => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_plaza_id.required' => 'Debe seleccionar un tipo de plaza.',
            'tipo_plaza_id.exists'   => 'El tipo de plaza seleccionado no existe.',
            'valor_hora.min'         => 'El valor por hora no puede ser negativo.',
            'vigente_hasta.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la de inicio.',
        ];
    }
}