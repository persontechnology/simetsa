@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('manzanas.index') }}
@endsection

@section('content')


<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Las manzanas codifican la trama urbana de cada zona para asignar personal
    operativo, de control y supervisión (Art. 10). Las cuatro manzanas iniciales
    son cuadrantes de ejemplo: ajustá su codificación y límites a la realidad.
</div>

{{-- Filtro por zona --}}
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="card-header">
        @can('manzanas.crear')
            <a href="{{ route('manzanas.create') }}" class="btn btn-simetsa">
                <i class="bi bi-plus-circle me-1"></i> Nueva manzana
            </a>
        @endcan
    </div>
    <form method="GET" action="{{ route('manzanas.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="zona_id" class="form-label small mb-1">Zona</label>
            <select name="zona_id" id="zona_id" class="form-select">
                <option value="">Todas las zonas</option>
                @foreach($zonas as $z)
                    <option value="{{ $z->id }}" @selected((string) $zonaId === (string) $z->id)>{{ $z->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-simetsa flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="{{ route('manzanas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div></div>

{{-- Mapa --}}
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div id="mapa-manzanas" style="height: 440px; border-radius:.5rem;" class="border"></div>
</div></div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Color</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Zona</th>
                    <th>Geometría</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($manzanas as $m)
                    <tr>
                        <td><span class="d-inline-block rounded" style="width:20px;height:20px;background:{{ $m->color }};"></span></td>
                        <td><code>{{ $m->codigo }}</code></td>
                        <td>
                            {{ $m->nombre ?? '—' }}
                            @if($m->descripcion)<div class="small text-muted">{{ Str::limit($m->descripcion, 60) }}</div>@endif
                        </td>
                        <td><span class="badge" style="background:{{ $m->zona?->color }};">{{ $m->zona?->nombre }}</span></td>
                        <td>
                            @if($m->tieneGeometria())
                                <span class="badge bg-success">{{ count($m->poligono) }} vértices</span>
                            @else
                                <span class="badge bg-warning text-dark">Sin polígono</span>
                            @endif
                        </td>
                        <td>
                            @if($m->activo)<span class="badge bg-success">Activa</span>
                            @else<span class="badge bg-secondary">Inactiva</span>@endif
                        </td>
                        <td class="text-end">
                            @can('manzanas.editar')
                                <a href="{{ route('manzanas.edit', $m) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('manzanas.eliminar')
                                <form method="POST" action="{{ route('manzanas.destroy', $m) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('¿Eliminar la manzana {{ $m->codigo }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay manzanas con este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($manzanas->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $manzanas->links() }}</div>
    @endif
</div>


@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
(function () {
    const manzanas = @json($manzanasMapa);
    const zonas    = @json($zonasMapa);

    const map = L.map('mapa-manzanas').setView([-1.0458, -78.5916], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '© OpenStreetMap'
    }).addTo(map);

    const grupo = [];

    // Zonas de fondo (tenues, no interactivas)
    zonas.forEach(z => {
        if (z.poligono && z.poligono.length >= 3) {
            L.polygon(z.poligono, {
                color: z.color, weight: 1, fillOpacity: 0.03, dashArray: '4 4', interactive: false
            }).addTo(map);
        }
    });

    // Manzanas
    manzanas.forEach(m => {
        if (m.poligono && m.poligono.length >= 3) {
            const poly = L.polygon(m.poligono, { color: m.color, weight: 2, fillOpacity: 0.25 })
                          .addTo(map)
                          .bindPopup('<strong>' + m.codigo + '</strong>' + (m.nombre ? '<br>' + m.nombre : ''));
            grupo.push(poly);
        }
    });

    if (grupo.length) {
        try { map.fitBounds(L.featureGroup(grupo).getBounds().pad(0.2)); } catch (e) { /* noop */ }
    }
    setTimeout(() => map.invalidateSize(), 200);
})();
</script>
@endpush
@endsection