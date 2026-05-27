@extends('layouts.app')
@section('breadcrumb')
{{ Breadcrumbs::render('dias-feriado.index') }}
@endsection

@section('content')
{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-4 rounded-4">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ route('dias-feriado.index') }}">
            <div class="row g-3 align-items-end">
                
                <div class="col-12 col-md-4">
                    <label for="ano" class="form-label fw-semibold small text-muted mb-1">
                        Año
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-calendar3"></i>
                        </span>
                        <input 
                            type="number" 
                            name="ano" 
                            id="ano" 
                            class="form-control" 
                            min="2020" 
                            max="2050"
                            value="{{ $filtros['ano'] }}"
                            placeholder="Ej. 2026"
                        >
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label for="tipo" class="form-label fw-semibold small text-muted mb-1">
                        Tipo de feriado
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-tags"></i>
                        </span>
                        <select name="tipo" id="tipo" class="form-select">
                            <option value="">Todos los tipos</option>
                            @foreach($tipos as $v => $e)
                                <option value="{{ $v }}" @selected(($filtros['tipo'] ?? '') === $v)>
                                    {{ $e }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="d-grid d-md-flex gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-search me-1"></i>
                            Filtrar
                        </button>

                        <a 
                            href="{{ route('dias-feriado.index') }}" 
                            class="btn btn-outline-secondary px-4"
                            title="Limpiar filtros"
                        >
                            <i class="bi bi-x-lg me-1"></i>
                            Limpiar
                        </a>
                        @can('feriados.crear')
                            <a href="{{ route('dias-feriado.create') }}" class="btn btn-simetsa">
                                <i class="bi bi-plus-circle me-1"></i> Nuevo feriado
                            </a>
                        @endcan
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Día</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Recurrente</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feriados as $f)
                    <tr>
                        <td><strong>{{ $f->fecha->format('d/m/Y') }}</strong></td>
                        <td class="text-muted small">{{ ucfirst($f->fecha->locale('es')->dayName) }}</td>
                        <td>
                            {{ $f->nombre }}
                            @if($f->descripcion)<div class="small text-muted">{{ Str::limit($f->descripcion, 80) }}</div>@endif
                        </td>
                        <td><span class="badge bg-{{ $f->color_badge }}">{{ $f->tipo_etiqueta }}</span></td>
                        <td>
                            @if($f->recurrente)
                                <span class="badge bg-light text-dark border"><i class="bi bi-arrow-clockwise"></i> Anual</span>
                            @else
                                <span class="text-muted small">Solo este año</span>
                            @endif
                        </td>
                        <td>
                            @if($f->activo)<span class="badge bg-success">Activo</span>
                            @else<span class="badge bg-secondary">Inactivo</span>@endif
                        </td>
                        <td class="text-end">
                            @can('feriados.editar')
                                <a href="{{ route('dias-feriado.edit', $f) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('feriados.eliminar')
                                <form method="POST" action="{{ route('dias-feriado.destroy', $f) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Eliminar"
                                            data-confirm
                                            data-action="eliminar"
                                            data-msg="¿Eliminar el feriado {{ $f->nombre }}?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay feriados en este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($feriados->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $feriados->links() }}</div>
    @endif
</div>
@endsection