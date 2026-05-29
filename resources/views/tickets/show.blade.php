{{-- resources/views/tickets/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('tickets.show', $ticket) }}
@endsection

@section('breadcrumb_elements')
    @can('tickets.anular')
        @if($ticket->estado->esAnulable())
        <button type="button" class="btn btn-sm btn-danger"
                data-bs-toggle="modal" data-bs-target="#modalAnular">
            <i class="bi bi-x-circle me-1"></i>Anular ticket
        </button>
        @endif
    @endcan
@endsection

@section('content')
<div class="row g-4">

    {{-- Datos del ticket --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-ticket-perforated me-2"></i>Datos del ticket
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Código</dt>
                    <dd class="col-7"><code>{{ $ticket->codigo }}</code></dd>

                    <dt class="col-5 text-muted">Estado</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $ticket->estado->color() }}">
                            {{ $ticket->estado->etiqueta() }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted">Zona</dt>
                    <dd class="col-7">{{ $ticket->zona?->nombre ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Calle</dt>
                    <dd class="col-7">{{ $ticket->calle?->nombre ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Horas compradas</dt>
                    <dd class="col-7">{{ $ticket->horas_compradas }} hora(s)</dd>

                    <dt class="col-5 text-muted">Monto</dt>
                    <dd class="col-7">${{ number_format($ticket->monto, 2) }}</dd>

                    <dt class="col-5 text-muted">Método de pago</dt>
                    <dd class="col-7">{{ $ticket->metodo_pago->etiqueta() }}</dd>

                    <dt class="col-5 text-muted">Exonerado</dt>
                    <dd class="col-7">
                        @if($ticket->es_exonerado)
                            <span class="badge bg-info">
                                Sí — {{ $ticket->tipo_exoneracion === 'conadis' ? 'CONADIS (Art. 26)' : 'Institucional (Art. 27)' }}
                            </span>
                        @else
                            <span class="text-muted">No</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Comprado en</dt>
                    <dd class="col-7">{{ $ticket->comprado_en?->format('d/m/Y H:i:s') }}</dd>

                    <dt class="col-5 text-muted">Expira en</dt>
                    <dd class="col-7">{{ $ticket->expira_en?->format('d/m/Y H:i:s') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Datos del conductor y vehículo --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-person me-2"></i>Conductor
            </div>
            <div class="card-body small">
                @php $conductor = $ticket->conductor; $perfil = $conductor?->user?->perfil; @endphp
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Código</dt>
                    <dd class="col-7">{{ $conductor?->codigo ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Nombre</dt>
                    <dd class="col-7">{{ $conductor?->user?->name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Cédula</dt>
                    <dd class="col-7">{{ $perfil?->cedula ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Celular</dt>
                    <dd class="col-7">{{ $perfil?->telefono_celular ?? '—' }}</dd>
                </dl>
                @if($conductor)
                <a href="{{ route('conductores.show', $conductor) }}" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Ver perfil
                </a>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-car-front me-2"></i>Vehículo
            </div>
            <div class="card-body small">
                @php $vehiculo = $ticket->vehiculo; @endphp
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Placa</dt>
                    <dd class="col-7"><strong>{{ $vehiculo?->placa ?? '—' }}</strong></dd>

                    <dt class="col-5 text-muted">Tipo</dt>
                    <dd class="col-7">{{ $vehiculo?->tipoVehiculo?->nombre ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Marca / Modelo</dt>
                    <dd class="col-7">{{ $vehiculo ? $vehiculo->marca . ' ' . $vehiculo->modelo : '—' }}</dd>

                    <dt class="col-5 text-muted">Color</dt>
                    <dd class="col-7">{{ $vehiculo?->color ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Sesión de parqueo --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-geo-alt me-2"></i>Sesión de parqueo
            </div>
            <div class="card-body small">
                @if($ticket->sesion)
                    @php $sesion = $ticket->sesion; @endphp
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted">Estado</dt>
                        <dd class="col-6">
                            <span class="badge bg-{{ $sesion->estado->color() }}">
                                {{ $sesion->estado->etiqueta() }}
                            </span>
                        </dd>

                        <dt class="col-6 text-muted">Agente</dt>
                        <dd class="col-6">{{ $sesion->agente?->codigo ?? '—' }}</dd>

                        <dt class="col-6 text-muted">Plaza</dt>
                        <dd class="col-6">{{ $sesion->plaza?->codigo ?? '—' }}</dd>

                        <dt class="col-6 text-muted">Inicio</dt>
                        <dd class="col-6">{{ $sesion->inicio_at?->format('H:i:s') }}</dd>

                        <dt class="col-6 text-muted">Fin programado</dt>
                        <dd class="col-6">{{ $sesion->fin_programado_at?->format('H:i:s') }}</dd>
                    </dl>
                @else
                    <p class="text-muted mb-0">Sin sesión iniciada.</p>
                @endif
            </div>
        </div>

        {{-- Cancelación / Anulación --}}
        @if($ticket->cancelacion)
        <div class="card border-0 shadow-sm border-danger-subtle">
            <div class="card-header bg-transparent fw-semibold text-danger">
                <i class="bi bi-x-octagon me-2"></i>{{ $ticket->cancelacion->tipo->etiqueta() }}
            </div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Por</dt>
                    <dd class="col-7">{{ $ticket->cancelacion->canceladoPorUsuario?->name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Fecha</dt>
                    <dd class="col-7">{{ $ticket->cancelacion->cancelado_en?->format('d/m/Y H:i') }}</dd>

                    <dt class="col-5 text-muted">Motivo</dt>
                    <dd class="col-7">{{ $ticket->cancelacion->motivo }}</dd>
                </dl>
            </div>
        </div>
        @endif
    </div>

</div>

{{-- Modal de anulación --}}
@can('tickets.anular')
@if($ticket->estado->esAnulable())
<div class="modal fade" id="modalAnular" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('tickets.anular', $ticket) }}" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-x-circle me-1"></i>Anular ticket {{ $ticket->codigo }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    Esta acción es irreversible. El ticket quedará anulado y se registrará el motivo y el usuario responsable.
                </div>
                <div class="mb-3">
                    <label for="motivoAnulacion" class="form-label">Motivo <span class="text-danger">*</span></label>
                    <textarea id="motivoAnulacion" name="motivo" class="form-control @error('motivo') is-invalid @enderror"
                              rows="3" minlength="5" maxlength="500" required
                              placeholder="Ingrese el motivo de la anulación...">{{ old('motivo') }}</textarea>
                    @error('motivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-circle me-1"></i>Confirmar anulación
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

@endsection
