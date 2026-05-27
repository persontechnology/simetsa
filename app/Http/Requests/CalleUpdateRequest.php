<?php
// app/Http/Requests/CalleUpdateRequest.php

namespace App\Http\Requests;

use App\Models\Calle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('calles.editar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'polilinea' => (new CalleStoreRequest())->decodificarPolilinea($this->input('polilinea')),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('calle')?->id;

        return [
            'zona_id'              => ['required', 'integer', 'exists:zonas,id'],
            'codigo'               => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_]*$/',
                                        Rule::unique('calles', 'codigo')->ignore($id)->whereNull('deleted_at')],
            'nombre'               => ['required', 'string', 'max:150'],
            'desde'                => ['nullable', 'string', 'max:150'],
            'hasta'                => ['nullable', 'string', 'max:150'],
            'sentido'              => ['required', Rule::in(array_keys(Calle::listadoSentidos()))],
            'lado_estacionamiento' => ['required', Rule::in(array_keys(Calle::listadoLados()))],
            'activo'               => ['required', 'boolean'],

            'polilinea'     => ['nullable', 'array', 'min:2'],
            'polilinea.*'   => ['array', 'size:2'],
            'polilinea.*.0' => ['numeric', 'between:-90,90'],
            'polilinea.*.1' => ['numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return (new CalleStoreRequest())->messages();
    }
}