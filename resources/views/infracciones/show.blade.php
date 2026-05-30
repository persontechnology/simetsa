{{-- resources/views/infracciones/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('infracciones.show', $infraccion) }}
@endsection

@section('breadcrumb_elements')
    @can('infracciones.registrar')
        @if($infraccion->estado->esAnulable())
        <button type="button" class="btn btn-sm btn-danger"
                data-bs-toggle="modal" data-bs-target="#modalAnular">
            <i class="bi bi-x-circle me-1"></i>Anular infracción
        </button>
        @endif
    @endcan
@endsection

@section('content')
<div class="row g-4">

    {{-- Datos de la infracción --}}
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-exclamation-triangle me-2"></i>Datos de la infracción
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Placa</dt>
                    <dd class="col-7"><strong>{{ $infraccion->placa }}</strong></dd>

                    <dt class="col-5 text-muted">Tipo</dt>
                    <dd class="col-7">{{ $infraccion->tipo_infraccion->etiqueta() }}</dd>

                    <dt class="col-5 text-muted">Estado</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $infraccion->estado->color() }}">
                            {{ $infraccion->estado->etiqueta() }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted">Multa</dt>
                    <dd class="col-7">
                        <strong>${{ number_format($infraccion->monto_multa, 2) }}</strong>
                        <small class="text-muted">(SBU ${{ number_format($infraccion->sbu_vigente, 2) }})</small>
                    </dd>

                    @if($infraccion->tipo_infraccion->value === 'tiempo_excedido' && $infraccion->minutos_excedidos)
                    <dt class="col-5 text-muted">Min. excedidos</dt>
                    <dd class="col-7">{{ $infraccion->minutos_excedidos }} min</dd>
                    @endif

                    <dt class="col-5 text-muted">Zona</dt>
                    <dd class="col-7">{{ $infraccion->zona?->nombre ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Calle</dt>
                    <dd class="col-7">{{ $infraccion->calle?->nombre ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Fecha</dt>
                    <dd class="col-7">{{ $infraccion->created_at?->format('d/m/Y H:i') }}</dd>

                    @if($infraccion->descripcion)
                    <dt class="col-5 text-muted">Descripción</dt>
                    <dd class="col-7">{{ $infraccion->descripcion }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Agente y conductor --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-person-badge me-2"></i>Agente de parqueo
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Código</dt>
                    <dd class="col-7">{{ $infraccion->agente?->codigo ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Nombre</dt>
                    <dd class="col-7">{{ $infraccion->agente?->user?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($infraccion->conductor)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-person me-2"></i>Conductor
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Nombre</dt>
                    <dd class="col-7">{{ $infraccion->conductor->user?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
        @endif
    </div>

    {{-- Inmovilización --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-lock me-2"></i>Candado inmovilizador
            </div>
            <div class="card-body">
                @if($infraccion->inmovilizacion)
                    <dl class="row mb-0 small">
                        <dt class="col-6 text-muted">Estado</dt>
                        <dd class="col-6">
                            <span class="badge bg-{{ $infraccion->inmovilizacion->estado->color() }}">
                                {{ $infraccion->inmovilizacion->estado->etiqueta() }}
                            </span>
                        </dd>
                        <dt class="col-6 text-muted">Inmovilizado</dt>
                        <dd class="col-6">{{ $infraccion->inmovilizacion->inmovilizada_en?->format('d/m/Y H:i') }}</dd>
                        @if($infraccion->inmovilizacion->liberada_en)
                        <dt class="col-6 text-muted">Liberado</dt>
                        <dd class="col-6">{{ $infraccion->inmovilizacion->liberada_en->format('d/m/Y H:i') }}</dd>
                        @endif
                        <dt class="col-6 text-muted">Agente</dt>
                        <dd class="col-6">{{ $infraccion->inmovilizacion->agente?->codigo ?? '—' }}</dd>
                        @if($infraccion->inmovilizacion->notas)
                        <dt class="col-6 text-muted">Notas</dt>
                        <dd class="col-6">{{ $infraccion->inmovilizacion->notas }}</dd>
                        @endif
                    </dl>
                @else
                    <p class="text-muted small mb-0">Sin inmovilización registrada.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Transacciones de pago --}}
    @if($infraccion->transacciones->isNotEmpty())
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-credit-card me-2"></i>Transacciones de pago
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th class="text-end">Monto</th>
                            <th>Estado</th>
                            <th>Referencia externa</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($infraccion->transacciones as $tx)
                        <tr>
                            <td><code>{{ $tx->id }}</code></td>
                            <td>{{ $tx->proveedor }}</td>
                            <td class="text-end">${{ number_format($tx->monto, 2) }}</td>
                            <td><span class="badge bg-secondary">{{ $tx->estado->value }}</span></td>
                            <td><code>{{ $tx->external_reference ?? '—' }}</code></td>
                            <td>{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Anulación --}}
    @if($infraccion->estado->value === 'anulada')
    <div class="col-12">
        <div class="alert alert-secondary small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Anulada el {{ $infraccion->anulada_en?->format('d/m/Y H:i') }}
            por <strong>{{ $infraccion->anuladaPor?->name ?? '—' }}</strong>:
            {{ $infraccion->motivo_anulacion }}
        </div>
    </div>
    @endif

</div><!-- /row -->

{{-- Modal de anulación --}}
@can('infracciones.registrar')
@if($infraccion->estado->esAnulable())
<div class="modal fade" id="modalAnular" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('infracciones.anular', $infraccion) }}" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-x-circle me-1"></i>Anular infracción #{{ $infraccion->id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    Esta acción es irreversible. La infracción quedará anulada. Si existe un candado activo, también se anulará.
                </div>
                <div class="mb-3">
                    <label for="motivoAnulacion" class="form-label">Motivo <span class="text-danger">*</span></label>
                    <textarea id="motivoAnulacion" name="motivo"
                              class="form-control @error('motivo') is-invalid @enderror"
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
