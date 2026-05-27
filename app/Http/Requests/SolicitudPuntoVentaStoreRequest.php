<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;

class SolicitudPuntoVentaStoreRequest extends FormRequest
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
        return [
            'cedula.required' => 'La cédula es obligatoria.',
            'cedula.string' => 'La cédula debe ser una cadena de texto.',
            'cedula.size' => 'La cédula debe tener exactamente 10 caracteres.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.string' => 'Los nombres deben ser una cadena de texto.',
            'nombres.max' => 'Los nombres no pueden exceder 255 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.string' => 'Los apellidos deben ser una cadena de texto.',
            'apellidos.max' => 'Los apellidos no pueden exceder 255 caracteres.',
            'telefono.string' => 'El teléfono debe ser una cadena de texto.',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
            'telefono_celular.string' => 'El teléfono celular debe ser una cadena de texto.',
            'telefono_celular.max' => 'El teléfono celular no puede exceder 20 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'nombre_comercial.string' => 'El nombre comercial debe ser una cadena de texto.',
            'nombre_comercial.max' => 'El nombre comercial no puede exceder 255 caracteres.',
            'ruc.string' => 'El RUC debe ser una cadena de texto.',
            'ruc.digits' => 'El RUC debe tener exactamente 13 dígitos.',
            'direccion.string' => 'La dirección debe ser una cadena de texto.',
            'direccion.max' => 'La dirección no puede exceder 255 caracteres.',
            'direccion_local.required' => 'La dirección del local es obligatoria.',
            'direccion_local.string' => 'La dirección del local debe ser una cadena de texto.',
            'direccion_local.max' => 'La dirección del local no puede exceder 255 caracteres.',
            'referencia_ubicacion.string' => 'La referencia de ubicación debe ser una cadena de texto.',
            'referencia_ubicacion.max' => 'La referencia de ubicación no puede exceder 255 caracteres.',
            'latitud.numeric' => 'La latitud debe ser un número.',
            'latitud.between' => 'La latitud debe estar entre -90 y 90.',
            'longitud.numeric' => 'La longitud debe ser un número.',
            'longitud.between' => 'La longitud debe estar entre -180 y 180.',
            'observaciones.string' => 'Las observaciones deben ser una cadena de texto.',
        ];
    }
}