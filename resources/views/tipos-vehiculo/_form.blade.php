@php $t = $tipoVehiculo ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="codigo" class="form-label">Código *</label>
        <input type="text" name="codigo" id="codigo"
               class="form-control @error('codigo') is-invalid @enderror"
               value="{{ old('codigo', $t?->codigo) }}" required>
        <small class="form-text text-muted">snake_case (ej: <code>liviano_privado</code>)</small>
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
        <label class="form-label d-block">¿Aplica tarifa? <span class="text-muted small">(Art. 25)</span></label>
        <div class="form-check form-switch">
            <input type="hidden" name="aplica_tarifa" value="0">
            <input class="form-check-input" type="checkbox" name="aplica_tarifa" id="aplica_tarifa" value="1"
                   @checked(old('aplica_tarifa', $t?->aplica_tarifa ?? true))>
            <label for="aplica_tarifa" class="form-check-label small">Tarifado</label>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label d-block">Estado</label>
        <div class="form-check form-switch">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                   @checked(old('activo', $t?->activo ?? true))>
            <label for="activo" class="form-check-label small">Activo</label>
        </div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('tipos-vehiculo.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear tipo' : 'Guardar cambios' }}
    </button>
</div>
