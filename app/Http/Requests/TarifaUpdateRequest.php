<?php
// app/Http/Requests/TarifaUpdateRequest.php

namespace App\Http\Requests;

use App\Services\TarifaService;
use Illuminate\Foundation\Http\FormRequest;

class TarifaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tarifas.editar') ?? false;
    }

    public function rules(): array
    {
        $tarifaId = $this->route('tarifa')?->id;

        return [
            'tipo_plaza_id' => ['required', 'integer', 'exists:tipos_plaza,id'],
            'nombre'        => ['required', 'string', 'max:100'],
            'valor_hora'    => ['required', 'numeric', 'min:0', 'max:999.9999'],

            'vigente_desde' => [
                'required', 'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($tarifaId) {
                    $service = app(TarifaService::class);
                    if ($service->existeSolapamiento(
                        (int) $this->input('tipo_plaza_id'),
                        $value,
                        $this->input('vigente_hasta'),
                        $tarifaId, // ignorar a sí mismo
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
        return (new TarifaStoreRequest())->messages();
    }
}