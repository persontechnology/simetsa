@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('plazas.index') }}
@endsection

@section('content')


{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div class="card-header">
        @can('plazas.crear')
            <a href="{{ route('plazas.create') }}" class="btn btn-simetsa">
                <i class="bi bi-plus-circle me-1"></i> Nueva plaza
            </a>
        @endcan
    </div>
    <form method="GET" action="{{ route('plazas.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="zona_id" class="form-label small mb-1">Zona</label>
            <select name="zona_id" id="zona_id" class="form-select">
                <option value="">Todas las zonas</option>
                @foreach($zonas as $z)
                    <option value="{{ $z->id }}" @selected((string)($filtros['zona_id'] ?? '') === (string)$z->id)>{{ $z->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="tipo_plaza_id" class="form-label small mb-1">Tipo de plaza</label>
            <select name="tipo_plaza_id" id="tipo_plaza_id" class="form-select">
                <option value="">Todos los tipos</option>
                @foreach($tiposPlaza as $t)
                    <option value="{{ $t->id }}" @selected((string)($filtros['tipo_plaza_id'] ?? '') === (string)$t->id)>{{ $t->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-simetsa flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="{{ route('plazas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div></div>

{{-- Mapa --}}
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <div id="mapa-plazas" style="height: 440px; border-radius:.5rem;" class="border"></div>
</div></div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>N.º</th>
                    <th>Tipo</th>
                    <th>Zona / Calle</th>
                    <th>Dimensiones</th>
                    <th>Orientación</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plazas as $p)
                    <tr>
                        <td><code>{{ $p->codigo }}</code></td>
                        <td>{{ $p->numero ?? '—' }}</td>
                        <td>
                            <span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:{{ $p->tipoPlaza?->color_mapa }};"></span>
                            {{ $p->tipoPlaza?->nombre }}
                        </td>
                        <td class="small">
                            {{ $p->zona?->nombre }}
                            @if($p->calle)<div class="text-muted">{{ $p->calle->nombre }}</div>@endif
                        </td>
                        <td>{{ $p->dimensiones ?? '—' }}</td>
                        <td class="small">{{ $p->orientacion_etiqueta }}</td>
                        <td>
                            @if($p->tieneUbicacion())
                                <span class="badge bg-success"><i class="bi bi-geo-alt-fill"></i> Ubicada</span>
                            @else
                                <span class="badge bg-warning text-dark">Sin ubicar</span>
                            @endif
                        </td>
                        <td>
                            @if($p->activo)<span class="badge bg-success">Activa</span>
                            @else<span class="badge bg-secondary">Inactiva</span>@endif
                        </td>
                        <td class="text-end">
                            @can('plazas.editar')
                                <a href="{{ route('plazas.edit', $p) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('plazas.eliminar')
                                <form method="POST" action="{{ route('plazas.destroy', $p) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('¿Eliminar la plaza {{ $p->codigo }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No hay plazas con este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($plazas->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $plazas->links() }}</div>
    @endif
</div>

@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
(function () {
    const plazas = @json($plazasMapa);
    const zonas  = @json($zonasMapa);

    const map = L.map('mapa-plazas').setView([-1.0458, -78.5916], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '© OpenStreetMap'
    }).addTo(map);

    // Zonas de fondo
    zonas.forEach(z => {
        if (z.poligono && z.poligono.length >= 3) {
            L.polygon(z.poligono, {
                color: z.color, weight: 1, fillOpacity: 0.04, dashArray: '4 4', interactive: false
            }).addTo(map);
        }
    });

    // Plazas como círculos coloreados por tipo
    const grupo = [];
    plazas.forEach(p => {
        const marker = L.circleMarker([p.lat, p.lng], {
            radius: 7, color: '#fff', weight: 1, fillColor: p.color, fillOpacity: 0.95
        }).addTo(map).bindPopup('<strong>' + p.codigo + '</strong><br>' + (p.tipo || ''));
        grupo.push(marker);
    });

    if (grupo.length) {
        try { map.fitBounds(L.featureGroup(grupo).getBounds().pad(0.3)); } catch (e) { /* noop */ }
    }
    setTimeout(() => map.invalidateSize(), 200);
})();
</script>
@endpush
@endsection