@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('calles.index') }}
@endsection

@section('content')


{{-- Filtro por zona --}}
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="card-header">
        @can('calles.crear')
            <a href="{{ route('calles.create') }}" class="btn btn-simetsa">
                <i class="bi bi-plus-circle me-1"></i> Nueva calle
            </a>
        @endcan
    </div>
    <form method="GET" action="{{ route('calles.index') }}" class="row g-2 align-items-end">
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
            <a href="{{ route('calles.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div></div>

{{-- Mapa con polilíneas de calles + polígonos de zonas de fondo --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div id="mapa-calles" style="height: 440px; border-radius:.5rem;" class="border"></div>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Calle</th>
                    <th>Tramo (Art. 16)</th>
                    <th>Zona</th>
                    <th>Sentido</th>
                    <th>Costado</th>
                    <th>Trazado</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($calles as $c)
                    <tr>
                        <td>
                            <strong>{{ $c->nombre }}</strong>
                            <div class="small text-muted"><code>{{ $c->codigo }}</code></div>
                        </td>
                        <td class="small">
                            @if($c->desde || $c->hasta)
                                {{ $c->desde ?? '—' }} <i class="bi bi-arrow-right"></i> {{ $c->hasta ?? '—' }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge" style="background:{{ $c->zona?->color }};">{{ $c->zona?->nombre }}</span></td>
                        <td class="small">{{ $c->sentido_etiqueta }}</td>
                        <td class="small">{{ $c->lado_etiqueta }}</td>
                        <td>
                            @if($c->tieneGeometria())
                                <span class="badge bg-success">{{ count($c->polilinea) }} puntos</span>
                            @else
                                <span class="badge bg-warning text-dark">Sin trazar</span>
                            @endif
                        </td>
                        <td>
                            @if($c->activo)<span class="badge bg-success">Activa</span>
                            @else<span class="badge bg-secondary">Inactiva</span>@endif
                        </td>
                        <td class="text-end">
                            @can('calles.editar')
                                <a href="{{ route('calles.edit', $c) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('calles.eliminar')
                                <form method="POST" action="{{ route('calles.destroy', $c) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('¿Eliminar la calle {{ $c->nombre }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay calles con este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($calles->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $calles->links() }}</div>
    @endif
</div>


@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
(function () {
    const calles = @json($callesMapa);
    const zonas  = @json($zonasMapa);

    const map = L.map('mapa-calles').setView([-1.0458, -78.5916], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '© OpenStreetMap'
    }).addTo(map);

    const grupo = [];

    // Zonas de fondo (tenues)
    zonas.forEach(z => {
        if (z.poligono && z.poligono.length >= 3) {
            L.polygon(z.poligono, {
                color: z.color, weight: 1, fillOpacity: 0.05, dashArray: '4 4', interactive: false
            }).addTo(map);
        }
    });

    // Polilíneas de calles
    calles.forEach(c => {
        if (c.polilinea && c.polilinea.length >= 2) {
            const linea = L.polyline(c.polilinea, { color: c.color, weight: 4 })
                           .addTo(map)
                           .bindPopup(c.nombre);
            grupo.push(linea);
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