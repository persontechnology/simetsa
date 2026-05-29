<?php

// app/Http/Requests/StoreTicketRequest.php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validación de formato para la compra de un ticket digital (Art. 19, 22 Ordenanza SIMETSA).
 *
 * Las reglas de negocio (horario, feriados, exoneraciones, máx 2h) viven en TicketService.
 */
class StoreTicketRequest extends FormRequest
{
    /**
     * La autorización se maneja vía HasMiddleware en el controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehiculo_id'     => ['required', 'integer', 'exists:vehiculos,id'],
            'zona_id'         => ['required', 'integer', 'exists:zonas,id'],
            'calle_id'        => ['nullable', 'integer', 'exists:calles,id'],
            'horas_compradas' => ['required', 'integer', 'min:1', 'max:2'],
            'metodo_pago'     => ['required', 'string', new Enum(MetodoPago::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehiculo_id.required'     => 'Debe seleccionar un vehículo.',
            'vehiculo_id.exists'       => 'El vehículo seleccionado no existe.',
            'zona_id.required'         => 'Debe seleccionar una zona.',
            'zona_id.exists'           => 'La zona seleccionada no existe.',
            'horas_compradas.required' => 'Debe indicar las horas de parqueo.',
            'horas_compradas.min'      => 'El mínimo es 1 hora.',
            'horas_compradas.max'      => 'El tiempo máximo es 2 horas (Art. 14).',
            'metodo_pago.required'     => 'Debe indicar el método de pago.',
        ];
    }
}
