@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('solicitudes-punto-venta.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-2 mb-lg-0">
        @can('puntos_venta.crear')
        <a href="{{ route('solicitudes-punto-venta.create') }}" class="d-flex align-items-center text-body py-2">
            <i class="bi bi-plus-lg"></i>
            Nueva solicitud
        </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach(['documentacion' => 'En documentación', 'contrato' => 'En contrato', 'activa' => 'Activa', 'rechazada' => 'Rechazada'] as $val => $lbl)
                    <option value="{{ $val }}" @selected($estadoFiltro === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>Código</th><th>Punto de venta</th><th>Solicitante</th><th>Estado</th><th>Fecha</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($solicitudes as $s)
                <tr>
                    <td><code>{{ $s->codigo }}</code></td>
                    <td>{{ $s->nombre_comercial }}</td>
                    <td class="small">{{ $s->nombre_completo }}</td>
                    <td><span class="badge bg-{{ $s->estado_color }}">{{ $s->estado_label }}</span></td>
                    <td class="small">{{ $s->fecha_solicitud?->format('d/m/Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('solicitudes-punto-venta.show', $s) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted small">No hay solicitudes.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $solicitudes->links() }}
</div></div>
@endsection