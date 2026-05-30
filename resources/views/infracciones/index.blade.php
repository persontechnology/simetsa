{{-- resources/views/infracciones/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('infracciones.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('infracciones.index') }}">
    <div class="col-sm-6 col-md-2">
        <input type="text" name="placa" class="form-control form-control-sm"
               placeholder="Placa (ej: ABC1234)"
               value="{{ request('placa') }}">
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="zona_id" class="form-select form-select-sm">
            <option value="">Todas las zonas</option>
            @foreach($zonas as $z)
                <option value="{{ $z->id }}" {{ request('zona_id') == $z->id ? 'selected' : '' }}>
                    {{ $z->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="tipo_infraccion" class="form-select form-select-sm">
            <option value="">Todos los tipos</option>
            @foreach($tipos as $val => $etiqueta)
                <option value="{{ $val }}" {{ request('tipo_infraccion') === $val ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            @foreach($estados as $val => $etiqueta)
                <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <input type="date" name="fecha_desde" class="form-control form-control-sm"
               value="{{ request('fecha_desde') }}" title="Desde">
    </div>
    <div class="col-sm-6 col-md-2">
        <input type="date" name="fecha_hasta" class="form-control form-control-sm"
               value="{{ request('fecha_hasta') }}" title="Hasta">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        <a href="{{ route('infracciones.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-exclamation-triangle me-2"></i>Infracciones</span>
        <span class="text-muted small">{{ $infracciones->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Placa</th>
                    <th>Tipo</th>
                    <th>Zona</th>
                    <th>Agente</th>
                    <th class="text-end">Multa</th>
                    <th>Estado</th>
                    <th>Candado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($infracciones as $inf)
                <tr>
                    <td><code>{{ $inf->id }}</code></td>
                    <td><strong>{{ $inf->placa }}</strong></td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width:180px"
                              title="{{ $inf->tipo_infraccion->etiqueta() }}">
                            {{ $inf->tipo_infraccion->etiqueta() }}
                        </span>
                    </td>
                    <td>{{ $inf->zona?->nombre ?? '—' }}</td>
                    <td>{{ $inf->agente?->codigo ?? '—' }}</td>
                    <td class="text-end">${{ number_format($inf->monto_multa, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $inf->estado->color() }}">
                            {{ $inf->estado->etiqueta() }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($inf->inmovilizacion)
                            <span class="badge bg-{{ $inf->inmovilizacion->estado->color() }}">
                                {{ $inf->inmovilizacion->estado->etiqueta() }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $inf->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('infracciones.show', $inf) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        No se encontraron infracciones con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($infracciones->hasPages())
    <div class="card-footer bg-transparent">
        {{ $infracciones->links() }}
    </div>
    @endif
</div>

@endsection
