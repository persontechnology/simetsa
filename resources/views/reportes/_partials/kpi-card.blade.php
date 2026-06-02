{{--
    resources/views/reportes/_partials/kpi-card.blade.php

    Tarjeta de KPI reutilizable para el dashboard de reportes (Fase 8.A).

    Props:
      $icono      — clase Bootstrap Icon (ej. "bi bi-cash-coin")
      $titulo     — texto del label de la métrica
      $valor      — valor principal (string o número)
      $prefijo    — (opcional) prefijo antes del valor (ej. "$")
      $color      — (opcional) color Bootstrap del ícono: primary, success, warning, danger, info. Default: primary
      $id         — (opcional) id HTML del elemento del valor (para actualización AJAX)
--}}
<div class="card border-0 shadow-sm h-100">
    <div class="card-body d-flex flex-column justify-content-between">
        <div class="d-flex align-items-center mb-2 text-muted small">
            <i class="{{ $icono ?? 'bi bi-graph-up' }} me-2 text-{{ $color ?? 'primary' }}"></i>
            {{ $titulo }}
        </div>
        <div class="display-6 fw-semibold text-{{ $color ?? 'primary' }}"
             @isset($id) id="{{ $id }}" @endisset>
            {{ $prefijo ?? '' }}{{ $valor }}
        </div>
    </div>
</div>
