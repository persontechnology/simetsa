
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('usuarios.show', $usuario) }}
@endsection

@section('content')
<div class="container-fluid px-0">
    
    <!-- 1. Cabecera principal del Usuario (Header Hero) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-sm-row align-items-center gap-3 text-center text-sm-start">
                <!-- Avatar con Iniciales -->
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" 
                     style="width: 64px; height: 64px; min-width: 64px;">
                    {{ strtoupper(substr($usuario->name, 0, 2)) }}
                </div>
                
                <!-- Info Primaria -->
                <div class="flex-grow-1">
                    <h1 class="h3 mb-1 fw-bold text-dark">{{ $usuario->name }}</h1>
                    <p class="text-muted mb-0 d-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-2">
                        <span><i class="ph ph-envelope me-1"></i>{{ $usuario->email }}</span>
                        <span class="text-dark-50">•</span>
                        <span><i class="ph ph-identification-card me-1"></i>{{ $usuario->perfil?->cedula ?? 'Sin cédula' }}</span>
                    </p>
                </div>

                <!-- Estado Destacado -->
                <div class="mt-2 mt-sm-0">
                    @if($usuario->perfil && $usuario->perfil->activo && !$usuario->perfil->trashed())
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold border border-success-subtle">
                            <i class="ph ph-circle-wavy-check me-1"></i> Activo
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-semibold border border-secondary-subtle">
                            <i class="ph ph-minus-circle me-1"></i> Inactivo
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Bloques de Información -->
    <div class="row g-4">

        {{-- Datos de cuenta --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h2 class="h6 text-uppercase fw-bold text-muted mb-0 tracking-wider">
                        <i class="ph ph-shield-check-line me-2 text-dark fs-5 align-middle"></i>Datos de seguridad y cuenta
                    </h2>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="mt-3">
                        <div class="row py-2.5 border-bottom border-light align-items-center">
                            <span class="col-sm-4 text-muted small fw-medium">Roles asignados</span>
                            <div class="col-sm-8 d-flex flex-wrap gap-1 mt-1 mt-sm-0">
                                @forelse($usuario->roles as $rol)
                                    <span class="badge bg-light text-dark border px-2 py-1.5 fw-medium">
                                        {{ \App\Enums\RolSistema::tryFrom($rol->name)?->etiqueta() ?? $rol->name }}
                                    </span>
                                @empty
                                    <span class="text-muted small italic">Sin roles asignados</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="row py-2.5 border-bottom border-light">
                            <span class="col-sm-4 text-muted small fw-medium">Verificación de Email</span>
                            <div class="col-sm-8">
                                @if($usuario->email_verified_at)
                                    <span class="text-success small fw-medium">
                                        <i class="ph ph-check-circle me-1 align-middle"></i> Verificado ({{ $usuario->email_verified_at->format('d/m/Y H:i') }})
                                    </span>
                                @else
                                    <span class="text-danger small fw-medium">
                                        <i class="ph ph-warning-circle me-1 align-middle"></i> Correo no verificado
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="row py-2.5 mb-0">
                            <span class="col-sm-4 text-muted small fw-medium">Fecha de registro</span>
                            <span class="col-sm-8 text-dark small">{{ $usuario->created_at?->format('d/m/Y \a \l\a\s H:i') ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos personales --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h2 class="h6 text-uppercase fw-bold text-muted mb-0 tracking-wider">
                        <i class="ph ph-user-list me-2 text-dark fs-5 align-middle"></i>Información Personal
                    </h2>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    @if($usuario->perfil)
                        <div class="mt-3">
                            <div class="row py-2.5 border-bottom border-light">
                                <span class="col-sm-4 text-muted small fw-medium">Celular</span>
                                <span class="col-sm-8 text-dark small font-monospace">{{ $usuario->perfil->telefono_celular }}</span>
                            </div>

                            <div class="row py-2.5 border-bottom border-light">
                                <span class="col-sm-4 text-muted small fw-medium">Teléfono fijo</span>
                                <span class="col-sm-8 text-dark small">{{ $usuario->perfil->telefono ?? '—' }}</span>
                            </div>

                            <div class="row py-2.5 border-bottom border-light">
                                <span class="col-sm-4 text-muted small fw-medium">Dirección domiciliaria</span>
                                <span class="col-sm-8 text-dark small">{{ $usuario->perfil->direccion ?? '—' }}</span>
                            </div>

                            <div class="row py-2.5 border-bottom border-light">
                                <span class="col-sm-4 text-muted small fw-medium">Fecha de Nacimiento</span>
                                <span class="col-sm-8 text-dark small">
                                    {{ $usuario->perfil->fecha_nacimiento?->format('d/m/Y') ?? '—' }}
                                </span>
                            </div>

                            <div class="row py-2.5 border-bottom border-light">
                                <span class="col-sm-4 text-muted small fw-medium">Género</span>
                                <span class="col-sm-8 text-dark small">{{ $usuario->perfil->genero_etiqueta ?? '—' }}</span>
                            </div>

                            <div class="row py-2.5 mb-0 align-items-center">
                                <span class="col-sm-4 text-muted small fw-medium">Consentimiento LOPDP</span>
                                <div class="col-sm-8 mt-1 mt-sm-0">
                                    @if($usuario->perfil->acepta_terminos)
                                        <span class="badge bg-info-subtle text-info px-2 py-1.5 rounded fw-medium">
                                            <i class="ph ph-hand-heart me-1"></i> Aceptado el {{ $usuario->perfil->fecha_aceptacion_terminos?->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-dark px-2 py-1.5 rounded fw-medium">
                                            <i class="ph ph-hourglass me-1"></i> Pendiente de firma
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ph ph-user-circle-gear fs-1 text-muted opacity-25 mb-2"></i>
                            <p class="text-muted small mb-0">Este usuario aún no tiene un perfil personal registrado.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Botones de acción / Footer (Barra limpia) -->
    <div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
        <a href="{{ route('usuarios.index') }}" class="btn btn-light text-secondary border">
            <i class="ph ph-arrow-left me-1"></i> Volver al listado
        </a>
        @can('update', $usuario)
            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-dark px-4">
                <i class="ph ph-pencil-line me-1"></i> Editar información
            </a>
        @endcan
    </div>
</div>
@endsection