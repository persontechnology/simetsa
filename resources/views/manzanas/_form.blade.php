{{-- resources/views/manzanas/_form.blade.php --}}
@php
    $m     = $manzana ?? null;
    $modo  = $modo ?? 'crear';
    $zonaM = $m?->zona ?? $zonas->first();
    $centroMapa = $zonaM ? [$zonaM->centro_lat, $zonaM->centro_lng] : [-1.0458, -78.5916];
    $zoomMapa   = $zonaM?->zoom ?? 16;
@endphp

<div class="row g-4">
    {{-- Datos de la manzana --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-info-circle me-1"></i> Datos de la manzana</h2>

                <div class="mb-3">
                    <label for="zona_id" class="form-label">Zona *</label>
                    <select name="zona_id" id="zona_id" class="form-select @error('zona_id') is-invalid @enderror" required>
                        <option value="">— Seleccione —</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z->id }}" @selected(old('zona_id', $m?->zona_id) == $z->id)>{{ $z->nombre }}</option>
                        @endforeach
                    </select>
                    @error('zona_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="codigo" class="form-label">Código *</label>
                    <input type="text" name="codigo" id="codigo"
                           class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $m?->codigo) }}" required>
                    <small class="form-text text-muted">Codificación urbana (ej: <code>M01</code>, <code>MZ-03</code>).</small>
                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $m?->nombre) }}">
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3"
                              class="form-control">{{ old('descripcion', $m?->descripcion) }}</textarea>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label for="color" class="form-label">Color *</label>
                        <input type="color" name="color" id="color"
                               class="form-control form-control-color @error('color') is-invalid @enderror"
                               value="{{ old('color', $m?->color ?? '#6c757d') }}" required>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label d-block">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="activo" value="0">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                   @checked(old('activo', $m?->activo ?? true))>
                            <label for="activo" class="form-check-label small">Activa</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Editor de polígono --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-pin-map me-1"></i> Polígono de la manzana</h2>
                <p class="small text-muted">El área tarifada de la zona aparece sombreada como referencia.</p>
                @include('partials.mapa-editor', [
                    'id'          => 'manzana',
                    'tipo'        => 'poligono',
                    'inputName'   => 'poligono',
                    'valorActual' => old('poligono') ? json_decode(old('poligono'), true) : ($m?->poligono ?? []),
                    'centro'      => $centroMapa,
                    'zoom'        => $zoomMapa,
                    'color'       => $m?->color ?? '#6c757d',
                    'referencias' => $zonasReferencia,
                ])
                @error('poligono')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('manzanas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-simetsa">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear manzana' : 'Guardar cambios' }}
    </button>
</div>