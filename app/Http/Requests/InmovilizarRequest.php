<?php

// app/Http/Requests/InmovilizarRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de formato para el registro del candado inmovilizador (Art. 15).
 */
class InmovilizarRequest extends FormRequest
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
            'foto_candado' => ['nullable', 'string', 'max:500'],
            'notas'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
