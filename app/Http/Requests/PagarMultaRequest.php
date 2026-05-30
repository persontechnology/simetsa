<?php

// app/Http/Requests/PagarMultaRequest.php

namespace App\Http\Requests;

use App\Enums\ProveedorPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de formato para el inicio del pago de una multa por el conductor.
 */
class PagarMultaRequest extends FormRequest
{
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
            'proveedor' => ['required', Rule::enum(ProveedorPago::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proveedor.required' => 'Debe indicar el proveedor de pago.',
            'proveedor.enum'     => 'El proveedor indicado no es válido.',
        ];
    }
}
