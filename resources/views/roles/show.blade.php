{{-- resources/views/roles/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('roles.show', $rol) }}
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h1 class="h3 mb-0 fw-bold text-dark">
                {{ \App\Enums\RolSistema::tryFrom($rol->name)?->etiqueta() ?? ucwords(str_replace('_', ' ', $rol->name)) }}
            </h1>
            <span class="badge bg-dark-subtle text-dark font-monospace px-2 py-1 fs-7">
                {{ $rol->name }}
            </span>
        </div>
        <p class="text-muted mb-0 small mt-1">
            <i class="ph ph-shield-check me-1"></i> Configuración de accesos y seguridad del sistema
        </p>
    </div>

    <div class="row g-4">
        
        {{-- Permisos asignados agrupados por módulo --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-1">
                    <h2 class="h6 text-uppercase fw-bold text-muted mb-0 tracking-wider">
                        <i class="ph ph-keyhole me-2 text-dark fs-5 align-middle"></i>
                        Permisos Habilitados ({{ $rol->permissions->count() }})
                    </h2>
                </div>
                
                <div class="card-body p-4">
                    @php
                        $permisosAsignados = $rol->permissions->pluck('name')->toArray();
                        $tienePermisos = false;
                    @endphp

                    <div class="d-flex flex-column gap-4">
                        @foreach($catalogoPermisos as $modulo => $entidades)
                            @php
                                $permisosModulo = [];
                                foreach ($entidades as $entidad => $acciones) {
                                    foreach ($acciones as $accion) {
                                        $p = "{$entidad}.{$accion}";
                                        if (in_array($p, $permisosAsignados, true)) {
                                            $permisosModulo[] = ['completo' => $p, 'entidad' => $entidad, 'accion' => $accion];
                                        }
                                    }
                                }
                            @endphp

                            @if(count($permisosModulo) > 0)
                                @php $tienePermisos = true; @endphp
                                <div class="pb-3 border-bottom border-light last-border-0">
                                    <h6 class="fw-bold text-dark mb-2.5 d-flex align-items-center fs-7 text-uppercase tracking-wide">
                                        <i class="ph ph-folder-open me-2 text-muted"></i>
                                        {{ ucfirst(str_replace('_', ' ', $modulo)) }}
                                    </h6>
                                    
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($permisosModulo as $item)
                                            <span class="badge bg-light text-dark border border-light-subtle d-inline-flex align-items-center px-2.5 py-2 rounded font-monospace fs-7" title="{{ $item['completo'] }}">
                                                <span class="bg-success rounded-circle me-1.5" style="width: 6px; height: 6px;"></span>
                                                <span class="text-muted">{{ $item['entidad'] }}.</span><span class="fw-bold text-dark">{{ $item['accion'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if(!$tienePermisos)
                        <div class="text-center py-5">
                            <i class="ph ph-lock-keyhole fs-1 text-muted opacity-25 mb-2"></i>
                            <p class="text-muted small mb-0">Este rol no cuenta con ningún permiso asignado en la plataforma.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Usuarios con este rol --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-1">
                    <h2 class="h6 text-uppercase fw-bold text-muted mb-0 tracking-wider">
                        <i class="ph ph-users-three me-2 text-dark fs-5 align-middle"></i>
                        Usuarios Asignados ({{ $rol->users->count() }})
                    </h2>
                </div>
                
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="mt-2 d-flex flex-column gap-1">
                        @forelse($rol->users->take(50) as $u)
                            <div class="d-flex justify-content-between align-items-center py-2.5 border-bottom border-light-subtle">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="bg-light border text-secondary rounded-circle d-flex align-items-center justify-content-center small fw-bold" 
                                         style="width: 34px; height: 34px; min-width: 34px; font-size: 0.75rem;">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold text-dark small lh-sm">{{ $u->name }}</span>
                                        <span class="text-muted fs-7 lh-sm">{{ $u->email }}</span>
                                    </div>
                                </div>
                                
                                @can('view', $u)
                                    <a href="{{ route('usuarios.show', $u) }}" 
                                       class="btn btn-sm btn-icon btn-ghost-dark rounded-circle" 
                                       title="Ver ficha del usuario"
                                       style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                        <i class="ph ph-arrow-square-out fs-5"></i>
                                    </a>
                                @endcan
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="ph ph-users fs-1 text-muted opacity-25 mb-2"></i>
                                <p class="text-muted small mb-0">Ningún usuario tiene este rol asignado actualmente.</p>
                            </div>
                        @endforelse
                    </div>

                    @if($rol->users->count() > 50)
                        <div class="p-2.5 bg-light rounded border border-light-subtle mt-3 text-center">
                            <span class="small text-muted font-medium">
                                <i class="ph ph-info me-1"></i> Mostrando los primeros 50 usuarios de la lista.
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
    </div>

    <div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
        <a href="{{ route('roles.index') }}" class="btn btn-light text-secondary border">
            <i class="ph ph-arrow-left me-1"></i> Volver al listado
        </a>
        @can('update', $rol)
            <a href="{{ route('roles.edit', $rol) }}" class="btn btn-dark px-4">
                <i class="ph ph-pencil-line me-1"></i> Editar Rol
            </a>
        @endcan
    </div>
</div>
@endsection