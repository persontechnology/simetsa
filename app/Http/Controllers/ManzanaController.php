<?php
// app/Http/Controllers/ManzanaController.php

namespace App\Http\Controllers;

use App\Http\Requests\ManzanaStoreRequest;
use App\Http\Requests\ManzanaUpdateRequest;
use App\Models\Manzana;
use App\Models\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
// removed unused middleware imports
use Illuminate\View\View;

/**
 * Controlador CRUD de Manzanas (codificación urbana — Art. 10).
 *
 * El index permite filtrar por zona y dibuja en un mapa los polígonos de
 * las manzanas sobre el polígono de fondo de las zonas.
 */
class ManzanaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manzanas.ver')->only('index');
        $this->middleware('permission:manzanas.crear')->only(['create', 'store']);
        $this->middleware('permission:manzanas.editar')->only(['edit', 'update']);
        $this->middleware('permission:manzanas.eliminar')->only('destroy');
    }

    public function index(Request $request): View
    {
        $zonaId = $request->input('zona_id');

        $query = Manzana::with('zona')->orderBy('codigo');
        if ($zonaId) {
            $query->where('zona_id', $zonaId);
        }
        $manzanas = $query->paginate(30)->withQueryString();

        // Polígonos de manzanas para el mapa
        $manzanasMapa = Manzana::with('zona')
            ->when($zonaId, fn ($q) => $q->where('zona_id', $zonaId))
            ->get()
            ->filter->tieneGeometria()
            ->map(fn (Manzana $m) => [
                'codigo'   => $m->codigo,
                'nombre'   => $m->nombre,
                'poligono' => $m->poligono,
                'color'    => $m->color,
            ])->values();

        $zonas     = Zona::activas()->orderBy('nombre')->get();
        $zonasMapa = $this->zonasParaMapa();

        return view('manzanas.index', compact('manzanas', 'manzanasMapa', 'zonas', 'zonasMapa', 'zonaId'));
    }

    public function create(): View
    {
        return view('manzanas.create', [
            'manzana'         => null,
            'zonas'           => Zona::activas()->orderBy('nombre')->get(),
            'zonasReferencia' => $this->zonasParaMapa(),
        ]);
    }

    public function store(ManzanaStoreRequest $request): RedirectResponse
    {
        $manzana = Manzana::create($request->validated());
        return redirect()->route('manzanas.index')
            ->with('success', "Manzana '{$manzana->codigo}' creada correctamente.");
    }

    public function edit(Manzana $manzana): View
    {
        return view('manzanas.edit', [
            'manzana'         => $manzana->load('zona'),
            'zonas'           => Zona::activas()->orderBy('nombre')->get(),
            'zonasReferencia' => $this->zonasParaMapa(),
        ]);
    }

    public function update(ManzanaUpdateRequest $request, Manzana $manzana): RedirectResponse
    {
        $manzana->update($request->validated());
        return redirect()->route('manzanas.index')
            ->with('success', "Manzana '{$manzana->codigo}' actualizada correctamente.");
    }

    public function destroy(Manzana $manzana): RedirectResponse
    {
        $manzana->delete(); // soft delete
        return redirect()->route('manzanas.index')
            ->with('success', "Manzana '{$manzana->codigo}' eliminada (soft delete).");
    }

    /**
     * Polígonos de zonas activas para dibujar como capa de fondo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function zonasParaMapa(): array
    {
        return Zona::activas()->get()
            ->filter->tieneGeometria()
            ->map(fn (Zona $z) => [
                'nombre'   => $z->nombre,
                'poligono' => $z->poligono,
                'color'    => $z->color,
            ])->values()->toArray();
    }
}