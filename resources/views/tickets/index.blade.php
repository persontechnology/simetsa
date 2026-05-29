{{-- resources/views/tickets/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('tickets.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('tickets.index') }}">
    <div class="col-sm-6 col-md-2">
        <input type="text" name="placa" class="form-control form-control-sm"
               placeholder="Placa (ej: ABC-1234)"
               value="{{ request('placa') }}">
    </div>
    <div class="col-sm-6 col-md-3">
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
        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-ticket-perforated me-2"></i>Tickets digitales</span>
        <span class="text-muted small">{{ $tickets->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Placa</th>
                    <th>Zona</th>
                    <th>Horas</th>
                    <th class="text-end">Monto</th>
                    <th>Estado</th>
                    <th>Comprado en</th>
                    <th>Expira en</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                <tr>
                    <td><code>{{ $ticket->codigo }}</code></td>
                    <td>
                        <strong>{{ $ticket->vehiculo?->placa ?? '—' }}</strong>
                        @if($ticket->es_exonerado)
                            <span class="badge bg-info-subtle text-info ms-1" title="{{ $ticket->tipo_exoneracion }}">
                                exonerado
                            </span>
                        @endif
                    </td>
                    <td>{{ $ticket->zona?->nombre ?? '—' }}</td>
                    <td class="text-center">{{ $ticket->horas_compradas }}h</td>
                    <td class="text-end">${{ number_format($ticket->monto, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $ticket->estado->color() }}">
                            {{ $ticket->estado->etiqueta() }}
                        </span>
                    </td>
                    <td>{{ $ticket->comprado_en?->format('d/m/Y H:i') }}</td>
                    <td>{{ $ticket->expira_en?->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('tickets.show', $ticket) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No se encontraron tickets con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tickets->hasPages())
    <div class="card-footer bg-transparent">
        {{ $tickets->links() }}
    </div>
    @endif
</div>

@endsection
