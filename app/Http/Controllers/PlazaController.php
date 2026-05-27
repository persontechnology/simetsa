<?php
// app/Http/Controllers/PlazaController.php

namespace App\Http\Controllers;

use App\Http\Requests\PlazaStoreRequest;
use App\Http\Requests\PlazaUpdateRequest;
use App\Models\Calle;
use App\Models\Manzana;
use App\Models\Plaza;
use App\Models\TipoPlaza;
use App\Models\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
// removed unused middleware imports
use Illuminate\View\View;

/**
 * Controlador CRUD de Plazas de estacionamiento.
 *
 * El index filtra por zona y tipo, y dibuja las plazas como marcadores
 * coloreados por tipo sobre el polígono de fondo de las zonas.
 */
class PlazaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:plazas.ver')->only('index');
        $this->middleware('permission:plazas.crear')->only(['create', 'store']);
        $this->middleware('permission:plazas.editar')->only(['edit', 'update']);
        $this->middleware('permission:plazas.eliminar')->only('destroy');
    }

    public function index(Request $request): View
    {
        $zonaId = $request->input('zona_id');
        $tipoId = $request->input('tipo_plaza_id');

        $query = Plaza::with(['zona', 'calle', 'tipoPlaza'])->orderBy('codigo');
        if ($zonaId) $query->where('zona_id', $zonaId);
        if ($tipoId) $query->where('tipo_plaza_id', $tipoId);

        $plazas = $query->paginate(30)->withQueryString();

        // Marcadores para el mapa, coloreados por tipo
        $plazasMapa = Plaza::with('tipoPlaza')
            ->when($zonaId, fn ($q) => $q->where('zona_id', $zonaId))
            ->when($tipoId, fn ($q) => $q->where('tipo_plaza_id', $tipoId))
            ->get()
            ->filter->tieneUbicacion()
            ->map(fn (Plaza $p) => [
                'codigo' => $p->codigo,
                'tipo'   => $p->tipoPlaza?->nombre,
                'lat'    => $p->latitud,
                'lng'    => $p->longitud,
                'color'  => $p->tipoPlaza?->color_mapa ?? '#0d4a8f',
            ])->values();

        return view('plazas.index', [
            'plazas'      => $plazas,
            'plazasMapa'  => $plazasMapa,
            'zonas'       => Zona::activas()->orderBy('nombre')->get(),
            'tiposPlaza'  => TipoPlaza::activos()->orderBy('nombre')->get(),
            'zonasMapa'   => $this->zonasParaMapa(),
            'filtros'     => ['zona_id' => $zonaId, 'tipo_plaza_id' => $tipoId],
        ]);
    }

    public function create(): View
    {
        return view('plazas.create', array_merge($this->datosFormulario(null), [
            'plaza' => null,
        ]));
    }

    public function store(PlazaStoreRequest $request): RedirectResponse
    {
        $plaza = Plaza::create($request->validated());
        return redirect()->route('plazas.index')
            ->with('success', "Plaza '{$plaza->codigo}' creada correctamente.");
    }

    public function edit(Plaza $plaza): View
    {
        return view('plazas.edit', array_merge($this->datosFormulario($plaza), [
            'plaza' => $plaza->load(['zona', 'calle', 'manzana', 'tipoPlaza']),
        ]));
    }

    public function update(PlazaUpdateRequest $request, Plaza $plaza): RedirectResponse
    {
        $plaza->update($request->validated());
        return redirect()->route('plazas.index')
            ->with('success', "Plaza '{$plaza->codigo}' actualizada correctamente.");
    }

    public function destroy(Plaza $plaza): RedirectResponse
    {
        $plaza->delete(); // soft delete
        return redirect()->route('plazas.index')
            ->with('success', "Plaza '{$plaza->codigo}' eliminada (soft delete).");
    }

    /**
     * Datos comunes para los formularios create/edit.
     *
     * @return array<string, mixed>
     */
    private function datosFormulario(?Plaza $plaza): array
    {
        return [
            'zonas'            => Zona::activas()->orderBy('nombre')->get(),
            'calles'           => Calle::activas()->with('zona')->orderBy('nombre')->get(),
            'manzanas'         => Manzana::activas()->orderBy('codigo')->get(),
            'tiposPlaza'       => TipoPlaza::activos()->orderBy('nombre')->get(),
            'orientaciones'    => Plaza::listadoOrientaciones(),
            'zonasReferencia'  => $this->zonasParaMapa(),
            'plazasReferencia' => $this->plazasParaMapa($plaza?->id),
            'callesReferencia' => $this->callesParaMapa(),
            'codigosPorCalle'  => $this->codigosPorCalle(),
            'dimensionesPorTipo' => $this->dimensionesPorTipo(),
        ];
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

    /**
    * Plazas ya ubicadas, para dibujarlas como referencia en el mapa
    * y evitar superposiciones. Excluye la plaza en edición.
    *
    * @param  int|null  $exceptoId
    * @return array<int, array<string, mixed>>
    */
    private function plazasParaMapa(?int $exceptoId = null): array
    {
        return Plaza::with('tipoPlaza')
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->get()
            ->filter->tieneUbicacion()
            ->map(fn (Plaza $p) => [
                'codigo' => $p->codigo,
                'lat'    => $p->latitud,
                'lng'    => $p->longitud,
                'color'  => $p->tipoPlaza?->color_mapa ?? '#0d4a8f',
            ])->values()->toArray();
    }

    /**
     * Polilíneas de calles activas para dibujarlas como guía en el mapa.
     *
     * @return array<int, array<string, mixed>>
     */
    private function callesParaMapa(): array
    {
        return Calle::activas()->get()
            ->filter->tieneGeometria()
            ->map(fn (Calle $c) => ['polilinea' => $c->polilinea])
            ->values()->toArray();
    }

    /**
     * Mapa de calle_id => [códigos de plazas existentes], para sugerir
     * el siguiente código correlativo en el formulario.
     *
     * @return array<int, array<int, string>>
     */
    private function codigosPorCalle(): array
    {
        return Plaza::query()
            ->whereNotNull('calle_id')
            ->get(['calle_id', 'codigo'])
            ->groupBy('calle_id')
            ->map(fn ($grupo) => $grupo->pluck('codigo')->values()->toArray())
            ->toArray();
    }

    /**
     * Mapa de tipo_plaza_id => {ancho, largo} sugeridos, para autocompletar
     * las dimensiones en el formulario al elegir el tipo de plaza.
     *
     * @return array<int, array<string, float|null>>
     */
    private function dimensionesPorTipo(): array
    {
        return TipoPlaza::activos()->get()
            ->mapWithKeys(fn (TipoPlaza $t) => [
                $t->id => [
                    'ancho' => $t->ancho_sugerido !== null ? (float) $t->ancho_sugerido : null,
                    'largo' => $t->largo_sugerido !== null ? (float) $t->largo_sugerido : null,
                ],
            ])->toArray();
    }
}