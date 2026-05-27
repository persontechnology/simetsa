{{-- resources/views/zonas/_form.blade.php --}}
@php $z = $zona ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="row g-4">
    {{-- Datos de la zona --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-info-circle me-1"></i> Datos de la zona</h2>

                <div class="mb-3">
                    <label for="codigo" class="form-label">Código *</label>
                    <input type="text" name="codigo" id="codigo"
                           class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $z?->codigo) }}" required>
                    <small class="form-text text-muted">snake_case (ej: <code>centro</code>)</small>
                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $z?->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3"
                              class="form-control">{{ old('descripcion', $z?->descripcion) }}</textarea>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label for="centro_lat" class="form-label">Latitud centro *</label>
                        <input type="number" step="0.0000001" name="centro_lat" id="centro_lat"
                               class="form-control @error('centro_lat') is-invalid @enderror"
                               value="{{ old('centro_lat', $z?->centro_lat ?? -1.0458) }}" required>
                        @error('centro_lat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="centro_lng" class="form-label">Longitud centro *</label>
                        <input type="number" step="0.0000001" name="centro_lng" id="centro_lng"
                               class="form-control @error('centro_lng') is-invalid @enderror"
                               value="{{ old('centro_lng', $z?->centro_lng ?? -78.5916) }}" required>
                        @error('centro_lng')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-2 mt-1">
                    <div class="col-4">
                        <label for="zoom" class="form-label">Zoom *</label>
                        <input type="number" name="zoom" id="zoom" min="1" max="20"
                               class="form-control @error('zoom') is-invalid @enderror"
                               value="{{ old('zoom', $z?->zoom ?? 16) }}" required>
                        @error('zoom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-4">
                        <label for="color" class="form-label">Color *</label>
                        <input type="color" name="color" id="color"
                               class="form-control form-control-color @error('color') is-invalid @enderror"
                               value="{{ old('color', $z?->color ?? '#0d4a8f') }}" required>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-4">
                        <label class="form-label d-block">Estado</label>
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="activo" value="0">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                   @checked(old('activo', $z?->activo ?? true))>
                            <label for="activo" class="form-check-label small">Activa</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Editor de geometría --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-simetsa mb-3"><i class="bi bi-pin-map me-1"></i> Polígono de la zona</h2>
                @include('partials.mapa-editor', [
                    'id'          => 'zona',
                    'tipo'        => 'poligono',
                    'inputName'   => 'poligono',
                    'valorActual' => old('poligono') ? json_decode(old('poligono'), true) : ($z?->poligono ?? []),
                    'centro'      => [$z?->centro_lat ?? -1.0458, $z?->centro_lng ?? -78.5916],
                    'zoom'        => $z?->zoom ?? 16,
                    'color'       => $z?->color ?? '#0d4a8f',
                ])
                @error('poligono')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('zonas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-simetsa">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear zona' : 'Guardar cambios' }}
    </button>
</div>