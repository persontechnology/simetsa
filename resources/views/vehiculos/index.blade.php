@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('vehiculos.index') }}
@endsection

@section('content')
{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">Placa</label>
                <input type="text" name="placa" value="{{ request('placa') }}"
                       class="form-control form-control-sm" placeholder="ABC-1234">
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Tipo de vehículo</label>
                <select name="tipo_vehiculo_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($tipos as $t)
                        <option value="{{ $t->id }}" @selected(request('tipo_vehiculo_id') == $t->id)>{{ $t->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="activo"   @selected(request('estado') === 'activo')>Activo</option>
                    <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
                </select>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-sm btn-simetsa">Filtrar</button>
                <a href="{{ route('vehiculos.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Placa</th>
                    <th>Tipo</th>
                    <th>Marca / Modelo</th>
                    <th>Año</th>
                    <th>Conductor</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehiculos as $v)
                    <tr>
                        <td><strong>{{ $v->placa }}</strong></td>
                        <td class="small text-muted">{{ $v->tipoVehiculo?->nombre ?? '—' }}</td>
                        <td>{{ $v->marca }} {{ $v->modelo }}</td>
                        <td>{{ $v->anio }}</td>
                        <td class="small">{{ $v->conductor?->user?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $v->estado_color }}">{{ $v->estado_label }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('vehiculos.show', $v) }}"
                               class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No hay vehículos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($vehiculos->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $vehiculos->links() }}</div>
    @endif
</div>
@endsection
