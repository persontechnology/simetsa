{{-- resources/views/reportes/ocupacion/index.blade.php --}}
{{-- Reporte de ocupación de plazas por hora/día/zona. (Fase 8.D) --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('reportes.ocupacion') }}
@endsection

@section('content')

    {{-- ── Filtros ────────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h6 class="mb-0"><i class="bi bi-funnel me-1"></i> Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.ocupacion.index') }}">
                <div class="row g-2 align-items-end">

                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label small">Desde</label>
                        <input type="text" name="fecha_desde" id="filtroDesde"
                               class="form-control form-control-sm"
                               value="{{ $fechaDesde ?? now()->subDays(29)->toDateString() }}"
                               placeholder="AAAA-MM-DD">
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label small">Hasta</label>
                        <input type="text" name="fecha_hasta" id="filtroHasta"
                               class="form-control form-control-sm"
                               value="{{ $fechaHasta ?? now()->toDateString() }}"
                               placeholder="AAAA-MM-DD">
                    </div>

                    <div class="col-sm-6 col-lg-3">
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

                    <div class="col-sm-6 col-lg-3 d-flex gap-2">
                        <a href="{{ route('reportes.ocupacion.index') }}" class="btn btn-sm btn-outline-secondary">
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

    {{-- ── Tarjetas KPI ───────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-p-square',
                'titulo' => 'Sesiones en el período',
                'valor'  => number_format($totales['total_sesiones']),
                'color'  => 'primary',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-clock-history',
                'titulo' => 'Duración promedio',
                'valor'  => number_format($totales['duracion_promedio_min'], 1) . ' min',
                'color'  => 'info',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-graph-up-arrow',
                'titulo' => 'Hora pico',
                'valor'  => $totales['hora_pico'] !== null ? sprintf('%02d:00', $totales['hora_pico']) : '—',
                'color'  => 'warning',
            ])
        </div>
        <div class="col-sm-6 col-xl-3">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-car-front',
                'titulo' => 'Ocupadas ahora',
                'valor'  => number_format($totales['sesiones_activas']),
                'color'  => 'success',
            ])
        </div>
    </div>

    {{-- ── Gráficos ───────────────────────────────────────────────────── --}}
    <div class="row g-3">

        {{-- Sesiones por día --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0">Sesiones por día</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartPorDia" height="70"></canvas>
                </div>
            </div>
        </div>

        {{-- Sesiones por hora + por zona --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0">Distribución por hora del día</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartPorHora" height="130"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0">Sesiones por zona</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    @if(count($porZona['labels']))
                        <canvas id="chartPorZona" style="max-height:200px;"></canvas>
                    @else
                        <p class="text-muted small mb-0">Sin datos de zona en el período.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scriptsHeader')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const porDia  = @json($porDia);
    const porHora = @json($porHora);
    const porZona = @json($porZona);
    const colores = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6c757d','#fd7e14'];

    new Chart(document.getElementById('chartPorDia'), {
        type: 'bar',
        data: {
            labels: porDia.labels,
            datasets: [{ label: 'Sesiones', data: porDia.data, backgroundColor: '#0d6efd80', borderColor: '#0d6efd', borderWidth: 1 }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } },
    });

    new Chart(document.getElementById('chartPorHora'), {
        type: 'bar',
        data: {
            labels: porHora.labels,
            datasets: [{ label: 'Sesiones', data: porHora.data, backgroundColor: '#19875480', borderColor: '#198754', borderWidth: 1 }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } },
    });

    if (porZona.labels.length) {
        new Chart(document.getElementById('chartPorZona'), {
            type: 'doughnut',
            data: {
                labels: porZona.labels,
                datasets: [{ data: porZona.data, backgroundColor: colores }],
            },
            options: { plugins: { legend: { position: 'bottom' } } },
        });
    }

    if (typeof flatpickr !== 'undefined') {
        const op = { dateFormat: 'Y-m-d', allowInput: true };
        flatpickr('#filtroDesde', op);
        flatpickr('#filtroHasta', op);
    }

});
</script>
@endpush
