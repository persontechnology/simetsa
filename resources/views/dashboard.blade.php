{{-- resources/views/dashboard.blade.php --}}
{{--
    Pantalla de inicio (dashboard) del SIMETSA.

    En FASE 1 muestra:
      - Tarjeta de bienvenida con el rol del usuario.
      - Aviso si el perfil aún no está completo (consentimiento LOPDP).
      - Placeholders para KPIs que se llenarán en FASE 8.
--}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('dashboard') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-2 mb-lg-0">
        <a href="#" class="d-flex align-items-center text-body py-2">
            <i class="ph-lifebuoy me-2"></i>
            Support
        </a>

        <div class="dropdown ms-lg-3">
            <a href="#" class="d-flex align-items-center text-body dropdown-toggle py-2" data-bs-toggle="dropdown">
                <i class="ph-gear me-2"></i>
                <span class="flex-1">Settings</span>
            </a>

            <div class="dropdown-menu dropdown-menu-end w-100 w-lg-auto">
                <a href="#" class="dropdown-item">
                    <i class="ph-shield-warning me-2"></i>
                    Account security
                </a>
                <a href="#" class="dropdown-item">
                    <i class="ph-chart-bar me-2"></i>
                    Analytics
                </a>
                <a href="#" class="dropdown-item">
                    <i class="ph-lock-key me-2"></i>
                    Privacy
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="ph-gear me-2"></i>
                    All settings
                </a>
            </div>
        </div>
    </div>
@endsection


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

    <div class="row g-3">

        {{-- Tarjeta bienvenida --}}
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 card-title mb-1">
                        Hola, {{ Auth::user()->name ?? 'Invitado' }}
                    </h2>
                    @auth
                        <p class="text-muted small mb-2">
                            Rol:
                            <span class="badge bg-simetsa">
                                {{ Auth::user()->roles->pluck('name')->map(fn($r) => \App\Enums\RolSistema::tryFrom($r)?->etiqueta() ?? $r)->join(', ') ?: 'Sin rol' }}
                            </span>
                        </p>
                        <p class="small mb-0 text-muted">
                            Última sesión: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
                        </p>
                    @endauth
                </div>
            </div>
        </div>

        {{-- KPI placeholder: Tickets vendidos hoy --}}
        @can('reportes.ver')
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 text-muted small">
                        <i class="bi bi-ticket-perforated me-1"></i> Tickets vendidos hoy
                    </div>
                    <div class="display-6 fw-semibold text-simetsa">—</div>
                    <div class="small text-muted">Disponible en Fase 8 (Reportes)</div>
                </div>
            </div>
        </div>
        @endcan

        {{-- KPI placeholder: Recaudación de hoy --}}
        @can('reportes.ver')
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 text-muted small">
                        <i class="bi bi-cash-coin me-1"></i> Recaudación de hoy
                    </div>
                    <div class="display-6 fw-semibold text-simetsa">$ —</div>
                    <div class="small text-muted">Disponible en Fase 8 (Reportes)</div>
                </div>
            </div>
        </div>
        @endcan

    </div>

    {{-- Sección de referencia legal --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h6 text-muted">Marco legal</h2>
            <p class="small mb-1">
                Sistema desarrollado conforme a la
                <em>Ordenanza de Creación y Funcionamiento del Sistema Municipal
                de Estacionamiento Tarifado del Cantón Salcedo (SIMETSA)</em>,
                aprobada el 06-feb-2020 y sancionada el 10-feb-2020.
            </p>
            <p class="small text-muted mb-0">
                Tarifa vigente: $0.25 por hora · Tiempo máximo: 2 horas (Art. 14) ·
                Tolerancia: 5 minutos (Art. 13) · Horario: martes-viernes y domingo 08:00-18:00 (Art. 12).
            </p>
        </div>
    </div>

@endsection