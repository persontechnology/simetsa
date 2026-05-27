{{-- resources/views/parametros/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('parametros.index') }}
@endsection


@section('content')

    <div class="alert alert-info small">
        <i class="ph ph-info me-1"></i>
        Estos parámetros gobiernan el comportamiento del SIMETSA y derivan
        de la Ordenanza vigente. Los valores se aplican automáticamente al
        resto de los módulos (tickets, multas, liquidaciones).
        Los cambios quedan registrados con fecha en la columna de actualización.
    </div>

    @foreach($parametrosPorCategoria as $categoria => $grupo)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 text-simetsa">
                    @switch($categoria)
                        @case('institucion')    <i class="ph ph-bank me-1"></i>            Institución           @break
                        @case('operacion')      <i class="ph ph-gear me-1"></i>                Operación             @break
                        @case('agentes')        <i class="ph ph-person me-1"></i>        Agentes de parqueo    @break
                        @case('puntos_venta')   <i class="ph ph-storefront me-1"></i>                Puntos de venta       @break
                        @case('app_movil')      <i class="ph ph-device-mobile me-1"></i>               Aplicación móvil      @break
                        @case('liquidaciones')  <i class="ph ph-money me-1"></i>           Liquidaciones         @break
                        @case('multas')         <i class="ph ph-currency-dollar me-1"></i> Multas y sanciones   @break
                        @case('sanciones')      <i class="ph ph-coins me-1"></i>               Sanciones administrativas @break
                        @default                <i class="ph ph-tag me-1"></i> {{ ucfirst($categoria) }}
                    @endswitch
                </h2>
                <span class="badge bg-secondary">{{ $grupo->count() }} parámetros</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 22%;">Clave</th>
                            <th>Descripción</th>
                            <th style="width: 10%;">Valor</th>
                            <th style="width: 10%;">Artículo</th>
                            <th style="width: 18%;">Última modificación</th>
                            <th class="text-end" style="width: 8%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grupo as $p)
                            <tr>
                                <td><code class="small">{{ $p->clave }}</code></td>
                                <td class="small text-muted">{{ $p->descripcion ?? '—' }}</td>
                                <td>
                                    <strong class="text-simetsa">{{ $p->valor_formateado }}</strong>
                                </td>
                                <td>
                                    @if($p->articulo_ordenanza)
                                        <span class="badge bg-light text-dark border">{{ $p->articulo_ordenanza }}</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($p->ultimaBitacora)
                                        <div>{{ $p->ultimaBitacora->ocurrido_en?->format('d/m/Y H:i') }}</div>
                                        <div class="text-muted">
                                            <i class="bi bi-person"></i>
                                            {{ $p->ultimaBitacora->user?->name ?? 'Sistema' }}
                                        </div>
                                    @else
                                        <span class="text-muted">— Sin cambios —</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('parametros.editar')
                                        @if($p->editable)
                                            <a href="{{ route('parametros.edit', $p) }}"
                                            class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="ph ph-pencil"></i>
                                            </a>
                                        @else
                                            <span class="text-muted small" title="Parámetro bloqueado">
                                                <i class="ph ph-lock"></i>
                                            </span>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

@endsection