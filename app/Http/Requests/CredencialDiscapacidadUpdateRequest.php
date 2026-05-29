<?php
// app/Http/Requests/CredencialDiscapacidadUpdateRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reservado para posibles actualizaciones de credencial desde la app móvil.
 * El flujo principal usa CredencialDiscapacidadStoreRequest (nueva solicitud).
 */
class CredencialDiscapacidadUpdateRequest extends FormRequest
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
        return [];
    }
}
