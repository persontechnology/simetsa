@extends('layouts.app')
@section('titulo', 'Punto de venta ' . $punto->codigo)


@section('content')
@if(session('password_temporal'))
    <div class="alert alert-warning">
        <i class="bi bi-key me-1"></i>
        <strong>Contraseña temporal:</strong> <code>{{ session('password_temporal') }}</code>.
        Comunicásela al responsable del punto de venta. (Se muestra una sola vez.)
    </div>
@endif

<div class="row g-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">
                <i class="bi bi-shop text-simetsa me-1"></i> <code>{{ $punto->codigo }}</code>
                <span class="badge bg-{{ $punto->estado_color }} ms-2">{{ $punto->estado_label }}</span>
            </h1>
            <a href="{{ route('puntos-venta.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3">Datos del punto de venta</h2>
            <dl class="row mb-0 small">
                <dt class="col-5">Local</dt><dd class="col-7">{{ $punto->nombre_comercial }}</dd>
                <dt class="col-5">Dirección</dt><dd class="col-7">{{ $punto->direccion_local }}</dd>
                <dt class="col-5">Referencia</dt><dd class="col-7">{{ $punto->referencia_ubicacion ?? '—' }}</dd>
                <dt class="col-5">Responsable</dt><dd class="col-7">{{ $punto->user?->name ?? '—' }}</dd>
                <dt class="col-5">Correo</dt><dd class="col-7">{{ $punto->user?->email ?? '—' }}</dd>
                @if($punto->solicitud)
                    <dt class="col-5">Solicitud</dt>
                    <dd class="col-7"><a href="{{ route('solicitudes-punto-venta.show', $punto->solicitud) }}">{{ $punto->solicitud->codigo }}</a></dd>
                @endif
            </dl>

            @can('puntos_venta.editar')
            <hr>
            <form method="POST" action="{{ route('puntos-venta.estado', $punto) }}" class="d-flex gap-2 align-items-end">
                @csrf @method('PATCH')
                <div class="flex-grow-1">
                    <label for="estado" class="form-label small mb-1">Estado</label>
                    <select name="estado" id="estado" class="form-select form-select-sm">
                        @foreach(\App\Models\PuntoVenta::listadoEstados() as $val => $lbl)
                            <option value="{{ $val }}" @selected($punto->estado === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-sm btn-simetsa">Actualizar</button>
            </form>
            @endcan
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3"><i class="bi bi-file-earmark-text me-1"></i> Contrato (Art. 31)</h2>
            @if($punto->contrato)
                <dl class="row mb-0 small">
                    <dt class="col-5">N.º contrato</dt><dd class="col-7">{{ $punto->contrato->numero_contrato }}</dd>
                    <dt class="col-5">Elaborado por</dt><dd class="col-7">{{ $punto->contrato->elaborado_por ?? '—' }}</dd>
                    <dt class="col-5">Firma</dt><dd class="col-7">{{ $punto->contrato->fecha_firma?->format('d/m/Y') }}</dd>
                    <dt class="col-5">Vigencia</dt><dd class="col-7">{{ $punto->contrato->fecha_inicio?->format('d/m/Y') }} → {{ $punto->contrato->fecha_fin?->format('d/m/Y') ?? 'Indefinida' }}</dd>
                    <dt class="col-5">Descuento</dt><dd class="col-7">{{ rtrim(rtrim(number_format($punto->contrato->porcentaje_descuento, 2), '0'), '.') }}% (Art. 21 / 31)</dd>
                </dl>
            @else
                <p class="small text-muted mb-0">Sin contrato registrado.</p>
            @endif
        </div></div>
    </div>
</div>

@if($punto->tieneUbicacion())
<div class="card border-0 shadow-sm mt-4"><div class="card-body">
    <h2 class="h6 text-simetsa mb-3"><i class="bi bi-geo-alt me-1"></i> Ubicación</h2>
    <div id="mapaPunto" style="height: 320px;" class="rounded border"></div>
</div></div>
@endif
@endsection

@if($punto->tieneUbicacion())


@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = {{ $punto->latitud }};
    const lng = {{ $punto->longitud }};
    const mapa = L.map('mapaPunto').setView([lat, lng], 17);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 19,
    }).addTo(mapa);
    L.marker([lat, lng]).addTo(mapa).bindPopup(@json($punto->nombre_comercial));
});
</script>
@endpush

@endif