<?php
// app/Http/Requests/RolStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Role;

/**
 * Form Request para crear un rol nuevo (custom, definido por el usuario admin).
 *
 * Los 6 roles del Enum RolSistema se crean por seeder y no se gestionan
 * mediante este request — esta vía sirve para crear roles adicionales
 * según evolucione la operación del SIMETSA.
 */
class RolStoreRequest extends FormRequest
{
    /**
     * Autoriza vía RolPolicy::create.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // snake_case, ≥3 caracteres, comienza con letra
            'name' => [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-z][a-z0-9_]+$/',
                'unique:roles,name',
            ],
            'permisos'   => ['nullable', 'array'],
            'permisos.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.regex'    => 'El nombre debe estar en formato snake_case (minúsculas, números y guion bajo).',
            'name.unique'   => 'Ya existe un rol con este nombre.',
            'name.min'      => 'El nombre debe tener al menos 3 caracteres.',
            'permisos.*.exists' => 'Alguno de los permisos seleccionados no existe en el catálogo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'     => 'nombre del rol',
            'permisos' => 'permisos',
        ];
    }
}