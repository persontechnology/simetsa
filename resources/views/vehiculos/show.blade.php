@extends('layouts.app')
@section('titulo', 'Vehículo ' . $vehiculo->placa)

@section('breadcrumb')
{{ Breadcrumbs::render('vehiculos.show', $vehiculo) }}
@endsection

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">
                <i class="bi bi-car-front text-simetsa me-1"></i>
                <strong>{{ $vehiculo->placa }}</strong>
                <span class="badge bg-{{ $vehiculo->estado_color }} ms-2">{{ $vehiculo->estado_label }}</span>
            </h1>
            <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    {{-- Datos del vehículo --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3">
                <i class="bi bi-car-front me-1"></i> Datos del vehículo (Art. 25)
            </h2>
            <dl class="row mb-0 small">
                <dt class="col-5">Placa</dt>
                <dd class="col-7"><strong>{{ $vehiculo->placa }}</strong></dd>

                <dt class="col-5">Tipo</dt>
                <dd class="col-7">{{ $vehiculo->tipoVehiculo?->nombre ?? '—' }}</dd>

                <dt class="col-5">Marca</dt>
                <dd class="col-7">{{ $vehiculo->marca }}</dd>

                <dt class="col-5">Modelo</dt>
                <dd class="col-7">{{ $vehiculo->modelo }}</dd>

                <dt class="col-5">Año</dt>
                <dd class="col-7">{{ $vehiculo->anio }}</dd>

                <dt class="col-5">Color</dt>
                <dd class="col-7">{{ $vehiculo->color }}</dd>

                <dt class="col-5">Fecha de registro</dt>
                <dd class="col-7">{{ $vehiculo->created_at?->format('d/m/Y') }}</dd>

                @if($vehiculo->observaciones)
                    <dt class="col-5">Observaciones</dt>
                    <dd class="col-7">{{ $vehiculo->observaciones }}</dd>
                @endif
            </dl>

            @can('vehiculos.editar')
            <hr>
            <form method="POST" action="{{ route('vehiculos.estado', $vehiculo) }}"
                  class="d-flex gap-2 align-items-end">
                @csrf @method('PATCH')
                <div class="flex-grow-1">
                    <label for="estado" class="form-label small mb-1">Estado</label>
                    <select name="estado" id="estado" class="form-select form-select-sm">
                        @foreach(\App\Models\Vehiculo::listadoEstados() as $val => $lbl)
                            <option value="{{ $val }}" @selected($vehiculo->estado === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-sm btn-simetsa">Actualizar</button>
            </form>
            @endcan
        </div></div>
    </div>

    {{-- Conductor propietario --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3">
                <i class="bi bi-person me-1"></i> Conductor propietario
            </h2>
            @if($vehiculo->conductor)
                <dl class="row mb-0 small">
                    <dt class="col-5">Código</dt>
                    <dd class="col-7"><code>{{ $vehiculo->conductor->codigo }}</code></dd>

                    <dt class="col-5">Nombre</dt>
                    <dd class="col-7">{{ $vehiculo->conductor->user?->name ?? '—' }}</dd>

                    <dt class="col-5">Cédula</dt>
                    <dd class="col-7">{{ $vehiculo->conductor->user?->perfil?->cedula ?? '—' }}</dd>

                    <dt class="col-5">Correo</dt>
                    <dd class="col-7">{{ $vehiculo->conductor->user?->email ?? '—' }}</dd>

                    <dt class="col-5">Teléfono</dt>
                    <dd class="col-7">{{ $vehiculo->conductor->user?->perfil?->telefono_celular ?? '—' }}</dd>

                    <dt class="col-5">Estado conductor</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $vehiculo->conductor->estado === 'activo' ? 'success' : 'secondary' }}">
                            {{ ucfirst($vehiculo->conductor->estado) }}
                        </span>
                    </dd>
                </dl>
            @else
                <p class="small text-muted mb-0">Conductor no encontrado.</p>
            @endif
        </div></div>
    </div>
</div>
@endsection
