<?php
// app/Http/Controllers/ParametroController.php

namespace App\Http\Controllers;

use App\Http\Requests\ParametroUpdateRequest;
use App\Http\Requests\UpdateParametroRequest;
use App\Models\Parametro;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador de gestión de parámetros del sistema.
 *
 * Operaciones expuestas:
 *  - index: listado agrupado por categoría.
 *  - edit:  formulario de edición de un parámetro.
 *  - update: persiste el cambio.
 *
 * Crear y eliminar parámetros NO se expone desde la UI: el catálogo
 * está definido en ParametroSeeder y referenciado por código.
 * Autorización vía middleware en routes (permission:parametros.ver / editar).
 */
class ParametroController extends Controller
{
    /**
     * Lista todos los parámetros agrupados por categoría.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $parametros = Parametro::with('ultimaBitacora.user')
            ->orderBy('categoria')
            ->orderBy('clave')
            ->get()
            ->groupBy('categoria');

        return view('parametros.index', [
            'parametrosPorCategoria' => $parametros,
        ]);
    }

    /**
     * Formulario de edición de un parámetro.
     * Bloquea acceso si el parámetro está marcado como no editable.
     *
     * @param  \App\Models\Parametro  $parametro
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Parametro $parametro): View|RedirectResponse
    {
        if (!$parametro->editable) {
            return redirect()
                ->route('parametros.index')
                ->with('error', "El parámetro '{$parametro->clave}' no es editable desde la interfaz.");
        }

        return view('parametros.edit', [
            'parametro' => $parametro,
        ]);
    }

    /**
     * Persiste el nuevo valor del parámetro.
     *
     * @param  \App\Http\Requests\UpdateParametroRequest  $request
     * @param  \App\Models\Parametro                       $parametro
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateParametroRequest $request, Parametro $parametro): RedirectResponse
    {
        $parametro->update($request->validated());

        return redirect()
            ->route('parametros.index')
            ->with('success', "Parámetro '{$parametro->clave}' actualizado correctamente.");
    }
}