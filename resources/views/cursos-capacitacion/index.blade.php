{{-- resources/views/cursos-capacitacion/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{  Breadcrumbs::render('cursos-capacitacion.index') }}
@endsection
@section('breadcrumb_elements')
    <div class="d-lg-flex mb-2 mb-lg-0">
        @can('agentes.crear')
            <a href="{{ route('cursos-capacitacion.create') }}" class="d-flex align-items-center text-body py-2">
                <i class="bi bi-plus-lg me-2"></i>
                Nuevo curso
            </a>
        @endcan
      
    </div>
@endsection



@section('content')
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Folio</th><th>Nombre</th><th>Fechas</th><th>Cupo</th><th>Inscritos</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                @forelse($cursos as $c)
                    <tr>
                        <td><code>{{ $c->codigo }}</code></td>
                        <td>{{ $c->nombre }}</td>
                        <td class="small">{{ $c->fecha_inicio?->format('d/m/Y') }} – {{ $c->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $c->cupo ?? '—' }}</td>
                        <td>{{ $c->inscripciones_count }}</td>
                        <td><span class="badge bg-{{ $c->estado_color }}">{{ $c->estado_label }}</span></td>
                        <td class="text-end">
                            @can('agentes.ver')
                                <a href="{{ route('cursos-capacitacion.show', $c) }}" class="btn btn-sm btn-outline-secondary" title="Gestionar"><i class="bi bi-eye"></i></a>
                            @endcan
                            @can('agentes.editar')
                                <a href="{{ route('cursos-capacitacion.edit', $c) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('agentes.eliminar')
                                <form method="POST" action="{{ route('cursos-capacitacion.destroy', $c) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar el curso {{ $c->codigo }}?')"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay cursos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cursos->hasPages())<div class="card-footer bg-white border-top-0">{{ $cursos->links() }}</div>@endif
</div>
@endsection