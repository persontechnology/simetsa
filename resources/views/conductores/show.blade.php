@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('conductores.show', $conductor) }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex gap-2 mb-2 mb-lg-0">
        @can('conductores.editar')
            @if($conductor->estado === 'activo')
                <form method="POST" action="{{ route('conductores.bloquear', $conductor) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-warning"
                            data-confirm data-action="bloquear"
                            data-msg="¿Bloquear al conductor {{ $conductor->codigo }}?">
                        <i class="bi bi-slash-circle me-1"></i>Bloquear
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('conductores.desbloquear', $conductor) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-success"
                            data-confirm data-action="desbloquear"
                            data-msg="¿Desbloquear al conductor {{ $conductor->codigo }}?">
                        <i class="bi bi-check-circle me-1"></i>Desbloquear
                    </button>
                </form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
<div class="row g-4">

    {{-- Datos personales --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-person-circle me-2"></i>Datos del conductor
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Código</dt>
                    <dd class="col-7"><code>{{ $conductor->codigo }}</code></dd>

                    <dt class="col-5 text-muted">Nombre</dt>
                    <dd class="col-7">{{ $conductor->user?->name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Correo</dt>
                    <dd class="col-7">{{ $conductor->user?->email ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Cédula</dt>
                    <dd class="col-7">{{ $conductor->user?->perfil?->cedula ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Celular</dt>
                    <dd class="col-7">{{ $conductor->user?->perfil?->telefono_celular ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Teléfono</dt>
                    <dd class="col-7">{{ $conductor->user?->perfil?->telefono ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Dirección</dt>
                    <dd class="col-7">{{ $conductor->user?->perfil?->direccion ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Estado</dt>
                    <dd class="col-7">
                        @if($conductor->estado === 'activo')
                            <span class="badge bg-success">Activo</span>
                        @else
                            <span class="badge bg-danger">Bloqueado</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Registrado</dt>
                    <dd class="col-7">{{ $conductor->created_at?->format('d/m/Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Vehículos y credenciales --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-car-front me-2"></i>Vehículos registrados
                <span class="badge bg-secondary ms-2">{{ $conductor->vehiculos->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Placa</th>
                            <th>Tipo</th>
                            <th>Vehículo</th>
                            <th>Año</th>
                            <th>Estado</th>
                            <th>Credencial CONADIS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conductor->vehiculos as $v)
                            <tr>
                                <td><strong>{{ $v->placa }}</strong></td>
                                <td class="text-muted">{{ $v->tipoVehiculo?->nombre }}</td>
                                <td>{{ $v->marca }} {{ $v->modelo }}</td>
                                <td>{{ $v->anio }}</td>
                                <td>
                                    @if($v->estado === 'activo')
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    @if($v->credencial)
                                        @switch($v->credencial->estado)
                                            @case('pendiente')
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                                @can('credenciales_discapacidad.aprobar')
                                                    <form method="POST" action="{{ route('credenciales-discapacidad.aprobar', $v->credencial) }}" class="d-inline ms-1">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-success py-0 px-1" title="Aprobar">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger py-0 px-1 ms-1 btn-rechazar-credencial"
                                                            data-bs-toggle="modal" data-bs-target="#modalRechazar"
                                                            data-id="{{ $v->credencial->id }}" title="Rechazar">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                @endcan
                                                @break
                                            @case('aprobada')
                                                <span class="badge bg-success">CONADIS aprobada</span>
                                                @break
                                            @case('rechazada')
                                                <span class="badge bg-danger">Rechazada</span>
                                                @break
                                            @case('vencida')
                                                <span class="badge bg-secondary">Vencida</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $v->credencial->estado }}</span>
                                        @endswitch

                                        {{-- mostrar datos de la credencial --}}
                                        <div class="small text-muted mt-1">
                                            <div><strong>N° CONADIS:</strong> {{ $v->credencial->numero_conadis }}</div>
                                            <div><strong>Beneficiario:</strong> {{ $v->credencial->nombre_beneficiario }}</div>
                                            <div><strong>Discapacidad:</strong> {{ $v->credencial->porcentaje_discapacidad }}%</div>
                                            <div><strong>Emisión:</strong> {{ $v->credencial->fecha_emision?->format('d/m/Y') }}</div>
                                            <div><strong>Vencimiento:</strong> {{ $v->credencial->fecha_vencimiento?->format('d/m/Y') }}</div>
                                        </div>

                                    @else
                                        <span class="text-muted">Sin credencial</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Este conductor no tiene vehículos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal rechazar credencial CONADIS --}}
@can('credenciales_discapacidad.aprobar')
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel">
    <div class="modal-dialog">
        <form method="POST" id="formRechazar" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title" id="modalRechazarLabel">Rechazar credencial CONADIS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="observacionesRechazo" class="form-label fw-semibold">
                    Motivo del rechazo <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="observacionesRechazo" name="observaciones"
                          rows="3" required placeholder="Describe el motivo..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Rechazar credencial</button>
            </div>
        </form>
    </div>
</div>

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-rechazar-credencial').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            document.getElementById('formRechazar').action = `/credenciales-discapacidad/${id}/rechazar`;
            document.getElementById('observacionesRechazo').value = '';
        });
    });
});
</script>
@endpush
@endcan
@endsection
