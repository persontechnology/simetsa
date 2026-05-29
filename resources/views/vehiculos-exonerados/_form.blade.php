{{-- Partial: formulario de vehículo exonerado (Art. 27 Ordenanza SIMETSA) --}}
<div class="row g-3">

    <div class="col-md-4">
        <label for="placa" class="form-label fw-semibold">Placa <span class="text-danger">*</span></label>
        <input type="text" id="placa" name="placa"
               class="form-control text-uppercase @error('placa') is-invalid @enderror"
               value="{{ old('placa', $vehiculo->placa ?? '') }}"
               maxlength="10" placeholder="GHI-0001"
               style="text-transform: uppercase">
        @error('placa')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="institucion" class="form-label fw-semibold">Institución <span class="text-danger">*</span></label>
        <input type="text" id="institucion" name="institucion"
               class="form-control @error('institucion') is-invalid @enderror"
               value="{{ old('institucion', $vehiculo->institucion ?? '') }}"
               maxlength="200">
        @error('institucion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-5">
        <label for="tipo_exoneracion" class="form-label fw-semibold">Tipo de exoneración <span class="text-danger">*</span></label>
        <select id="tipo_exoneracion" name="tipo_exoneracion"
                class="form-select @error('tipo_exoneracion') is-invalid @enderror">
            <option value="">— Seleccionar —</option>
            @foreach($tipos as $val => $etiqueta)
                <option value="{{ $val }}"
                    {{ old('tipo_exoneracion', $vehiculo->tipo_exoneracion ?? '') === $val ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        @error('tipo_exoneracion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="tiempo_maximo_horas" class="form-label fw-semibold">Tiempo máximo (horas)</label>
        <input type="number" id="tiempo_maximo_horas" name="tiempo_maximo_horas"
               class="form-control @error('tiempo_maximo_horas') is-invalid @enderror"
               value="{{ old('tiempo_maximo_horas', $vehiculo->tiempo_maximo_horas ?? 2) }}"
               min="1" max="2">
        <div class="form-text">Máximo 2 horas (Art. 27)</div>
        @error('tiempo_maximo_horas')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="fecha_registro" class="form-label fw-semibold">Fecha de registro <span class="text-danger">*</span></label>
        <input type="date" id="fecha_registro" name="fecha_registro"
               class="form-control @error('fecha_registro') is-invalid @enderror"
               value="{{ old('fecha_registro', isset($vehiculo) ? $vehiculo->fecha_registro?->format('Y-m-d') : now()->format('Y-m-d')) }}">
        @error('fecha_registro')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="nombre_funcionario" class="form-label">Nombre del funcionario</label>
        <input type="text" id="nombre_funcionario" name="nombre_funcionario"
               class="form-control @error('nombre_funcionario') is-invalid @enderror"
               value="{{ old('nombre_funcionario', $vehiculo->nombre_funcionario ?? '') }}"
               maxlength="200">
        @error('nombre_funcionario')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="numero_oficio" class="form-label">N.º de oficio</label>
        <input type="text" id="numero_oficio" name="numero_oficio"
               class="form-control @error('numero_oficio') is-invalid @enderror"
               value="{{ old('numero_oficio', $vehiculo->numero_oficio ?? '') }}"
               maxlength="100">
        @error('numero_oficio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="observaciones" class="form-label">Observaciones</label>
        <textarea id="observaciones" name="observaciones"
                  class="form-control @error('observaciones') is-invalid @enderror"
                  rows="2" maxlength="500">{{ old('observaciones', $vehiculo->observaciones ?? '') }}</textarea>
        @error('observaciones')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="activo" value="0">
            <input class="form-check-input @error('activo') is-invalid @enderror"
                   type="checkbox" id="activo" name="activo" value="1"
                   {{ old('activo', ($vehiculo->activo ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="activo">Exoneración activa</label>
        </div>
    </div>
</div>
