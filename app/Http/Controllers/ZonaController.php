<?php
// app/Http/Controllers/ZonaController.php

namespace App\Http\Controllers;

use App\Http\Requests\ZonaStoreRequest;
use App\Http\Requests\ZonaUpdateRequest;
use App\Models\Zona;
use Illuminate\Http\RedirectResponse;
// removed unused middleware imports
use Illuminate\View\View;

/**
 * Controlador CRUD de Zonas tarifadas.
 *
 * El index entrega además un arreglo JSON con la geometría de todas las
 * zonas activas, que la vista usa para dibujarlas en un mapa Leaflet.
 */
class ZonaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:zonas.ver')->only('index');
        $this->middleware('permission:zonas.crear')->only(['create', 'store']);
        $this->middleware('permission:zonas.editar')->only(['edit', 'update']);
        $this->middleware('permission:zonas.eliminar')->only('destroy');
    }

    public function index(): View
    {
        $zonas = Zona::orderBy('nombre')->get();

        // Estructura compacta para dibujar en el mapa (solo lo necesario)
        $zonasMapa = $zonas->filter->tieneGeometria()->map(fn (Zona $z) => [
            'nombre'   => $z->nombre,
            'color'    => $z->color,
            'poligono' => $z->poligono,
        ])->values();

        return view('zonas.index', compact('zonas', 'zonasMapa'));
    }

    public function create(): View
    {
        return view('zonas.create', ['zona' => null]);
    }

    public function store(ZonaStoreRequest $request): RedirectResponse
    {
        $zona = Zona::create($request->validated());
        return redirect()->route('zonas.index')
            ->with('success', "Zona '{$zona->nombre}' creada correctamente.");
    }

    public function edit(Zona $zona): View
    {
        return view('zonas.edit', compact('zona'));
    }

    public function update(ZonaUpdateRequest $request, Zona $zona): RedirectResponse
    {
        $zona->update($request->validated());
        return redirect()->route('zonas.index')
            ->with('success', "Zona '{$zona->nombre}' actualizada correctamente.");
    }

    public function destroy(Zona $zona): RedirectResponse
    {
        $zona->delete(); // soft delete
        return redirect()->route('zonas.index')
            ->with('success', "Zona '{$zona->nombre}' eliminada (soft delete).");
    }
}