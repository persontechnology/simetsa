<?php
// app/Http/Requests/AprobacionCredencialRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del backoffice para aprobar o rechazar una credencial CONADIS (Art. 26 Ordenanza SIMETSA).
 *
 * El carácter obligatorio de las observaciones en el rechazo se valida en
 * CredencialDiscapacidadService::rechazar() (es una regla de negocio, no de formato).
 */
class AprobacionCredencialRequest extends FormRequest
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
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}
