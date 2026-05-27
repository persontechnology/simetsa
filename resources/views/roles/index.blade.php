{{-- resources/views/roles/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('roles.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-3 mb-lg-0">
        @can('roles.crear')
            <a href="{{ route('roles.create') }}" class="btn btn-link text-dark px-0 py-2 d-flex align-items-center text-decoration-none">
                <i class="bi bi-plus-lg me-2"></i> Nuevo rol
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="card border-0 shadow-sm rounded-4">
       

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="small text-uppercase text-muted border-bottom">
                    <tr>
                        <th class="ps-4 fw-semibold">Rol</th>
                        <th class="fw-semibold">Identificador</th>
                        <th class="fw-semibold">Permisos</th>
                        <th class="fw-semibold">Usuarios</th>
                        <th class="text-end pe-4 fw-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $r)
                        <tr class="border-bottom">
                            <td class="ps-4 text-nowrap">
                                <span class="fw-semibold">
                                    {{ method_exists($r, 'etiqueta') ? $r->etiqueta() : ucwords(str_replace('_', ' ', $r->name)) }}
                                </span>
                                @if(isset($r->description))
                                    <span class="text-muted small ms-2">{{ $r->description }}</span>
                                @endif
                            </td>

                            <td>
                                <code class="small bg-light px-2 py-0 rounded text-secondary">{{ $r->name }}</code>
                            </td>

                            <td class="text-nowrap">
                                <i class="bi bi-shield-lock me-1"></i>
                                {{ $r->permissions_count ?? $r->permissions->count() }}
                            </td>

                            <td class="text-nowrap">
                                <i class="bi bi-people me-1"></i>
                                {{ $r->users_count ?? $r->users->count() }}
                            </td>

                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 border-0"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            aria-label="Acciones del rol">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-1">
                                        @can('roles.editar')
                                            <a href="{{ route('roles.edit', $r) }}" class="dropdown-item py-1 px-3">
                                                <i class="bi bi-pencil me-2 text-muted"></i> Editar
                                            </a>
                                        @endcan
                                        @can('roles.eliminar')
                                            @if(!in_array($r->name, ['admin', 'super_admin']))
                                                <hr class="dropdown-divider my-1">
                                                <a href="#" class="dropdown-item py-1 px-3 text-danger"
                                                   onclick="event.preventDefault(); if(confirm('¿Eliminar el rol «{{ $r->name }}»?')) { document.getElementById('delete-form-{{ $r->id }}').submit(); }">
                                                    <i class="bi bi-trash me-2"></i> Eliminar
                                                </a>
                                                <form id="delete-form-{{ $r->id }}" method="POST" action="{{ route('roles.destroy', $r) }}" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-shield-slash d-block mb-1 opacity-50"></i>
                                No hay roles configurados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($roles, 'hasPages') && $roles->hasPages())
            <div class="card-footer bg-white border-top-0 pt-2 pb-2 px-4">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
@endsection