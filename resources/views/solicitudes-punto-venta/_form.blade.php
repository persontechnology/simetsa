@php $s = $solicitud ?? null; @endphp

<div class="row g-3">
    <div class="col-12"><h2 class="h6 text-simetsa">Datos del solicitante</h2></div>
    <div class="col-md-3">
        <label class="form-label">Cédula</label>
        <input type="text" name="cedula" class="form-control @error('cedula') is-invalid @enderror" value="{{ old('cedula', $s?->cedula) }}" maxlength="10" required>
        @error('cedula') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Nombres</label>
        <input type="text" name="nombres" class="form-control" value="{{ old('nombres', $s?->nombres) }}" required>
    </div>
    <div class="col-md-5">
        <label class="form-label">Apellidos</label>
        <input type="text" name="apellidos" class="form-control" value="{{ old('apellidos', $s?->apellidos) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Correo</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $s?->email) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $s?->telefono) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Celular</label>
        <input type="text" name="telefono_celular" class="form-control" value="{{ old('telefono_celular', $s?->telefono_celular) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">Domicilio del solicitante</label>
        <input type="text" name="direccion" class="form-control" value="{{ old('direccion', $s?->direccion) }}">
    </div>

    <div class="col-12 mt-3"><h2 class="h6 text-simetsa">Datos del punto de venta</h2></div>
    <div class="col-md-6">
        <label class="form-label">Nombre comercial / local</label>
        <input type="text" name="nombre_comercial" class="form-control" value="{{ old('nombre_comercial', $s?->nombre_comercial) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">RUC (opcional)</label>
        <input type="text" name="ruc" class="form-control" value="{{ old('ruc', $s?->ruc) }}" maxlength="13">
    </div>
    <div class="col-md-8">
        <label class="form-label">Dirección del local</label>
        <input type="text" name="direccion_local" class="form-control" value="{{ old('direccion_local', $s?->direccion_local) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Referencia</label>
        <input type="text" name="referencia_ubicacion" class="form-control" value="{{ old('referencia_ubicacion', $s?->referencia_ubicacion) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Latitud (opcional)</label>
        <input type="number" step="any" name="latitud" class="form-control" value="{{ old('latitud', $s?->latitud) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Longitud (opcional)</label>
        <input type="number" step="any" name="longitud" class="form-control" value="{{ old('longitud', $s?->longitud) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Observaciones</label>
        <textarea name="observaciones" rows="2" class="form-control">{{ old('observaciones', $s?->observaciones) }}</textarea>
    </div>
</div>