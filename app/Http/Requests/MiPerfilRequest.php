<?php
// app/Http/Requests/MiPerfilRequest.php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para el flujo de auto-gestión del PerfilUsuario.
 *
 * Diferencias respecto a UsuarioStoreRequest / PerfilUsuarioRequest:
 *  - No valida campos del modelo User (eso lo hace Breeze en /profile).
 *  - El usuario autenticado actualiza siempre su PROPIO perfil
 *    (sin route model binding — se resuelve vía $this->user()->perfil).
 *  - El consentimiento LOPDP es obligatorio solo si aún no fue otorgado;
 *    si ya está aceptado, el campo es opcional y la fecha original se preserva.
 */
class MiPerfilRequest extends FormRequest
{
    /**
     * Solo usuarios autenticados pueden editar su propio perfil.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación dinámicas según si ya existe consentimiento previo.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $perfilActual = $this->user()
            ? $this->user()->perfil()->first()
            : null;
        $perfilId    = $perfilActual?->id;
        $yaConsintio = (bool) $perfilActual?->acepta_terminos;

        return [
            'cedula' => [
                'required', 'string', 'size:10',
                Rule::unique('perfiles_usuario', 'cedula')->ignore($perfilId),
                new CedulaEcuatoriana(),
            ],
            'telefono'         => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'telefono_celular' => ['required', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:-18 years'],
            'genero'           => ['nullable', Rule::in(['M', 'F', 'O', 'ND'])],
            'foto_perfil'      => ['nullable', 'image', 'max:2048'],

            // Consentimiento LOPDP: obligatorio solo si aún no fue aceptado
            'acepta_terminos'  => $yaConsintio
                ? ['nullable', 'boolean']
                : ['required', 'accepted'],
        ];
    }

    /**
     * Mensajes de error en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cedula.required'           => 'La cédula es obligatoria.',
            'cedula.size'               => 'La cédula debe tener 10 dígitos.',
            'cedula.unique'             => 'Esta cédula ya está registrada en el sistema.',
            'telefono_celular.required' => 'El teléfono celular es obligatorio.',
            'telefono_celular.regex'    => 'El celular solo puede contener números, espacios, guiones y +.',
            'fecha_nacimiento.before'   => 'Debes ser mayor de 18 años.',
            'foto_perfil.image'         => 'El archivo debe ser una imagen.',
            'foto_perfil.max'           => 'La imagen no puede superar los 2 MB.',
            'acepta_terminos.required'  => 'Debes aceptar los términos LOPDP para continuar.',
            'acepta_terminos.accepted'  => 'Debes aceptar los términos LOPDP para continuar.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'telefono_celular' => 'teléfono celular',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'foto_perfil'      => 'foto de perfil',
            'acepta_terminos'  => 'aceptación de términos',
        ];
    }
}