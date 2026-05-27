{{-- resources/views/registro-accesos/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('accesos.index') }}
@endsection

@section('content')

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('accesos.index') }}" class="row g-3 align-items-end">

                <div class="col-12 col-lg-4">
                    <label for="buscar" class="form-label small mb-1">Buscar</label>
                    <input type="text" name="buscar" id="buscar" class="form-control"
                        placeholder="Nombre o email"
                        value="{{ $filtros['buscar'] ?? '' }}">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="evento" class="form-label small mb-1">Evento</label>
                    <select name="evento" id="evento" class="form-select">
                        <option value="">Todos los eventos</option>
                        @foreach($eventos as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['evento'] ?? '') === $valor)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-sm-3 col-lg-2">
                    <label for="desde" class="form-label small mb-1">Desde</label>
                    <input type="date" name="desde" id="desde" class="form-control"
                        value="{{ $filtros['desde'] ?? '' }}">
                </div>

                <div class="col-6 col-sm-3 col-lg-2">
                    <label for="hasta" class="form-label small mb-1">Hasta</label>
                    <input type="date" name="hasta" id="hasta" class="form-control"
                        value="{{ $filtros['hasta'] ?? '' }}">
                </div>

                <div class="col-12 col-lg-2">
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="ph ph-faders-horizontal me-1"></i>
                            Filtrar
                        </button>

                        <a href="{{ route('accesos.index') }}"
                        class="btn btn-outline-secondary"
                        title="Limpiar filtros">
                            <i class="ph ph-backspace"></i>
                        </a>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Evento</th>
                        <th>IP</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $r)
                        <tr>
                            <td>
                                <span class="text-nowrap">
                                    {{ $r->ocurrido_en->format('d/m/Y H:i:s') }}
                                </span>
                            </td>
                            <td>
                                @if($r->user)
                                    <strong>{{ $r->user->name }}</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <code class="small">
                                    {{ $r->email_intento ?? $r->user?->email ?? '—' }}
                                </code>
                            </td>
                            <td>
                                <span class="badge bg-{{ $r->color_badge }}">
                                    {{ $r->evento_etiqueta }}
                                </span>
                            </td>
                            <td>
                                <code class="small">{{ $r->ip ?? '—' }}</code>
                            </td>
                            <td>
                                <span class="text-muted small text-truncate d-inline-block"
                                      style="max-width: 240px;"
                                      data-bs-toggle="tooltip"
                                      title="{{ $r->user_agent ?? '' }}">
                                    {{ $r->user_agent ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay registros de acceso que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($registros->hasPages())
            <div class="card-footer bg-white border-top-0">
                {{ $registros->links() }}
            </div>
        @endif
    </div>

@endsection