<?php

namespace App\Http\Requests;

use App\Models\Parametro;
use Illuminate\Foundation\Http\FormRequest;

class UpdateParametroRequest extends FormRequest
{
    /**
     * Autoriza si el usuario tiene el permiso 'parametros.editar'
     * Y el parámetro está marcado como editable.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $parametro = $this->route('parametro');

        if (!$parametro instanceof Parametro || !$parametro->editable) {
            return false;
        }

        return $this->user()?->can('parametros.editar') ?? false;
    }

    /**
     * Reglas de validación dinámicas según el tipo del parámetro.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $parametro = $this->route('parametro');

        $reglasValor = match ($parametro->tipo) {
            Parametro::TIPO_INTEGER => ['required', 'integer', 'min:0'],
            Parametro::TIPO_DECIMAL => ['required', 'numeric', 'min:0'],
            Parametro::TIPO_BOOLEAN => ['required', 'boolean'],
            default                 => ['required', 'string', 'max:255'],
        };

        return [
            'valor'       => $reglasValor,
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'valor.required' => 'El valor es obligatorio.',
            'valor.integer'  => 'El valor debe ser un número entero.',
            'valor.numeric'  => 'El valor debe ser numérico.',
            'valor.min'      => 'El valor no puede ser negativo.',
            'valor.boolean'  => 'El valor debe ser verdadero o falso.',
        ];
    }
}
