
@php $f = $feriado ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="row g-3">
    <div class="col-md-4">
        <label for="fecha" class="form-label">Fecha *</label>
        <input type="date" name="fecha" id="fecha"
               class="form-control @error('fecha') is-invalid @enderror"
               value="{{ old('fecha', $f?->fecha?->format('Y-m-d')) }}" required>
        @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label for="nombre" class="form-label">Nombre *</label>
        <input type="text" name="nombre" id="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $f?->nombre) }}" required>
        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="tipo" class="form-label">Tipo *</label>
        <select name="tipo" id="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
            @foreach($tipos as $v => $e)
                <option value="{{ $v }}" @selected(old('tipo', $f?->tipo) === $v)>{{ $e }}</option>
            @endforeach
        </select>
        @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label d-block">Recurrente</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="recurrente" value="0">
            <input class="form-check-input" type="checkbox" name="recurrente" id="recurrente" value="1"
                   @checked(old('recurrente', $f?->recurrente ?? true))>
            <label for="recurrente" class="form-check-label small">Se repite cada año</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label d-block">Estado</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                   @checked(old('activo', $f?->activo ?? true))>
            <label for="activo" class="form-check-label small">Activo</label>
        </div>
    </div>
    <div class="col-12">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" id="descripcion" rows="3"
                  class="form-control">{{ old('descripcion', $f?->descripcion) }}</textarea>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('dias-feriado.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear feriado' : 'Guardar cambios' }}
    </button>
</div>