{{-- resources/views/cursos-capacitacion/_form.blade.php --}}
@php $c = $curso ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="card border-0 shadow-sm"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-8">
            <label for="nombre" class="form-label">Nombre *</label>
            <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror"
                   value="{{ old('nombre', $c?->nombre) }}" required>
            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="estado" class="form-label">Estado *</label>
            <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
                @foreach($estados as $v => $e)
                    <option value="{{ $v }}" @selected(old('estado', $c?->estado ?? 'planificado') === $v)>{{ $e }}</option>
                @endforeach
            </select>
            @error('estado')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="fecha_inicio" class="form-label">Fecha de inicio *</label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control @error('fecha_inicio') is-invalid @enderror"
                   value="{{ old('fecha_inicio', $c?->fecha_inicio?->format('Y-m-d')) }}" required>
            @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="fecha_fin" class="form-label">Fecha de fin</label>
            <input type="date" name="fecha_fin" id="fecha_fin" class="form-control @error('fecha_fin') is-invalid @enderror"
                   value="{{ old('fecha_fin', $c?->fecha_fin?->format('Y-m-d')) }}">
            @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="cupo" class="form-label">Cupo</label>
            <input type="number" min="1" name="cupo" id="cupo" class="form-control @error('cupo') is-invalid @enderror"
                   value="{{ old('cupo', $c?->cupo) }}">
            @error('cupo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="2" class="form-control">{{ old('descripcion', $c?->descripcion) }}</textarea>
            <small class="form-text text-muted">Temáticas: Atención al Cliente, Primeros Auxilios y Educación Vial (Art. 33).</small>
        </div>
    </div>
</div></div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('cursos-capacitacion.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-simetsa">
        <i class="bi bi-check-circle me-1"></i>{{ $modo === 'crear' ? 'Crear curso' : 'Guardar cambios' }}
    </button>
</div>