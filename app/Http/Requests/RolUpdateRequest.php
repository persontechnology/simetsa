<?php
// app/Http/Requests/RolUpdateRequest.php

namespace App\Http\Requests;

use App\Enums\RolSistema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Form Request para actualizar un rol existente.
 *
 * - Los roles del sistema (los 6 del Enum RolSistema) NO permiten cambiar
 *   el nombre, solo los permisos.
 * - El rol super_admin no permite cambiar permisos (siempre tiene todos);
 *   la policy ya bloquea el update entero pero aquí lo reforzamos.
 */
class RolUpdateRequest extends FormRequest
{
    /**
     * Autoriza vía RolPolicy::update.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $rol = $this->route('rol');
        return $rol && $this->user()->can('update', $rol);
    }

    /**
     * Reglas de validación dinámicas según si es rol del sistema.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rol = $this->route('rol');
        $reglas = [];

        // Solo permitir cambiar el nombre si NO es un rol del sistema
        if ($rol && !$this->esRolDelSistema($rol->name)) {
            $reglas['name'] = [
                'required', 'string', 'min:3', 'max:50',
                'regex:/^[a-z][a-z0-9_]+$/',
                Rule::unique('roles', 'name')->ignore($rol->id),
            ];
        }

        $reglas['permisos']   = ['nullable', 'array'];
        $reglas['permisos.*'] = ['string', 'exists:permissions,name'];

        return $reglas;
    }

    /**
     * Mensajes en español (compartidos con Store).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return (new RolStoreRequest())->messages();
    }

    /**
     * @param  string  $nombre
     * @return bool
     */
    private function esRolDelSistema(string $nombre): bool
    {
        return in_array(
            $nombre,
            array_column(RolSistema::cases(), 'value'),
            true
        );
    }
}