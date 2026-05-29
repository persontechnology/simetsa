@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('conductores.index') }}
@endsection

@section('content')
{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('conductores.index') }}">
    <div class="col-sm-6 col-md-5">
        <input type="text" name="buscar" class="form-control"
               placeholder="Código, nombre, correo o cédula"
               value="{{ request('buscar') }}">
    </div>
    <div class="col-sm-4 col-md-3">
        <select name="estado" class="form-select">
            <option value="">Todos los estados</option>
            @foreach($estados as $val => $etiqueta)
                <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="{{ route('conductores.index') }}" class="btn btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Cédula</th>
                    <th>Celular</th>
                    <th>Estado</th>
                    <th>Registrado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conductores as $c)
                    <tr>
                        <td><code class="small">{{ $c->codigo }}</code></td>
                        <td><strong>{{ $c->user?->name ?? '—' }}</strong></td>
                        <td class="text-muted small">{{ $c->user?->email }}</td>
                        <td class="small">{{ $c->user?->perfil?->cedula ?? '—' }}</td>
                        <td class="small">{{ $c->user?->perfil?->telefono_celular ?? '—' }}</td>
                        <td>
                            @if($c->estado === 'activo')
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Bloqueado</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $c->created_at?->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('conductores.show', $c) }}"
                               class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('conductores.editar')
                                @if($c->estado === 'activo')
                                    <form method="POST" action="{{ route('conductores.bloquear', $c) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-warning"
                                                title="Bloquear"
                                                data-confirm
                                                data-action="bloquear"
                                                data-msg="¿Bloquear al conductor {{ $c->codigo }}?">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('conductores.desbloquear', $c) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                                title="Desbloquear"
                                                data-confirm
                                                data-action="desbloquear"
                                                data-msg="¿Desbloquear al conductor {{ $c->codigo }}?">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay conductores registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($conductores->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $conductores->links() }}</div>
    @endif
</div>
@endsection
