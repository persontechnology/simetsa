<?php
// app/Http/Controllers/CalleController.php

namespace App\Http\Controllers;

use App\Http\Requests\CalleStoreRequest;
use App\Http\Requests\CalleUpdateRequest;
use App\Models\Calle;
use App\Models\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador CRUD de Calles tarifadas (Art. 16).
 *
 * El index permite filtrar por zona y dibuja en un mapa las polilíneas
 * de las calles sobre el polígono de fondo de las zonas.
 */
class CalleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:calles.ver')->only('index');
        $this->middleware('permission:calles.crear')->only(['create', 'store']);
        $this->middleware('permission:calles.editar')->only(['edit', 'update']);
        $this->middleware('permission:calles.eliminar')->only('destroy');
    }

    public function index(Request $request): View
    {
        $zonaId = $request->input('zona_id');

        $query = Calle::with('zona')->orderBy('nombre');
        if ($zonaId) {
            $query->where('zona_id', $zonaId);
        }
        $calles = $query->paginate(30)->withQueryString();

        // Polilíneas para el mapa (coloreadas por la zona)
        $callesMapa = Calle::with('zona')
            ->when($zonaId, fn ($q) => $q->where('zona_id', $zonaId))
            ->get()
            ->filter->tieneGeometria()
            ->map(fn (Calle $c) => [
                'nombre'    => $c->nombre,
                'polilinea' => $c->polilinea,
                'color'     => $c->zona?->color ?? '#0d4a8f',
            ])->values();

        $zonas     = Zona::activas()->orderBy('nombre')->get();
        $zonasMapa = $this->zonasParaMapa();

        return view('calles.index', compact('calles', 'callesMapa', 'zonas', 'zonasMapa', 'zonaId'));
    }

    public function create(): View
    {
        return view('calles.create', [
            'calle'           => null,
            'zonas'           => Zona::activas()->orderBy('nombre')->get(),
            'sentidos'        => Calle::listadoSentidos(),
            'lados'           => Calle::listadoLados(),
            'zonasReferencia' => $this->zonasParaMapa(),
        ]);
    }

    public function store(CalleStoreRequest $request): RedirectResponse
    {
        $calle = Calle::create($request->validated());
        return redirect()->route('calles.index')
            ->with('success', "Calle '{$calle->nombre}' creada correctamente.");
    }

    public function edit(Calle $calle): View
    {
        return view('calles.edit', [
            'calle'           => $calle->load('zona'),
            'zonas'           => Zona::activas()->orderBy('nombre')->get(),
            'sentidos'        => Calle::listadoSentidos(),
            'lados'           => Calle::listadoLados(),
            'zonasReferencia' => $this->zonasParaMapa(),
        ]);
    }

    public function update(CalleUpdateRequest $request, Calle $calle): RedirectResponse
    {
        $calle->update($request->validated());
        return redirect()->route('calles.index')
            ->with('success', "Calle '{$calle->nombre}' actualizada correctamente.");
    }

    public function destroy(Calle $calle): RedirectResponse
    {
        $calle->delete(); // soft delete
        return redirect()->route('calles.index')
            ->with('success', "Calle '{$calle->nombre}' eliminada (soft delete).");
    }

    /**
     * Polígonos de zonas activas (con geometría) para dibujar como
     * capa de fondo / referencia en los mapas.
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