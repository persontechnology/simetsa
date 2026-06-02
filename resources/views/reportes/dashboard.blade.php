{{-- resources/views/reportes/dashboard.blade.php --}}
{{--
    Dashboard de KPIs del SIMETSA. (Fase 8.A)
    Acceso: super_admin, comisario, director_seguridad (permiso kpi.ver).
    Los KPIs se cachean 5 min en el servidor y se actualizan en el cliente
    via polling AJAX cada 60 s al endpoint GET /dashboard/kpis.
--}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('dashboard') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-flex align-items-center text-muted small">
        <i class="bi bi-arrow-clockwise me-1" id="kpi-spinner"></i>
        Actualización automática cada 60 s
    </div>
@endsection

@push('scriptsHeader')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@section('content')

    {{-- Aviso de perfil incompleto --}}
    @auth
        @unless(Auth::user()->tienePerfilCompleto())
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div class="flex-grow-1">
                    Aún no completaste tu perfil. Para operar dentro del SIMETSA
                    debes registrar tus datos personales y aceptar los términos LOPDP.
                </div>
                <a href="{{ route('perfil.completar') }}" class="btn btn-sm btn-warning ms-2">
                    Completar ahora
                </a>
            </div>
        @endunless
    @endauth

    {{-- ================================================================ --}}
    {{-- Tarjetas KPI (solo para roles con kpi.ver)                        --}}
    {{-- ================================================================ --}}
    @if($tieneKpi)
    <div class="row g-3 mb-4">

        <div class="col-sm-6 col-xl-4">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-ticket-perforated',
                'titulo' => 'Tickets activos ahora',
                'valor'  => number_format($kpis['tickets_activos']),
                'color'  => 'success',
                'id'     => 'kpi-tickets-activos',
            ])
        </div>

        <div class="col-sm-6 col-xl-4">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-cash-coin',
                'titulo' => 'Recaudación hoy',
                'prefijo' => '$ ',
                'valor'  => number_format($kpis['recaudacion_hoy'], 2),
                'color'  => 'primary',
                'id'     => 'kpi-recaudacion-hoy',
            ])
        </div>

        <div class="col-sm-6 col-xl-4">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-calendar-month',
                'titulo' => 'Recaudación del mes',
                'prefijo' => '$ ',
                'valor'  => number_format($kpis['recaudacion_mes'], 2),
                'color'  => 'info',
                'id'     => 'kpi-recaudacion-mes',
            ])
        </div>

        <div class="col-sm-6 col-xl-4">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-exclamation-triangle',
                'titulo' => 'Infracciones pendientes',
                'valor'  => number_format($kpis['infracciones_pendientes']),
                'color'  => 'danger',
                'id'     => 'kpi-infracciones-pendientes',
            ])
        </div>

        <div class="col-sm-6 col-xl-4">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-p-square',
                'titulo' => 'Plazas ocupadas ahora',
                'valor'  => number_format($kpis['plazas_ocupadas']),
                'color'  => 'warning',
                'id'     => 'kpi-plazas-ocupadas',
            ])
        </div>

        <div class="col-sm-6 col-xl-4">
            @include('reportes._partials.kpi-card', [
                'icono'  => 'bi bi-person-badge',
                'titulo' => 'Agentes activos hoy',
                'valor'  => number_format($kpis['agentes_hoy']),
                'color'  => 'secondary',
                'id'     => 'kpi-agentes-hoy',
            ])
        </div>

    </div>

    {{-- ================================================================ --}}
    {{-- Gráficos                                                           --}}
    {{-- ================================================================ --}}
    <div class="row g-3">

        {{-- Recaudación últimos 30 días --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0">Recaudación — últimos 30 días</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartRecaudacion" height="120"></canvas>
                </div>
            </div>
        </div>

        {{-- Distribución por método de pago --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0">Método de pago — hoy</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartMetodoPago" style="max-height:200px;"></canvas>
                </div>
            </div>
        </div>

        {{-- Tickets por zona hoy --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="mb-0">Tickets por zona — hoy</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartTicketsZona" height="80"></canvas>
                </div>
            </div>
        </div>

    </div>
    @endif {{-- @if($tieneKpi) --}}

@endsection

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {

    @if($tieneKpi)
    // ── Datos del servidor (pasados por Blade) ─────────────────────────────
    const datosRecaudacion = @json($recaudacionDia);
    const datosZona        = @json($ticketsPorZona);
    const datosMetodo      = @json($metodoPago);

    const colores = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6c757d','#fd7e14'];

    // ── Chart: Recaudación por día ─────────────────────────────────────────
    new Chart(document.getElementById('chartRecaudacion'), {
        type: 'line',
        data: {
            labels: datosRecaudacion.labels,
            datasets: [{
                label: 'Recaudación ($)',
                data: datosRecaudacion.data,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.08)',
                tension: 0.3,
                fill: true,
                pointRadius: 3,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '$ ' + v.toFixed(2) } },
            },
        },
    });

    // ── Chart: Tickets por zona ────────────────────────────────────────────
    new Chart(document.getElementById('chartTicketsZona'), {
        type: 'bar',
        data: {
            labels: datosZona.labels,
            datasets: [{
                label: 'Tickets',
                data: datosZona.data,
                backgroundColor: colores,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
    });

    // ── Chart: Método de pago ──────────────────────────────────────────────
    new Chart(document.getElementById('chartMetodoPago'), {
        type: 'doughnut',
        data: {
            labels: datosMetodo.labels.length ? datosMetodo.labels : ['Sin datos'],
            datasets: [{
                data: datosMetodo.data.length ? datosMetodo.data : [1],
                backgroundColor: datosMetodo.data.length ? colores : ['#dee2e6'],
            }],
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
        },
    });

    // ── Polling AJAX: actualización de KPIs cada 60 s ─────────────────────
    const kpiMap = {
        tickets_activos:       { el: document.getElementById('kpi-tickets-activos'),        fmt: v => v.toLocaleString() },
        recaudacion_hoy:       { el: document.getElementById('kpi-recaudacion-hoy'),        fmt: v => '$ ' + v.toFixed(2) },
        recaudacion_mes:       { el: document.getElementById('kpi-recaudacion-mes'),        fmt: v => '$ ' + v.toFixed(2) },
        infracciones_pendientes: { el: document.getElementById('kpi-infracciones-pendientes'), fmt: v => v.toLocaleString() },
        plazas_ocupadas:       { el: document.getElementById('kpi-plazas-ocupadas'),        fmt: v => v.toLocaleString() },
        agentes_hoy:           { el: document.getElementById('kpi-agentes-hoy'),            fmt: v => v.toLocaleString() },
    };

    function actualizarKpis() {
        fetch('{{ route('dashboard.kpis') }}', { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                for (const [key, cfg] of Object.entries(kpiMap)) {
                    if (cfg.el && data[key] !== undefined) {
                        cfg.el.textContent = cfg.fmt(parseFloat(data[key]));
                    }
                }
            })
            .catch(() => {});
    }

    setInterval(actualizarKpis, 60_000);
    @endif {{-- @if($tieneKpi) --}}

});
</script>
@endpush
