{{-- resources/views/solicitudes-agente/_form.blade.php --}}
@php $s = $solicitud ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="cedula" class="form-label">Cédula *</label>
                <input type="text" name="cedula" id="cedula" maxlength="10"
                       class="form-control @error('cedula') is-invalid @enderror"
                       value="{{ old('cedula', $s?->cedula) }}" required>
                @error('cedula')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="nombres" class="form-label">Nombres *</label>
                <input type="text" name="nombres" id="nombres"
                       class="form-control @error('nombres') is-invalid @enderror"
                       value="{{ old('nombres', $s?->nombres) }}" required>
                @error('nombres')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="apellidos" class="form-label">Apellidos *</label>
                <input type="text" name="apellidos" id="apellidos"
                       class="form-control @error('apellidos') is-invalid @enderror"
                       value="{{ old('apellidos', $s?->apellidos) }}" required>
                @error('apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento *</label>
                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                       class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                       value="{{ old('fecha_nacimiento', $s?->fecha_nacimiento?->format('Y-m-d')) }}" required>
                <small class="form-text text-muted">Mínimo 18 años (Art. 33).</small>
                @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="nivel_educacion" class="form-label">Nivel de educación *</label>
                <select name="nivel_educacion" id="nivel_educacion" class="form-select @error('nivel_educacion') is-invalid @enderror" required>
                    <option value="">— Seleccione —</option>
                    @foreach($niveles as $v => $e)
                        <option value="{{ $v }}" @selected(old('nivel_educacion', $s?->nivel_educacion) === $v)>{{ $e }}</option>
                    @endforeach
                </select>
                @error('nivel_educacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="email" class="form-label">Correo</label>
                <input type="email" name="email" id="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $s?->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="form-control"
                       value="{{ old('telefono', $s?->telefono) }}">
            </div>
            <div class="col-md-4">
                <label for="telefono_celular" class="form-label">Celular</label>
                <input type="text" name="telefono_celular" id="telefono_celular" class="form-control"
                       value="{{ old('telefono_celular', $s?->telefono_celular) }}">
            </div>
            <div class="col-md-4">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" name="direccion" id="direccion" class="form-control"
                       value="{{ old('direccion', $s?->direccion) }}">
            </div>

            <div class="col-12">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="2" class="form-control">{{ old('observaciones', $s?->observaciones) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex justify-content-end gap-2">
    <a href="{{ route('solicitudes-agente.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-simetsa">
        <i class="bi bi-check-circle me-1"></i>
        {{ $modo === 'crear' ? 'Registrar solicitud' : 'Guardar cambios' }}
    </button>
</div>