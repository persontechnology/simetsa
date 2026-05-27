<?php
// app/Http/Requests/DocumentoAgenteStoreRequest.php

namespace App\Http\Requests;

use App\Models\DocumentoAgente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para cargar un documento a una solicitud de agente.
 */
class DocumentoAgenteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agentes.editar') ?? false;
    }

    public function rules(): array
    {
        return [
            'tipo'    => ['required', Rule::in(array_keys(DocumentoAgente::listadoTipos()))],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5 MB
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.mimes' => 'El documento debe ser PDF o imagen (jpg, jpeg, png).',
            'archivo.max'   => 'El documento no puede superar los 5 MB.',
        ];
    }
}