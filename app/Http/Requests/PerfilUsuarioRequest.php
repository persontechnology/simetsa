<?php
// app/Http/Requests/PerfilUsuarioRequest.php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para crear/actualizar un PerfilUsuario.
 *
 * Aplica las reglas LOPDP (consentimiento informado obligatorio) y
 * la validación oficial de cédula ecuatoriana. Reutilizable tanto
 * en el backoffice (Blade) como en la API móvil (Sanctum).
 */
class PerfilUsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta petición.
     * La autorización fina por rol/permiso se delega a las Policies
     * que se implementarán en la sub-fase 1.D.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Reglas de validación aplicables a la petición.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Para el update, recuperamos el id del perfil desde la ruta para
        // ignorarlo en la regla unique de cedula.
        $perfilId = $this->route('perfil')?->id;

        return [
            'cedula' => [
                'required',
                'string',
                'size:10',
                Rule::unique('perfiles_usuario', 'cedula')->ignore($perfilId),
                new CedulaEcuatoriana(),
            ],

            'telefono'         => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'telefono_celular' => ['required', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'direccion'        => ['nullable', 'string', 'max:255'],

            // Mínimo 18 años: requisito Art. 33.4 para Agentes; aplica en general.
            'fecha_nacimiento' => ['nullable', 'date', 'before:-18 years'],

            'genero'      => ['nullable', Rule::in(['M', 'F', 'O', 'ND'])],
            'foto_perfil' => ['nullable', 'image', 'max:2048'], // 2 MB

            // Consentimiento informado obligatorio (LOPDP Art. 7)
            'acepta_terminos' => ['required', 'accepted'],
        ];
    }

    /**
     * Mensajes de error en español para cada regla.
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
            'telefono.regex'            => 'El teléfono solo puede contener números, espacios, guiones y +.',

            'fecha_nacimiento.before'   => 'Debe ser mayor de 18 años.',

            'foto_perfil.image'         => 'El archivo debe ser una imagen.',
            'foto_perfil.max'           => 'La imagen no puede superar los 2 MB.',

            'acepta_terminos.required'  => 'Debe aceptar los términos y condiciones para continuar.',
            'acepta_terminos.accepted'  => 'Debe aceptar los términos y condiciones para continuar.',
        ];
    }

    /**
     * Nombres legibles para los atributos en mensajes de error.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'telefono_celular'          => 'teléfono celular',
            'fecha_nacimiento'          => 'fecha de nacimiento',
            'foto_perfil'               => 'foto de perfil',
            'acepta_terminos'           => 'aceptación de términos',
        ];
    }
}