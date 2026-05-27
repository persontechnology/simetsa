{{-- resources/views/layouts/partials/mapa-editor.blade.php --}}
{{--
    Editor de geometría reutilizable sobre Leaflet + OpenStreetMap.

    Dibuja polígonos (zonas, manzanas) o polilíneas (calles). Click = agrega
    vértice; vértices arrastrables; sincroniza un input oculto con el JSON.

    Parámetros del @include:
      $id          string   Identificador único del editor.
      $tipo        string   'poligono' | 'polilinea'.
      $inputName   string   Nombre del input oculto.
      $valorActual array|null  Coordenadas existentes [[lat,lng],...].
      $centro      array    [lat, lng] centro inicial.
      $zoom        int      Nivel de zoom inicial.
      $color       string   Color hex de la geometría.
      $referencias array|null  (OPCIONAL) Capas de fondo no editables:
                              [['poligono'=>[[lat,lng]...], 'color'=>'#xxx'], ...]
--}}


@php
    $config = [
        'idMapa'      => "mapa-{$id}",
        'inputId'     => "input-{$id}",
        'idDeshacer'  => "deshacer-{$id}",
        'idLimpiar'   => "limpiar-{$id}",
        'tipo'        => $tipo,
        'centro'      => $centro,
        'zoom'        => $zoom,
        'color'       => $color,
        'valorActual' => $valorActual ?: [],
        'referencias' => $referencias ?? [],
    ];
@endphp

<div class="mb-2 d-flex gap-2 align-items-center">
    <span class="small text-muted">
        <i class="bi bi-cursor"></i>
        Clic en el mapa para agregar un vértice. Arrastrá los puntos para ajustarlos.
    </span>
    <div class="ms-auto d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="deshacer-{{ $id }}">
            <i class="bi bi-arrow-counterclockwise"></i> Deshacer punto
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" id="limpiar-{{ $id }}">
            <i class="bi bi-eraser"></i> Limpiar
        </button>
    </div>
</div>

<div id="mapa-{{ $id }}" style="height: 420px; border-radius: .5rem;" class="border"></div>

<input type="hidden" name="{{ $inputName }}" id="input-{{ $id }}"
       value="{{ $valorActual ? json_encode($valorActual) : '' }}">

<script type="application/json" id="cfg-{{ $id }}">{!! json_encode($config) !!}</script>

@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
(function () {
    const cfg = JSON.parse(document.getElementById('cfg-{{ $id }}').textContent);

    const map = L.map(cfg.idMapa).setView(cfg.centro, cfg.zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Capas de referencia (no editables): polígonos de fondo en tono tenue
    (cfg.referencias || []).forEach(ref => {
        if (ref.poligono && ref.poligono.length >= 3) {
            L.polygon(ref.poligono, {
                color: ref.color || '#888',
                weight: 1,
                fillOpacity: 0.05,
                dashArray: '4 4',
                interactive: false
            }).addTo(map);
        }
    });

    const input   = document.getElementById(cfg.inputId);
    let puntos    = Array.isArray(cfg.valorActual) ? cfg.valorActual.slice() : [];
    let capa      = null;
    const markers = [];

    function sync() {
        input.value = puntos.length ? JSON.stringify(puntos) : '';
    }

    function redibujar() {
        if (capa) { map.removeLayer(capa); capa = null; }
        markers.forEach(m => map.removeLayer(m));
        markers.length = 0;

        puntos.forEach((p, i) => {
            const mk = L.marker(p, { draggable: true }).addTo(map);
            mk.on('dragend', e => {
                const ll = e.target.getLatLng();
                puntos[i] = [+ll.lat.toFixed(7), +ll.lng.toFixed(7)];
                redibujar();
            });
            markers.push(mk);
        });

        if (cfg.tipo === 'poligono' && puntos.length >= 3) {
            capa = L.polygon(puntos, { color: cfg.color, fillOpacity: 0.2 }).addTo(map);
        } else if (cfg.tipo === 'polilinea' && puntos.length >= 2) {
            capa = L.polyline(puntos, { color: cfg.color, weight: 4 }).addTo(map);
        }
        sync();
    }

    map.on('click', e => {
        puntos.push([+e.latlng.lat.toFixed(7), +e.latlng.lng.toFixed(7)]);
        redibujar();
    });

    document.getElementById(cfg.idDeshacer).addEventListener('click', () => {
        puntos.pop();
        redibujar();
    });

    document.getElementById(cfg.idLimpiar).addEventListener('click', () => {
        puntos = [];
        redibujar();
    });

    if (puntos.length) {
        redibujar();
        try { map.fitBounds(L.latLngBounds(puntos).pad(0.2)); } catch (e) { /* noop */ }
    }

    setTimeout(() => map.invalidateSize(), 200);
})();
</script>
@endpush