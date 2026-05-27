{{-- resources/views/usuarios/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('usuarios.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-3 mb-lg-0">
        @can('create', App\Models\User::class)
            <a href="{{ route('usuarios.create') }}" class="btn btn-link text-dark px-0 py-2 d-flex align-items-center text-decoration-none">
                <i class="bi bi-plus-lg me-2"></i> Nuevo usuario
            </a>
        @endcan
    </div>
@endsection

@section('content')
    {{-- Filtros compactos --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('usuarios.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="buscar" class="form-label small text-secondary fw-medium mb-1">Buscar usuario</label>
                    <input type="text" name="buscar" id="buscar" class="form-control form-control-sm"
                        placeholder="Nombre, email o cédula"
                        value="{{ $filtros['buscar'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="rol" class="form-label small text-secondary fw-medium mb-1">Rol</label>
                    <select name="rol" id="rol" class="form-select form-select-sm">
                        <option value="">Todos los roles</option>
                        @foreach($roles as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string)($filtros['rol'] ?? '') === (string)$valor)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="activo" class="form-label small text-secondary fw-medium mb-1">Estado</label>
                    <select name="activo" id="activo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" @selected(($filtros['activo'] ?? '') === '1')>Activos</option>
                        <option value="0" @selected(($filtros['activo'] ?? '') === '0')>Inactivos</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <div class="d-flex justify-content-lg-end gap-2">
                        <a href="{{ route('usuarios.index') }}" class="btn btn-sm btn-light text-secondary border">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
                        </a>
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-sliders me-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla ultracompacta con table-sm --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="small text-uppercase text-muted border-bottom">
                    <tr>
                        <th class="ps-4 fw-semibold">Usuario</th>
                        <th class="fw-semibold">Cédula</th>
                        <th class="fw-semibold">Roles</th>
                        <th class="fw-semibold">Celular</th>
                        <th class="fw-semibold">Estado</th>
                        <th class="text-end pe-4 fw-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $u)
                        <tr class="border-bottom">
                            {{-- Usuario: nombre y email juntos --}}
                            <td class="ps-4 text-nowrap">
                                <span class="fw-semibold">{{ $u->name }}</span>
                                <span class="text-muted small ms-2">{{ $u->email }}</span>
                            </td>

                            {{-- Cédula: plano --}}
                            <td class="font-monospace text-secondary text-nowrap">
                                {{ $u->perfil?->cedula ?? '—' }}
                            </td>

                            {{-- Roles: máx 2 en línea + badge +N --}}
                            <td class="text-nowrap">
                                @php
                                    $rolesList = $u->roles->map(fn($rol) => 
                                        \App\Enums\RolSistema::tryFrom($rol->name)?->etiqueta() 
                                        ?? ucwords(str_replace('_', ' ', $rol->name))
                                    )->toArray();
                                    $total = count($rolesList);
                                    $visible = array_slice($rolesList, 0, 2);
                                    $hidden = $total - 2;
                                @endphp

                                @if($total === 0)
                                    <span class="text-muted fst-italic small">Sin rol</span>
                                @else
                                    @foreach($visible as $idx => $rolNombre)
                                        <span class="text-secondary small">{{ $rolNombre }}</span>
                                        @if($idx < count($visible)-1 || $hidden > 0)
                                            <span class="text-muted mx-1">•</span>
                                        @endif
                                    @endforeach
                                    @if($hidden > 0)
                                        <span class="badge bg-light text-dark rounded-pill fw-normal px-2 py-0 ms-1"
                                              data-bs-toggle="tooltip"
                                              data-bs-html="true"
                                              title="{{ implode('<br>', array_slice($rolesList, 2)) }}">
                                            +{{ $hidden }}
                                        </span>
                                    @endif
                                @endif
                            </td>

                            {{-- Celular: plano --}}
                            <td class="text-secondary text-nowrap">
                                {{ $u->perfil?->telefono_celular ?? '—' }}
                            </td>

                            {{-- Estado: ícono + texto --}}
                            <td class="text-nowrap">
                                @if($u->perfil && $u->perfil->activo && !$u->perfil->trashed())
                                    <span class="text-success">
                                        <i class="bi bi-circle-fill small me-1" style="font-size: 0.6rem;"></i> Activo
                                    </span>
                                @else
                                    <span class="text-secondary">
                                        <i class="bi bi-circle-fill small me-1" style="font-size: 0.6rem;"></i> Inactivo
                                    </span>
                                @endif
                            </td>

                            {{-- Acciones dropdown compacto --}}
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 border-0"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            aria-label="Acciones del usuario">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-1">
                                        @can('view', $u)
                                            <a href="{{ route('usuarios.show', $u) }}" class="dropdown-item py-1 px-3">
                                                <i class="bi bi-eye me-2 text-muted"></i> Ver detalles
                                            </a>
                                        @endcan
                                        @can('update', $u)
                                            <a href="{{ route('usuarios.edit', $u) }}" class="dropdown-item py-1 px-3">
                                                <i class="bi bi-pencil me-2 text-muted"></i> Editar datos
                                            </a>
                                        @endcan
                                        @if($u->perfil && $u->perfil->trashed())
                                            @can('update', $u)
                                                <hr class="dropdown-divider my-1">
                                                <button type="button" class="dropdown-item py-1 px-3 text-success"
                                                        data-confirm data-action="reactivar" data-method="PATCH"
                                                        data-url="{{ route('usuarios.reactivar', $u) }}">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i> Reactivar
                                                </button>
                                            @endcan
                                        @else
                                            @can('delete', $u)
                                                <hr class="dropdown-divider my-1">
                                                <button type="button" class="dropdown-item py-1 px-3 text-danger"
                                                        data-confirm data-action="desactivar" data-method="DELETE"
                                                        data-url="{{ route('usuarios.destroy', $u) }}">
                                                    <i class="bi bi-trash me-2"></i> Desactivar
                                                </button>
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-person-x d-block mb-1 opacity-50"></i>
                                No se encontraron usuarios
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($usuarios->hasPages())
            <div class="card-footer bg-white border-top-0 pt-2 pb-2 px-4">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
@endsection