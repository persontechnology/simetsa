@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('tipos-plaza.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-2 mb-lg-0">
        
        @can('create', App\Models\TipoPlaza::class)
            <a href="{{ route('tipos-plaza.create') }}" class="d-flex align-items-center text-body py-2">
                <i class="ph ph-plus me-1"></i> Nuevo tipo de plaza
            </a>
        @endcan
            
    </div>
@endsection


@section('content')
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Color</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tarifado</th>
                    <th>Credencial</th>
                    <th>Dimensiones sugeridas</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tipos as $t)
                    <tr>
                        <td>
                            <span class="d-inline-block rounded-circle" title="{{ $t->color_mapa }}"
                                  style="width:24px;height:24px;background:{{ $t->color_mapa }};">
                            </span>
                            @if($t->icono)<i class="bi {{ $t->icono }} ms-2"></i>@endif
                        </td>
                        <td><code class="small">{{ $t->codigo }}</code></td>
                        <td>
                            <strong>{{ $t->nombre }}</strong>
                            @if($t->descripcion)
                                <div class="small text-muted">{{ Str::limit($t->descripcion, 80) }}</div>
                            @endif
                        </td>
                        <td>
                            @if($t->es_pagado)
                                <span class="badge bg-warning text-dark">Pagado</span>
                            @else
                                <span class="badge bg-success">Exonerado</span>
                            @endif
                        </td>
                        <td>
                            @if($t->requiere_credencial)
                                <span class="badge bg-info">Sí</span>
                            @else
                                <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td class="text-nowrap small">
                            {{ $t->dimensiones_sugeridas }}
                        </td>
                        <td>
                            @if($t->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('tipos_plaza.editar')
                                <a href="{{ route('tipos-plaza.edit', $t) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endcan
                            @can('tipos_plaza.eliminar')
                                <form method="POST" action="{{ route('tipos-plaza.destroy', $t) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Desactivar"
                                            data-confirm
                                            data-action="desactivar"
                                            data-msg="¿Desactivar el tipo {{ $t->nombre }}?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay tipos de plaza registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tipos->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $tipos->links() }}</div>
    @endif
</div>
@endsection