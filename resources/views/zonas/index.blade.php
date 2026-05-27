@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('zonas.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-3 mb-lg-0">
        @can('zonas.crear')
            <a href="{{ route('zonas.create') }}" class="btn btn-link text-dark px-0 py-2 d-flex align-items-center text-decoration-none">
                <i class="bi bi-plus-lg me-2"></i> Nueva zona
            </a>
        @endcan
    </div>
@endsection

@section('content')

{{-- Sección del Mapa --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-2"> {{-- Reducido el padding para maximizar el área del mapa --}}
        <div id="mapa-zonas" style="height: 400px; border-radius: 0.375rem;" class="bg-light"></div>
    </div>
</div>

{{-- Sección de la Tabla --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase fs-7 text-muted">
                <tr>
                    <th style="width: 60px;" class="ps-4">Color</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Geometría</th>
                    <th>Estado</th>
                    <th class="text-end pe-4" style="width: 80px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($zonas as $z)
                    <tr>
                        <td class="ps-4">
                            <span class="d-block rounded-circle shadow-sm"
                                  style="width: 18px; height: 18px; background-color: {{ $z->color }};"
                                  title="{{ $z->color }}"></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border font-monospace px-2 py-1">{{ $z->codigo }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $z->nombre }}</div>
                            @if($z->descripcion)
                                <div class="small text-muted text-truncate" style="max-width: 300px;">
                                    {{ $z->descripcion }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($z->tieneGeometria())
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                    <i class="bi bi-hexagon-fill me-1"></i> {{ count($z->poligono) }} vértices
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Sin polígono
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($z->activo)
                                <span class="badge bg-success-subtle text-success rounded-pill px-2.5">Activa</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5">Inactiva</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            {{-- Template de Acciones Dropdown adaptado --}}
                            <div class="d-inline-flex">
                                <div class="dropdown">
                                    <a href="#" class="text-body text-decoration-none px-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i> {{-- O usa "bi-list" si prefieres el de hamburguesa --}}
                                    </a>
                                    
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-light">
                                        @can('zonas.editar')
                                            <a href="{{ route('zonas.edit', $z) }}" class="dropdown-item py-2">
                                                <i class="bi bi-pencil me-2 text-primary"></i>
                                                Editar zona
                                            </a>
                                        @endcan

                                        @can('zonas.eliminar')
                                            <div class="dropdown-divider my-1"></div>
                                            
                                            {{-- Nota: Para que el click del item dispare el formulario correctamente --}}
                                            <a href="#" class="dropdown-item py-2 text-danger" 
                                               onclick="event.preventDefault(); if(confirm('¿Estás seguro de que deseas eliminar la zona &quot;{{ $z->nombre }}&quot;?')) { document.getElementById('delete-form-{{ $z->id }}').submit(); }">
                                                <i class="bi bi-trash me-2"></i>
                                                Eliminar zona
                                            </a>

                                            <form id="delete-form-{{ $z->id }}" method="POST" action="{{ route('zonas.destroy', $z) }}" class="d-none">
                                                @csrf 
                                                @method('DELETE')
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-geo-alt d-block display-6 mb-2 text-black-50"></i>
                            No hay zonas registradas en el sistema.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scriptsHeader')
<link rel="stylesheet" href="{{ asset('assets/js/vendor/maps/leaflet/leaflet.css') }}"> {{-- Asegurar que los estilos estén presentes --}}
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
(function () {
    const zonas = @json($zonasMapa ?? []);

    if (!document.getElementById('mapa-zonas')) return;

    // Coordenadas por defecto si no hay datos
    const map = L.map('mapa-zonas').setView([-1.0458, -78.5916], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, 
        attribution: '© OpenStreetMap'
    }).addTo(map);

    const grupo = [];
    
    zonas.forEach(z => {
        if (z.poligono && z.poligono.length >= 3) {
            const poly = L.polygon(z.poligono, { 
                color: z.color || '#3b82f6', 
                fillColor: z.color || '#3b82f6',
                fillOpacity: 0.15,
                weight: 2
            }).addTo(map).bindPopup(`<strong>${z.nombre}</strong>`);
            grupo.push(poly);
        }
    });

    if (grupo.length > 0) {
        const fg = L.featureGroup(grupo);
        try { 
            map.fitBounds(fg.getBounds().pad(0.1)); 
        } catch (e) { 
            console.error("Error al ajustar bordes del mapa:", e); 
        }
    }

    // Invalidate size para prevenir renderizado parcial en componentes dinámicos
    setTimeout(() => map.invalidateSize(), 250);
})();
</script>
@endpush
@endsection