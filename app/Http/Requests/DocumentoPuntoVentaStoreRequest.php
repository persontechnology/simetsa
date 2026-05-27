<?php

namespace App\Http\Requests;

use App\Models\DocumentoPuntoVenta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentoPuntoVentaStoreRequest extends FormRequest
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
            'tipo' => ['required', Rule::in(array_keys(DocumentoPuntoVenta::listadoTipos()))],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }
}