{{-- resources/views/calles/_form.blade.php --}}
@php
    $c     = $calle ?? null;
    $modo  = $modo ?? 'crear';
    $zonaC = $c?->zona ?? $zonas->first();
    $centroMapa = $zonaC ? [$zonaC->centro_lat, $zonaC->centro_lng] : [-1.0458, -78.5916];
    $zoomMapa   = $zonaC?->zoom ?? 16;
@endphp

<div class="row g-4">
    {{-- Datos de la calle --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-info-circle me-1"></i> Datos de la calle</h2>

                <div class="mb-3">
                    <label for="zona_id" class="form-label">Zona *</label>
                    <select name="zona_id" id="zona_id" class="form-select @error('zona_id') is-invalid @enderror" required>
                        <option value="">— Seleccione —</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z->id }}" @selected(old('zona_id', $c?->zona_id) == $z->id)>{{ $z->nombre }}</option>
                        @endforeach
                    </select>
                    @error('zona_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="codigo" class="form-label">Código *</label>
                    <input type="text" name="codigo" id="codigo"
                           class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $c?->codigo) }}" required>
                    <small class="form-text text-muted">snake_case (ej: <code>vicente_leon</code>)</small>
                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $c?->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label for="desde" class="form-label">Desde</label>
                        <input type="text" name="desde" id="desde"
                               class="form-control" value="{{ old('desde', $c?->desde) }}">
                    </div>
                    <div class="col-6">
                        <label for="hasta" class="form-label">Hasta</label>
                        <input type="text" name="hasta" id="hasta"
                               class="form-control" value="{{ old('hasta', $c?->hasta) }}">
                    </div>
                </div>
                <small class="form-text text-muted">Tramo según el Art. 16.</small>

                <div class="mb-3 mt-2">
                    <label for="sentido" class="form-label">Sentido *</label>
                    <select name="sentido" id="sentido" class="form-select @error('sentido') is-invalid @enderror" required>
                        @foreach($sentidos as $v => $e)
                            <option value="{{ $v }}" @selected(old('sentido', $c?->sentido ?? 'doble') === $v)>{{ $e }}</option>
                        @endforeach
                    </select>
                    @error('sentido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="lado_estacionamiento" class="form-label">Costado de estacionamiento *</label>
                    <select name="lado_estacionamiento" id="lado_estacionamiento"
                            class="form-select @error('lado_estacionamiento') is-invalid @enderror" required>
                        @foreach($lados as $v => $e)
                            <option value="{{ $v }}" @selected(old('lado_estacionamiento', $c?->lado_estacionamiento ?? 'derecho') === $v)>{{ $e }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Art. 5: por defecto costado derecho.</small>
                    @error('lado_estacionamiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-check form-switch">
                    <input type="hidden" name="activo" value="0">
                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                           @checked(old('activo', $c?->activo ?? true))>
                    <label for="activo" class="form-check-label small">Activa</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Editor de polilínea --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-pin-map me-1"></i> Trazado de la calle</h2>
                <p class="small text-muted">El área tarifada de cada zona aparece sombreada como referencia.</p>
                @include('partials.mapa-editor', [
                    'id'          => 'calle',
                    'tipo'        => 'polilinea',
                    'inputName'   => 'polilinea',
                    'valorActual' => old('polilinea') ? json_decode(old('polilinea'), true) : ($c?->polilinea ?? []),
                    'centro'      => $centroMapa,
                    'zoom'        => $zoomMapa,
                    'color'       => '#dc3545',
                    'referencias' => $zonasReferencia,
                ])
                @error('polilinea')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('calles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-simetsa">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear calle' : 'Guardar cambios' }}
    </button>
</div>