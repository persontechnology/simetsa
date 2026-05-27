{{-- resources/views/plazas/_form.blade.php --}}
@php
    $p     = $plaza ?? null;
    $modo  = $modo ?? 'crear';
    $zonaP = $p?->zona ?? $zonas->first();
    $centroMapa = $p?->tieneUbicacion()
        ? [$p->latitud, $p->longitud]
        : ($zonaP ? [$zonaP->centro_lat, $zonaP->centro_lng] : [-1.0458, -78.5916]);
    $zoomMapa = $zonaP?->zoom ?? 16;
@endphp

<div class="row g-4">
    {{-- Datos de la plaza --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-info-circle me-1"></i> Datos de la plaza</h2>

                <div class="mb-3">
                    <label for="zona_id" class="form-label">Zona *</label>
                    <select name="zona_id" id="zona_id" class="form-select @error('zona_id') is-invalid @enderror" required>
                        <option value="">— Seleccione —</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z->id }}" @selected(old('zona_id', $p?->zona_id) == $z->id)>{{ $z->nombre }}</option>
                        @endforeach
                    </select>
                    @error('zona_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="tipo_plaza_id" class="form-label">Tipo de plaza *</label>
                    <select name="tipo_plaza_id" id="tipo_plaza_id" class="form-select @error('tipo_plaza_id') is-invalid @enderror" required>
                        <option value="">— Seleccione —</option>
                        @foreach($tiposPlaza as $t)
                            <option value="{{ $t->id }}" @selected(old('tipo_plaza_id', $p?->tipo_plaza_id) == $t->id)>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                    @error('tipo_plaza_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label for="calle_id" class="form-label">Calle</label>
                        <select name="calle_id" id="calle_id" class="form-select @error('calle_id') is-invalid @enderror">
                            <option value="">— Ninguna —</option>
                            @foreach($calles as $c)
                                <option value="{{ $c->id }}" @selected(old('calle_id', $p?->calle_id) == $c->id)>{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                        @error('calle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="manzana_id" class="form-label">Manzana</label>
                        <select name="manzana_id" id="manzana_id" class="form-select @error('manzana_id') is-invalid @enderror">
                            <option value="">— Ninguna —</option>
                            @foreach($manzanas as $mz)
                                <option value="{{ $mz->id }}" @selected(old('manzana_id', $p?->manzana_id) == $mz->id)>{{ $mz->codigo }}</option>
                            @endforeach
                        </select>
                        @error('manzana_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <label for="codigo" class="form-label">Código *</label>
                        <input type="text" name="codigo" id="codigo"
                               class="form-control @error('codigo') is-invalid @enderror"
                               value="{{ old('codigo', $p?->codigo) }}" required>
                        <small class="form-text text-muted">Ej: <code>VL-01</code></small>
                        @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="numero" class="form-label">N.º visible</label>
                        <input type="text" name="numero" id="numero"
                               class="form-control @error('numero') is-invalid @enderror"
                               value="{{ old('numero', $p?->numero) }}">
                        @error('numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Dimensiones: ancho (Art. 6) + largo (técnico) --}}
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <label for="ancho_metros" class="form-label">Ancho (m)</label>
                        <input type="number" step="0.01" min="2.20" max="2.50" name="ancho_metros" id="ancho_metros"
                               class="form-control @error('ancho_metros') is-invalid @enderror"
                               value="{{ old('ancho_metros', $p?->ancho_metros) }}">
                        <small class="form-text text-muted">Art. 6: 2.20–2.50</small>
                        @error('ancho_metros')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="largo_metros" class="form-label">Largo (m)</label>
                        <input type="number" step="0.01" min="3.00" max="15.00" name="largo_metros" id="largo_metros"
                               class="form-control @error('largo_metros') is-invalid @enderror"
                               value="{{ old('largo_metros', $p?->largo_metros) }}">
                        <small class="form-text text-muted">3.00–15.00 (auto a carga)</small>
                        @error('largo_metros')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-2">
                    <label for="orientacion" class="form-label">Orientación *</label>
                    <select name="orientacion" id="orientacion" class="form-select @error('orientacion') is-invalid @enderror" required>
                        @foreach($orientaciones as $v => $e)
                            <option value="{{ $v }}" @selected(old('orientacion', $p?->orientacion ?? 'paralelo') === $v)>{{ $e }}</option>
                        @endforeach
                    </select>
                    @error('orientacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Lat/Lng: editables a mano y sincronizadas con el mapa --}}
                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <label for="plaza_latitud" class="form-label">Latitud</label>
                        <input type="number" step="0.0000001" name="latitud" id="plaza_latitud"
                               class="form-control @error('latitud') is-invalid @enderror"
                               value="{{ old('latitud', $p?->latitud) }}">
                        @error('latitud')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="plaza_longitud" class="form-label">Longitud</label>
                        <input type="number" step="0.0000001" name="longitud" id="plaza_longitud"
                               class="form-control @error('longitud') is-invalid @enderror"
                               value="{{ old('longitud', $p?->longitud) }}">
                        @error('longitud')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-check form-switch mt-3">
                    <input type="hidden" name="activo" value="0">
                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                           @checked(old('activo', $p?->activo ?? true))>
                    <label for="activo" class="form-check-label small">Activa</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Selector de ubicación con referencias --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-pin-map me-1"></i> Ubicación de la plaza</h2>
                @include('partials.mapa-punto', [
                    'id'          => 'plaza',
                    'latInputId'  => 'plaza_latitud',
                    'lngInputId'  => 'plaza_longitud',
                    'latActual'   => old('latitud', $p?->latitud),
                    'lngActual'   => old('longitud', $p?->longitud),
                    'centro'      => $centroMapa,
                    'zoom'        => $zoomMapa,
                    'color'       => $p?->tipoPlaza?->color_mapa ?? '#0d4a8f',
                    'referencias' => $zonasReferencia,
                    'plazas'      => $plazasReferencia,
                    'calles'      => $callesReferencia,
                ])
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('plazas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-simetsa">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear plaza' : 'Guardar cambios' }}
    </button>
</div>

@push('scriptsFooter')
<script>
/*
 * (1) Sugerencia de código correlativo al elegir la calle.
 *     Si el código está vacío y la calle tiene plazas previas, propone el
 *     siguiente número con el mismo prefijo (ej: VL-06 -> VL-07).
 * (2) Autocompletado de dimensiones al elegir el tipo de plaza.
 *     Rellena ancho/largo solo si están vacíos (no pisa lo escrito).
 *
 * Se castea a (object) para garantizar que las claves numéricas (ids)
 * se serialicen como objeto JSON y no como arreglo.
 */
(function () {
    // --- (1) Sugerencia de código ---
    const codigosPorCalle = @json((object) ($codigosPorCalle ?? []));
    const selCalle  = document.getElementById('calle_id');
    const inpCodigo = document.getElementById('codigo');

    function sugerirCodigo() {
        if (!inpCodigo || inpCodigo.value.trim() !== '') return;
        const codes = codigosPorCalle[selCalle.value] || [];
        if (!codes.length) return;

        let prefijo = '', maxNum = -1;
        codes.forEach(c => {
            const m = String(c).match(/^(.*?)(\d+)$/);
            if (m) {
                const n = parseInt(m[2], 10);
                if (n > maxNum) { maxNum = n; prefijo = m[1]; }
            }
        });
        if (maxNum >= 0) {
            inpCodigo.value = prefijo + String(maxNum + 1).padStart(2, '0');
        }
    }
    selCalle?.addEventListener('change', sugerirCodigo);

    // --- (2) Autocompletado de dimensiones por tipo ---
    const dimsPorTipo = @json((object) ($dimensionesPorTipo ?? []));
    const selTipo  = document.getElementById('tipo_plaza_id');
    const inpAncho = document.getElementById('ancho_metros');
    const inpLargo = document.getElementById('largo_metros');

    function sugerirDimensiones() {
        const dims = dimsPorTipo[selTipo.value];
        if (!dims) return;
        if (inpAncho && inpAncho.value.trim() === '' && dims.ancho != null) inpAncho.value = dims.ancho;
        if (inpLargo && inpLargo.value.trim() === '' && dims.largo != null) inpLargo.value = dims.largo;
    }
    selTipo?.addEventListener('change', sugerirDimensiones);
})();
</script>
@endpush