
@php $t = $tarifa ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="tipo_plaza_id" class="form-label">Tipo de plaza *</label>
        <select name="tipo_plaza_id" id="tipo_plaza_id"
                class="form-select @error('tipo_plaza_id') is-invalid @enderror" required>
            <option value="">— Seleccione —</option>
            @foreach($tiposPlaza as $tp)
                <option value="{{ $tp->id }}"
                    @selected(old('tipo_plaza_id', $t?->tipo_plaza_id) == $tp->id)>
                    {{ $tp->nombre }} ({{ $tp->codigo }})
                    @if(!$tp->es_pagado) — Exonerado @endif
                </option>
            @endforeach
        </select>
        @error('tipo_plaza_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre *</label>
        <input type="text" name="nombre" id="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $t?->nombre) }}" required
               placeholder="Ej: Tarifa estándar 2026">
        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="valor_hora" class="form-label">Valor por hora (USD) *</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="valor_hora" id="valor_hora" step="0.0001" min="0"
                   class="form-control @error('valor_hora') is-invalid @enderror"
                   value="{{ old('valor_hora', $t?->valor_hora) }}" required>
            @error('valor_hora')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <small class="form-text text-muted">$0.25 según Art. 22.</small>
    </div>

    <div class="col-md-3">
        <label for="vigente_desde" class="form-label">Vigente desde *</label>
        <input type="date" name="vigente_desde" id="vigente_desde"
               class="form-control @error('vigente_desde') is-invalid @enderror"
               value="{{ old('vigente_desde', $t?->vigente_desde?->format('Y-m-d')) }}" required>
        @error('vigente_desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="vigente_hasta" class="form-label">Vigente hasta</label>
        <input type="date" name="vigente_hasta" id="vigente_hasta"
               class="form-control @error('vigente_hasta') is-invalid @enderror"
               value="{{ old('vigente_hasta', $t?->vigente_hasta?->format('Y-m-d')) }}">
        <small class="form-text text-muted">Dejar en blanco si es "hasta nuevo aviso".</small>
        @error('vigente_hasta')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label d-block">Estado</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                   @checked(old('activo', $t?->activo ?? true))>
            <label for="activo" class="form-check-label small">Activa</label>
        </div>
    </div>

    <div class="col-12">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" id="descripcion" rows="2"
                  class="form-control @error('descripcion') is-invalid @enderror"
        >{{ old('descripcion', $t?->descripcion) }}</textarea>
        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('tarifas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-simetsa">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Crear tarifa' : 'Guardar cambios' }}
    </button>
</div>