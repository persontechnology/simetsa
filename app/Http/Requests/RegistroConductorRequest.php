<?php
// app/Http/Requests/RegistroConductorRequest.php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request del registro público de conductores (Fase 4).
 *
 * Exige cédula ecuatoriana válida y única, correo único, contraseña confirmada
 * y la aceptación expresa del tratamiento de datos personales (Art. 7 LOPDP).
 */
class RegistroConductorRequest extends FormRequest
{
    /**
     * Endpoint público: cualquier persona puede registrarse.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del registro.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // La cédula identifica a la persona: única entre perfiles vigentes (ignora soft-deleted)
            'cedula'           => ['required', 'string', new CedulaEcuatoriana, Rule::unique('perfiles_usuario', 'cedula')->whereNull('deleted_at')],
            'nombres'          => ['required', 'string', 'max:100'],
            'apellidos'        => ['required', 'string', 'max:100'],
            'email'            => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'telefono'         => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'acepta_terminos'  => ['accepted'], // consentimiento informado (Art. 7 LOPDP)
        ];
    }

    /**
     * Mensajes personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'acepta_terminos.accepted' => 'Debe aceptar el tratamiento de sus datos personales (LOPDP) para registrarse.',
            'cedula.unique'            => 'Ya existe una cuenta registrada con esta cédula. Iniciá sesión.',
            'email.unique'             => 'Este correo ya está registrado. Iniciá sesión.',
            'password.confirmed'       => 'La confirmación de la contraseña no coincide.',
        ];
    }

    /**
     * Return a JSON response with the standardized envelope when validation fails.
     * Include both `errores` (project envelope) and `errors` (Laravel test helpers expect this).
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        $response = response()->json([
            'exito'   => false,
            'mensaje' => 'Errores de validación.',
            'datos'   => null,
            'errores' => $errors,
            'errors'  => $errors,
        ], 422);

        throw new HttpResponseException($response);
    }
}