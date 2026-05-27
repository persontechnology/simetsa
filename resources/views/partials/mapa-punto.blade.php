
{{--
    Selector de un punto único sobre Leaflet + OpenStreetMap.

    Coloca un marcador arrastrable y sincroniza dos inputs (lat/lng). Además
    dibuja capas de referencia NO interactivas para dar contexto:
      - polígonos de zonas
      - polilíneas de calles
      - plazas ya existentes (círculos), para no superponerlas

    Parámetros del @include:
      $id          string   Identificador único.
      $latInputId  string   ID del input de latitud.
      $lngInputId  string   ID del input de longitud.
      $latActual   float|null
      $lngActual   float|null
      $centro      array    [lat, lng].
      $zoom        int
      $color       string   Color del marcador activo.
      $referencias array|null  Polígonos de zonas.
      $plazas      array|null  Plazas existentes [{codigo, lat, lng, color}].
      $calles      array|null  Polilíneas de calles [{polilinea}].
--}}


@php
    $config = [
        'idMapa'      => "mapa-{$id}",
        'idLimpiar'   => "limpiar-{$id}",
        'latInputId'  => $latInputId,
        'lngInputId'  => $lngInputId,
        'latActual'   => $latActual,
        'lngActual'   => $lngActual,
        'centro'      => $centro,
        'zoom'        => $zoom,
        'color'       => $color,
        'referencias' => $referencias ?? [],
        'plazas'      => $plazas ?? [],
        'calles'      => $calles ?? [],
    ];
@endphp

<div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
    <span class="small text-muted">
        <i class="bi bi-geo-alt"></i>
        Clic en el mapa para ubicar la plaza. Los círculos son plazas ya creadas; no las pises.
    </span>
    <button type="button" class="btn btn-sm btn-outline-danger ms-auto" id="limpiar-{{ $id }}">
        <i class="bi bi-eraser"></i> Quitar ubicación
    </button>
</div>

<div id="mapa-{{ $id }}" style="height: 440px; border-radius:.5rem;" class="border"></div>

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
        maxZoom: 19, attribution: '© OpenStreetMap'
    }).addTo(map);

    // --- Capas de referencia (no interactivas) ---

    // Zonas (polígono tenue)
    (cfg.referencias || []).forEach(ref => {
        if (ref.poligono && ref.poligono.length >= 3) {
            L.polygon(ref.poligono, {
                color: ref.color || '#888', weight: 1, fillOpacity: 0.04,
                dashArray: '4 4', interactive: false
            }).addTo(map);
        }
    });

    // Calles (línea guía gris)
    (cfg.calles || []).forEach(c => {
        if (c.polilinea && c.polilinea.length >= 2) {
            L.polyline(c.polilinea, {
                color: '#6c757d', weight: 2, opacity: 0.5, dashArray: '6 4', interactive: false
            }).addTo(map);
        }
    });

    // Plazas existentes (círculos coloreados por tipo, con código)
    (cfg.plazas || []).forEach(p => {
        L.circleMarker([p.lat, p.lng], {
            radius: 6, color: '#fff', weight: 1, fillColor: p.color, fillOpacity: 0.65
        }).addTo(map).bindTooltip(p.codigo, { direction: 'top' });
    });

    // --- Marcador activo (la plaza que se está ubicando) ---
    const inputLat = document.getElementById(cfg.latInputId);
    const inputLng = document.getElementById(cfg.lngInputId);
    let marker = null;

    function ubicar(lat, lng) {
        const latR = +(+lat).toFixed(7);
        const lngR = +(+lng).toFixed(7);
        if (!marker) {
            marker = L.marker([latR, lngR], { draggable: true }).addTo(map);
            marker.on('dragend', e => {
                const ll = e.target.getLatLng();
                inputLat.value = ll.lat.toFixed(7);
                inputLng.value = ll.lng.toFixed(7);
            });
        } else {
            marker.setLatLng([latR, lngR]);
        }
        inputLat.value = latR;
        inputLng.value = lngR;
    }

    map.on('click', e => ubicar(e.latlng.lat, e.latlng.lng));

    document.getElementById(cfg.idLimpiar).addEventListener('click', () => {
        if (marker) { map.removeLayer(marker); marker = null; }
        inputLat.value = '';
        inputLng.value = '';
    });

    if (cfg.latActual !== null && cfg.lngActual !== null && cfg.latActual !== '' && cfg.lngActual !== '') {
        ubicar(cfg.latActual, cfg.lngActual);
        map.setView([cfg.latActual, cfg.lngActual], Math.max(cfg.zoom, 17));
    }

    setTimeout(() => map.invalidateSize(), 200);
})();
</script>
@endpush