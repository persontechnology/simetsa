<?php
// app/Http/Requests/LoginConductorRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request del inicio de sesión de conductores (Fase 4).
 */
class LoginConductorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // endpoint público
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}