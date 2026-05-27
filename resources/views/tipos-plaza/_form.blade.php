
@php $t = $tipoPlaza ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="codigo" class="form-label">Código *</label>
        <input type="text" name="codigo" id="codigo"
               class="form-control @error('codigo') is-invalid @enderror"
               value="{{ old('codigo', $t?->codigo) }}" required>
        <small class="form-text text-muted">snake_case (ej: <code>plaza_motos</code>)</small>
        @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label for="nombre" class="form-label">Nombre *</label>
        <input type="text" name="nombre" id="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $t?->nombre) }}" required>
        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" id="descripcion" rows="3"
                  class="form-control @error('descripcion') is-invalid @enderror"
        >{{ old('descripcion', $t?->descripcion) }}</textarea>
        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="color_mapa" class="form-label">Color en mapa *</label>
        <input type="color" name="color_mapa" id="color_mapa"
               class="form-control form-control-color @error('color_mapa') is-invalid @enderror"
               value="{{ old('color_mapa', $t?->color_mapa ?? '#1d6fb8') }}" required>
        @error('color_mapa')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="icono" class="form-label">Ícono Bootstrap</label>
        <input type="text" name="icono" id="icono"
               class="form-control @error('icono') is-invalid @enderror"
               value="{{ old('icono', $t?->icono) }}" placeholder="bi-car-front">
        <small class="form-text text-muted">
            Ver lista en <a href="https://icons.getbootstrap.com" target="_blank">icons.getbootstrap.com</a>
        </small>
        @error('icono')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label d-block">¿Pagado?</label>
        <div class="form-check form-switch">
            <input type="hidden" name="es_pagado" value="0">
            <input class="form-check-input" type="checkbox" name="es_pagado" id="es_pagado" value="1"
                   @checked(old('es_pagado', $t?->es_pagado ?? true))>
            <label for="es_pagado" class="form-check-label small">Tarifado</label>
        </div>
    </div>
    <div class="col-md-2">
        <label class="form-label d-block">¿Credencial?</label>
        <div class="form-check form-switch">
            <input type="hidden" name="requiere_credencial" value="0">
            <input class="form-check-input" type="checkbox" name="requiere_credencial" id="requiere_credencial" value="1"
                   @checked(old('requiere_credencial', $t?->requiere_credencial ?? false))>
            <label for="requiere_credencial" class="form-check-label small">Requerida</label>
        </div>
    </div>
    <div class="col-md-2">
        <label class="form-label d-block">Estado</label>
        <div class="form-check form-switch">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                   @checked(old('activo', $t?->activo ?? true))>
            <label for="activo" class="form-check-label small">Activo</label>
        </div>
    </div>
 </div>

 {{-- Dimensiones sugeridas: el formulario de Plaza las autocompleta al elegir este tipo --}}
<div class="row g-2 mb-3">
    <div class="col-6">
        <label for="ancho_sugerido" class="form-label">Ancho sugerido (m)</label>
        <input type="number" step="0.01" min="2.20" max="2.50" name="ancho_sugerido" id="ancho_sugerido"
               class="form-control @error('ancho_sugerido') is-invalid @enderror"
               value="{{ old('ancho_sugerido', $tipoPlaza?->ancho_sugerido) }}">
        <small class="form-text text-muted">Art. 6: 2.20–2.50</small>
        @error('ancho_sugerido')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-6">
        <label for="largo_sugerido" class="form-label">Largo sugerido (m)</label>
        <input type="number" step="0.01" min="3.00" max="15.00" name="largo_sugerido" id="largo_sugerido"
               class="form-control @error('largo_sugerido') is-invalid @enderror"
               value="{{ old('largo_sugerido', $tipoPlaza?->largo_sugerido) }}">
        <small class="form-text text-muted">3.00–15.00 (auto a carga)</small>
        @error('largo_sugerido')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('tipos-plaza.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear tipo' : 'Guardar cambios' }}
    </button>
</div>