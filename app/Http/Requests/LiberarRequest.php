<?php

// app/Http/Requests/LiberarRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de formato para la liberación del candado inmovilizador (Art. 15).
 * Se puede liberar por pago automático (sin motivo) o por liberación forzada administrativa (con motivo).
 */
class LiberarRequest extends FormRequest
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
            'motivo' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
