@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('tarifas.index') }}
@endsection

@section('content')

    <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>
        Cada tipo de plaza tiene un historial de tarifas con su rango de vigencia.
        La tarifa <strong>vigente</strong> es la que se aplica a los tickets emitidos hoy.
        Las tarifas <strong>expiradas</strong> se conservan para reportes históricos y no se eliminan.
    </div>

    @foreach($tiposPlaza as $tipo)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 text-simetsa">
                    <span class="d-inline-block rounded-circle me-2"
                          style="width:14px;height:14px;background:{{ $tipo->color_mapa }};vertical-align:middle;"></span>
                    @if($tipo->icono)<i class="bi {{ $tipo->icono }} me-1"></i>@endif
                    {{ $tipo->nombre }}
                </h2>
                <div class="small text-muted">
                    @if($tipo->es_pagado)
                        <span class="badge bg-warning text-dark">Tarifado</span>
                    @else
                        <span class="badge bg-success">Exonerado</span>
                    @endif
                </div>
            </div>

            @if($tipo->tarifas->isEmpty())
                <div class="card-body text-muted small">
                    Sin tarifas registradas para este tipo de plaza.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Nombre</th>
                                <th class="text-end">$/hora</th>
                                <th>Vigente desde</th>
                                <th>Vigente hasta</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tipo->tarifas as $t)
                                <tr>
                                    <td>
                                        {{ $t->nombre }}
                                        @if($t->descripcion)
                                            <div class="small text-muted">{{ Str::limit($t->descripcion, 80) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-simetsa">
                                            $ {{ number_format((float) $t->valor_hora, 2) }}
                                        </strong>
                                    </td>
                                    <td>{{ $t->vigente_desde?->format('d/m/Y') }}</td>
                                    <td>
                                        {{ $t->vigente_hasta?->format('d/m/Y') ?? '— sin fin —' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $t->color_badge }}">
                                            {{ $t->estado_etiqueta }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @can('tarifas.editar')
                                            <a href="{{ route('tarifas.edit', $t) }}"
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('tarifas.eliminar')
                                            <form method="POST" action="{{ route('tarifas.destroy', $t) }}"
                                                  class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Eliminar"
                                                        data-confirm
                                                        data-action="eliminar"
                                                        data-msg="¿Eliminar la tarifa {{ $t->nombre }}? Quedará en estado soft-deleted.">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach

@endsection