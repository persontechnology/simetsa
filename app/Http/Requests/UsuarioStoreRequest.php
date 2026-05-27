<?php
// app/Http/Requests/UsuarioStoreRequest.php

namespace App\Http\Requests;

use App\Enums\RolSistema;
use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para crear un nuevo usuario del SIMETSA desde el backoffice.
 *
 * Valida User (name, email, password), múltiples roles (Spatie permite
 * varios por usuario) y PerfilUsuario (cedula, telefonos, etc.) en una
 * sola petición.
 */
class UsuarioStoreRequest extends FormRequest
{
    /**
     * Autorización de la petición — la policy fina ya se aplica en el
     * controlador vía authorizeResource (UserPolicy::create).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // ===== Datos de cuenta =====
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // ===== Roles del sistema (Spatie: varios por usuario) =====
            'roles' => [
                'required', 'array', 'min:1',
                // Defensa en profundidad: solo super_admin puede asignar super_admin
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_array($value)
                        && in_array(RolSistema::SuperAdmin->value, $value, true)
                        && !$this->user()->can('asignar-rol-super-admin')) {
                        $fail('No tiene permiso para asignar el rol Super Administrador.');
                    }
                },
            ],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name'),
            ],

            // ===== Datos personales =====
            'cedula' => [
                'required', 'string', 'size:10',
                'unique:perfiles_usuario,cedula',
                new CedulaEcuatoriana(),
            ],
            'telefono'         => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'telefono_celular' => ['required', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:-18 years'],
            'genero'           => ['nullable', Rule::in(['M', 'F', 'O', 'ND'])],
            'foto_perfil'      => ['nullable', 'image', 'max:2048'],
            'acepta_terminos'  => ['nullable', 'boolean'],
        ];
    }

    /**
     * Mensajes en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'             => 'El nombre completo es obligatorio.',
            'email.required'            => 'El email es obligatorio.',
            'email.email'               => 'El email no tiene un formato válido.',
            'email.unique'              => 'Ya existe un usuario registrado con este email.',
            'password.required'         => 'La contraseña es obligatoria.',
            'password.min'              => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'        => 'La confirmación de contraseña no coincide.',
            'roles.required'            => 'Debe seleccionar al menos un rol.',
            'roles.min'                 => 'Debe seleccionar al menos un rol.',
            'roles.*.in'                => 'Alguno de los roles seleccionados no es válido.',
            'roles.*.exists'            => 'Alguno de los roles seleccionados ya no existe en el sistema.',
            'cedula.required'           => 'La cédula es obligatoria.',
            'cedula.size'               => 'La cédula debe tener exactamente 10 dígitos.',
            'cedula.unique'             => 'Esta cédula ya está registrada.',
            'telefono_celular.required' => 'El teléfono celular es obligatorio.',
            'fecha_nacimiento.before'   => 'El usuario debe ser mayor de 18 años.',
            'foto_perfil.image'         => 'El archivo debe ser una imagen.',
            'foto_perfil.max'           => 'La imagen no puede superar los 2 MB.',
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
            'roles'            => 'roles',
        ];
    }
}