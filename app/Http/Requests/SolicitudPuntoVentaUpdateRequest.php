<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudPuntoVentaUpdateRequest extends FormRequest
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
        return [
            'cedula' => ['required', 'string', 'size:10', new CedulaEcuatoriana],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'nombre_comercial' => ['required', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'digits:13'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'direccion_local' => ['required', 'string', 'max:255'],
            'referencia_ubicacion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
    public function messages(): array
    {
        return new SolicitudPuntoVentaStoreRequest()->messages();
    }
}