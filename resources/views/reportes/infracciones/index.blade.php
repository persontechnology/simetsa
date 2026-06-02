{{-- resources/views/reportes/infracciones/index.blade.php --}}
{{-- Reporte de infracciones (Fase 8.C). Arts. 15, 17, 18, 28-30 Ordenanza SIMETSA. --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('reportes.infracciones') }}
@endsection

@section('breadcrumb_elements')
    @can('reportes.exportar')
        <div class="d-flex gap-2">
            <a href="{{ route('reportes.infracciones.excel', request()->query()) }}"
               class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="{{ route('reportes.infracciones.pdf', request()->query()) }}"
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
        <div class="col-sm-6 col-xl-2">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-list-check',
                'titulo' => 'Infracciones',
                'valor'  => number_format($totales['cantidad']),
                'color'  => 'secondary',
            ])
        </div>
        <div class="col-sm-6 col-xl-2">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-cash-stack',
                'titulo' => 'Total multas',
                'prefijo'=> '$ ',
                'valor'  => number_format($totales['total_multas'], 2),
                'color'  => 'primary',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-check-circle',
                'titulo' => 'Cobrado',
                'prefijo'=> '$ ',
                'valor'  => number_format($totales['total_cobrado'], 2),
                'color'  => 'success',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-hourglass-split',
                'titulo' => 'Pendiente cobro',
                'prefijo'=> '$ ',
                'valor'  => number_format($totales['pendiente_cobro'], 2),
                'color'  => 'warning',
            ])
        </div>
        <div class="col-sm-6 col-xl-2">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-lock',
                'titulo' => 'Inmovilizadas',
                'valor'  => number_format($totales['inmovilizaciones_activas']),
                'color'  => 'danger',
            ])
        </div>
    </div>

    {{-- ── Filtros ────────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.infracciones.index') }}">
                <div class="row g-2">

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small">Desde</label>
                        <input type="text" name="fecha_desde" id="filtroDesde"
                               class="form-control form-control-sm"
                               value="{{ request('fecha_desde') }}" placeholder="AAAA-MM-DD">
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small">Hasta</label>
                        <input type="text" name="fecha_hasta" id="filtroHasta"
                               class="form-control form-control-sm"
                               value="{{ request('fecha_hasta') }}" placeholder="AAAA-MM-DD">
                    </div>

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach($estadosInfr as $e)
                                <option value="{{ $e->value }}" {{ request('estado') === $e->value ? 'selected' : '' }}>
                                    {{ $e->etiqueta() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label small">Tipo</label>
                        <select name="tipo_infraccion" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach($tiposInfr as $t)
                                <option value="{{ $t->value }}" {{ request('tipo_infraccion') === $t->value ? 'selected' : '' }}>
                                    {{ $t->etiqueta() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-6 col-lg-1">
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

                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label small">Agente</label>
                        <select name="agente_parqueo_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach($agentes as $a)
                                <option value="{{ $a->id }}" {{ request('agente_parqueo_id') == $a->id ? 'selected' : '' }}>
                                    {{ $a->codigo }} — {{ $a->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('reportes.infracciones.index') }}" class="btn btn-sm btn-outline-secondary">
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

    {{-- ── Tabla de infracciones ───────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($infracciones->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No hay infracciones para los filtros seleccionados.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Placa</th>
                                <th>Zona</th>
                                <th>Agente</th>
                                <th>Estado</th>
                                <th class="text-center">Inmov.</th>
                                <th class="text-end">Multa</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($infracciones as $inf)
                                <tr>
                                    <td class="text-nowrap small">
                                        {{ $inf->created_at->isoFormat('D MMM YYYY') }}
                                    </td>
                                    <td class="small">{{ $inf->tipo_infraccion->etiqueta() }}</td>
                                    <td class="fw-semibold small">{{ $inf->placa }}</td>
                                    <td class="small">{{ $inf->zona?->nombre ?? '—' }}</td>
                                    <td class="small">{{ $inf->agente?->nombre_completo ?? '—' }}</td>
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
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        $ {{ number_format((float) $inf->monto_multa, 2) }}
                                    </td>
                                    <td>
                                        @can('infracciones.ver')
                                            <a href="{{ route('infracciones.show', $inf) }}"
                                               class="btn btn-xs btn-outline-secondary" title="Ver detalle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="7" class="text-end small">Total (página {{ $infracciones->currentPage() }})</th>
                                <th class="text-end">$ {{ number_format($infracciones->sum('monto_multa'), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <small class="text-muted">
                        {{ $infracciones->total() }} infracciones · Página {{ $infracciones->currentPage() }} de {{ $infracciones->lastPage() }}
                    </small>
                    {{ $infracciones->links() }}
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof flatpickr !== 'undefined') {
        const op = { dateFormat: 'Y-m-d', allowInput: true };
        flatpickr('#filtroDesde', op);
        flatpickr('#filtroHasta', op);
    }
});
</script>
@endpush
