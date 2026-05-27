<?php
// app/Http/Requests/UsuarioUpdateRequest.php

namespace App\Http\Requests;

use App\Enums\RolSistema;
use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para actualizar un usuario existente.
 *
 * Diferencias respecto a UsuarioStoreRequest:
 *  - Contraseña opcional.
 *  - Reglas unique de email/cedula ignoran al registro actual.
 */
class UsuarioUpdateRequest extends FormRequest
{
    /**
     * Autoriza la petición vía UserPolicy::update.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');
        return $usuario && $this->user()->can('update', $usuario);
    }

    /**
     * Reglas de validación con ignore en uniques.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuario  = $this->route('usuario');
        $userId   = $usuario?->id;
        $perfilId = $usuario?->perfil?->id;

        return [
            // ===== Datos de cuenta =====
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],

            // ===== Roles =====
            'roles' => [
                'required', 'array', 'min:1',
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
                Rule::unique('perfiles_usuario', 'cedula')->ignore($perfilId),
                new CedulaEcuatoriana(),
            ],
            'telefono'         => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'telefono_celular' => ['required', 'string', 'max:15', 'regex:/^[0-9+\-\s]+$/'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:-18 years'],
            'genero'           => ['nullable', Rule::in(['M', 'F', 'O', 'ND'])],
            'foto_perfil'      => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Reutiliza los mensajes del request de creación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return (new UsuarioStoreRequest())->messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return (new UsuarioStoreRequest())->attributes();
    }
}