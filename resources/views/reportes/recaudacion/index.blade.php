{{-- resources/views/reportes/recaudacion/index.blade.php --}}
{{-- Reporte de recaudación (Fase 8.B). Filtros + tabla paginada + exportación. --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('reportes.recaudacion') }}
@endsection

@section('breadcrumb_elements')
    @can('reportes.exportar')
        <div class="d-flex gap-2">
            <a href="{{ route('reportes.recaudacion.excel', request()->query()) }}"
               class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="{{ route('reportes.recaudacion.pdf', request()->query()) }}"
               target="_blank"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> PDF
            </a>
        </div>
    @endcan
@endsection

@section('content')

    {{-- ── Tarjetas de resumen ─────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-cash-stack',
                'titulo' => 'Total recaudado',
                'prefijo'=> '$ ',
                'valor'  => number_format($totales['total'], 2),
                'color'  => 'primary',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-ticket-perforated',
                'titulo' => 'Tickets',
                'prefijo'=> '$ ',
                'valor'  => number_format($totales['total_tickets'], 2),
                'color'  => 'success',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-exclamation-triangle',
                'titulo' => 'Multas',
                'prefijo'=> '$ ',
                'valor'  => number_format($totales['total_infracciones'], 2),
                'color'  => 'danger',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-receipt',
                'titulo' => 'Transacciones',
                'valor'  => number_format($totales['cantidad']),
                'color'  => 'info',
            ])
        </div>
    </div>

    {{-- ── Filtros ────────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.recaudacion.index') }}" id="formFiltros">
                <div class="row g-2">

                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label small">Desde</label>
                        <input type="text" name="fecha_desde" id="filtroDesde"
                               class="form-control form-control-sm"
                               value="{{ request('fecha_desde') }}"
                               placeholder="AAAA-MM-DD">
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label small">Hasta</label>
                        <input type="text" name="fecha_hasta" id="filtroHasta"
                               class="form-control form-control-sm"
                               value="{{ request('fecha_hasta') }}"
                               placeholder="AAAA-MM-DD">
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small">Tipo</label>
                        <select name="tipo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="ticket"     {{ request('tipo') === 'ticket'     ? 'selected' : '' }}>Ticket</option>
                            <option value="infraccion" {{ request('tipo') === 'infraccion' ? 'selected' : '' }}>Infracción</option>
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small">Proveedor</label>
                        <select name="proveedor" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->value }}"
                                    {{ request('proveedor') === $prov->value ? 'selected' : '' }}>
                                    {{ $prov->etiqueta() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small">Zona</label>
                        <select name="zona_id" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            @foreach($zonas as $z)
                                <option value="{{ $z->id }}" {{ request('zona_id') == $z->id ? 'selected' : '' }}>
                                    {{ $z->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('reportes.recaudacion.index') }}" class="btn btn-sm btn-outline-secondary">
                            Limpiar
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- ── Tabla de transacciones ──────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($transacciones->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No hay transacciones para los filtros seleccionados.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Referencia</th>
                                <th>Placa</th>
                                <th>Zona</th>
                                <th>Proveedor</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transacciones as $tx)
                                @php
                                    $concepto   = $tx->concepto;
                                    $esTicket   = $tx->concepto_type === \App\Models\Ticket::class;
                                    $referencia = $esTicket
                                        ? ($concepto?->codigo ?? '—')
                                        : 'INF-' . str_pad($concepto?->id ?? 0, 6, '0', STR_PAD_LEFT);
                                    $placa      = $esTicket
                                        ? ($concepto?->vehiculo?->placa ?? '—')
                                        : ($concepto?->placa ?? '—');
                                    $zona       = $concepto?->zona?->nombre ?? '—';
                                @endphp
                                <tr>
                                    <td class="text-nowrap small">
                                        {{ $tx->created_at->isoFormat('D MMM YYYY, HH:mm') }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $esTicket ? 'primary' : 'danger' }} bg-opacity-10 text-{{ $esTicket ? 'primary' : 'danger' }}">
                                            {{ $esTicket ? 'Ticket' : 'Infracción' }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $referencia }}</td>
                                    <td class="small fw-semibold">{{ $placa }}</td>
                                    <td class="small">{{ $zona }}</td>
                                    <td class="small">{{ $tx->proveedor->etiqueta() }}</td>
                                    <td class="text-end fw-semibold">$ {{ number_format((float) $tx->monto, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end small">
                                    Total (página {{ $transacciones->currentPage() }})
                                </th>
                                <th class="text-end">
                                    $ {{ number_format($transacciones->sum('monto'), 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <small class="text-muted">
                        {{ $transacciones->total() }} transacciones · Página {{ $transacciones->currentPage() }} de {{ $transacciones->lastPage() }}
                    </small>
                    {{ $transacciones->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scriptsHeader')
<link rel="stylesheet" href="{{ asset('assets/js/vendor/pickers/daterangepicker/daterangepicker.css') }}">
<script src="{{ asset('assets/js/vendor/pickers/daterangepicker/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/pickers/daterangepicker/daterangepicker.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Flatpickr para los campos de fecha
    const opcionesFecha = {
        dateFormat: 'Y-m-d',
        allowInput: true,
        locale: { firstDayOfWeek: 1 },
    };
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#filtroDesde', opcionesFecha);
        flatpickr('#filtroHasta', opcionesFecha);
    }
});
</script>
@endpush
