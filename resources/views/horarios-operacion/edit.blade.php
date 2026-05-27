{{-- resources/views/horarios-operacion/edit.blade.php --}}
@extends('layouts.app')
@section('breadcrumb')
{{ Breadcrumbs::render('horarios-operacion.edit', $horario) }}
@endsection

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center gap-2">
                <i class="bi bi-clock text-simetsa fs-4"></i>
                <h2 class="h5 mb-0">Editar horario de operación del dia {{ $horario->nombre_dia }}</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('horarios-operacion.update', $horario) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="hora_inicio" class="form-label">Hora de inicio *</label>
                            <input type="time" name="hora_inicio" id="hora_inicio"
                                   class="form-control @error('hora_inicio') is-invalid @enderror"
                                   value="{{ old('hora_inicio', \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i')) }}" required>
                            @error('hora_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="hora_fin" class="form-label">Hora de cierre *</label>
                            <input type="time" name="hora_fin" id="hora_fin"
                                   class="form-control @error('hora_fin') is-invalid @enderror"
                                   value="{{ old('hora_fin', \Carbon\Carbon::parse($horario->hora_fin)->format('H:i')) }}" required>
                            @error('hora_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="activo" value="0">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                       @checked(old('activo', $horario->activo))>
                                <label for="activo" class="form-check-label">
                                    Este día <strong>opera</strong> el SIMETSA
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('horarios-operacion.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection