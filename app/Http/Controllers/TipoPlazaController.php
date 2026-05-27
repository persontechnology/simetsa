<?php
// app/Http/Controllers/TipoPlazaController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoPlazaRequest;
use App\Http\Requests\UpdateTipoPlazaRequest;
use App\Models\TipoPlaza;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Controlador CRUD de TipoPlaza.
 *
 * Autorización vía HasMiddleware (Laravel 11): cada acción declara
 * el permiso Spatie que requiere.
 */
class TipoPlazaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tipos_plaza.ver')->only('index');
        $this->middleware('permission:tipos_plaza.crear')->only(['create', 'store']);
        $this->middleware('permission:tipos_plaza.editar')->only(['edit', 'update']);
        $this->middleware('permission:tipos_plaza.eliminar')->only('destroy');
    }

    public function index(): View
    {
        $tipos = TipoPlaza::orderBy('nombre')->paginate(20);
        return view('tipos-plaza.index', compact('tipos'));
    }

    public function create(): View
    {
        return view('tipos-plaza.create');
    }

    public function store(StoreTipoPlazaRequest $request): RedirectResponse
    {
        $tipo = TipoPlaza::create($request->validated());
        return redirect()->route('tipos-plaza.index')
            ->with('success', "Tipo de plaza '{$tipo->nombre}' creado correctamente.");
    }

    public function edit(TipoPlaza $tipo_plaza): View
    {
        return view('tipos-plaza.edit', ['tipoPlaza' => $tipo_plaza]);
    }

    public function update(UpdateTipoPlazaRequest $request, TipoPlaza $tipo_plaza): RedirectResponse
    {
        $tipo_plaza->update($request->validated());
        return redirect()->route('tipos-plaza.index')
            ->with('success', "Tipo de plaza '{$tipo_plaza->nombre}' actualizado.");
    }

    public function destroy(TipoPlaza $tipo_plaza): RedirectResponse
    {
        $tipo_plaza->delete();
        return redirect()->route('tipos-plaza.index')
            ->with('success', "Tipo de plaza '{$tipo_plaza->nombre}' desactivado (soft delete).");
    }
}