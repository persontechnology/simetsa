@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('tipos-vehiculo.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-2 mb-lg-0">
        @can('tipos_vehiculo.crear')
            <a href="{{ route('tipos-vehiculo.create') }}" class="d-flex align-items-center text-body py-2">
                <i class="ph ph-plus me-1"></i> Nuevo tipo de vehículo
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
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Aplica tarifa</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tipos as $t)
                    <tr>
                        <td><code class="small">{{ $t->codigo }}</code></td>
                        <td><strong>{{ $t->nombre }}</strong></td>
                        <td class="text-muted small">{{ Str::limit($t->descripcion, 80) }}</td>
                        <td>
                            @if($t->aplica_tarifa)
                                <span class="badge bg-warning text-dark">Pagado</span>
                            @else
                                <span class="badge bg-success">Exonerado</span>
                            @endif
                        </td>
                        <td>
                            @if($t->activo)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('tipos_vehiculo.editar')
                                <a href="{{ route('tipos-vehiculo.edit', $t) }}"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endcan
                            @can('tipos_vehiculo.eliminar')
                                <form method="POST" action="{{ route('tipos-vehiculo.destroy', $t) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Desactivar"
                                            data-confirm
                                            data-action="desactivar"
                                            data-msg="¿Desactivar el tipo '{{ $t->nombre }}'?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No hay tipos de vehículo registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tipos->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $tipos->links() }}</div>
    @endif
</div>
@endsection
