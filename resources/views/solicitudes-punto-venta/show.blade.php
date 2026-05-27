@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('solicitudes-punto-venta.show', $solicitud) }}
@endsection


@section('content')
@if($solicitud->estado === \App\Models\SolicitudPuntoVenta::ESTADO_RECHAZADA)
    <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i> <strong>Rechazada.</strong> {{ $solicitud->motivo_rechazo }}</div>
@elseif($solicitud->estado === \App\Models\SolicitudPuntoVenta::ESTADO_CONTRATO)
    <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-file-earmark-text me-1"></i> Documentación aprobada. Falta firmar el contrato (Procuraduría Síndica) y activar el punto de venta.</span>
    @can('puntos_venta.crear')
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalActivar"><i class="bi bi-shop-window me-1"></i> Firmar contrato y activar</button>
    @endcan
</div>
@elseif($solicitud->estado === \App\Models\SolicitudPuntoVenta::ESTADO_ACTIVA)
     <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i> Punto de venta <strong>activo</strong>.
        @if($solicitud->puntoVenta)
            <a href="{{ route('puntos-venta.show', $solicitud->puntoVenta) }}" class="alert-link">Ver {{ $solicitud->puntoVenta->codigo }}</a>.
        @endif
    </div>
@endif



<div class="row g-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">
                <i class="bi bi-shop text-simetsa me-1"></i> <code>{{ $solicitud->codigo }}</code>
                <span class="badge bg-{{ $solicitud->estado_color }} ms-2">{{ $solicitud->estado_label }}</span>
            </h1>
            <a href="{{ route('solicitudes-punto-venta.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3">Datos</h2>
            <dl class="row mb-0 small">
                <dt class="col-5">Solicitante</dt><dd class="col-7">{{ $solicitud->nombre_completo }}</dd>
                <dt class="col-5">Cédula</dt><dd class="col-7">{{ $solicitud->cedula }}</dd>
                <dt class="col-5">Correo</dt><dd class="col-7">{{ $solicitud->email }}</dd>
                <dt class="col-5">Local</dt><dd class="col-7">{{ $solicitud->nombre_comercial }}</dd>
                <dt class="col-5">RUC</dt><dd class="col-7">{{ $solicitud->ruc ?? '—' }}</dd>
                <dt class="col-5">Dirección local</dt><dd class="col-7">{{ $solicitud->direccion_local }}</dd>
                <dt class="col-5">Referencia</dt><dd class="col-7">{{ $solicitud->referencia_ubicacion ?? '—' }}</dd>
            </dl>
            @can('puntos_venta.editar')
                @if($solicitud->enEtapaDocumentacion())
                    <hr><a href="{{ route('solicitudes-punto-venta.edit', $solicitud) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Editar datos</a>
                @endif
            @endcan
        </div></div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3"><i class="bi bi-paperclip me-1"></i> Documentación requerida (Art. 31)</h2>

            <ul class="list-group list-group-flush mb-3">
                @foreach($tiposDocumento as $tipo => $label)
                    @php $doc = $solicitud->documentos->firstWhere('tipo', $tipo); $req = in_array($tipo, $requeridos, true); @endphp
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span>
                            @if($doc && $doc->validado) <i class="bi bi-check-circle-fill text-success"></i>
                            @elseif($doc) <i class="bi bi-clock text-warning"></i>
                            @else <i class="bi bi-circle text-muted"></i> @endif
                            {{ $label }} @if($req)<span class="text-danger">*</span>@endif
                        </span>
                        <span class="d-flex gap-1">
                            @if($doc)
                                <a href="{{ route('documentos-punto-venta.descargar', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
                                @can('puntos_venta.editar')
                                    <form method="POST" action="{{ route('documentos-punto-venta.validar', $doc) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-{{ $doc->validado ? 'warning' : 'success' }}">
                                            <i class="bi bi-{{ $doc->validado ? 'x' : 'check' }}-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('documentos-punto-venta.destroy', $doc) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar documento?')"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endcan
                            @else
                                <span class="text-muted small">Pendiente</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>

            @can('puntos_venta.editar')
            @if($solicitud->enEtapaDocumentacion())
                <form method="POST" action="{{ route('documentos-punto-venta.store', $solicitud) }}" enctype="multipart/form-data" class="row g-2 align-items-end border-top pt-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Tipo</label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            @foreach($tiposDocumento as $tipo => $label)<option value="{{ $tipo }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Archivo (PDF/JPG/PNG)</label>
                        <input type="file" name="archivo" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="col-md-2"><button class="btn btn-sm btn-simetsa w-100"><i class="bi bi-upload"></i></button></div>
                </form>

                <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">* documentos obligatorios. Aprobá cuando estén todos validados.</small>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRechazo">Rechazar</button>
                        <form method="POST" action="{{ route('solicitudes-punto-venta.aprobar-documentacion', $solicitud) }}">
                            @csrf
                            <button class="btn btn-sm btn-success" @disabled(! $completa)><i class="bi bi-check2-all me-1"></i> Aprobar documentación</button>
                        </form>
                    </div>
                </div>
            @endif
            @endcan
        </div></div>
    </div>
</div>

@can('puntos_venta.editar')
<div class="modal fade" id="modalRechazo" tabindex="-1"><div class="modal-dialog">
    <form method="POST" action="{{ route('solicitudes-punto-venta.rechazar', $solicitud) }}" class="modal-content">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Rechazar solicitud</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <label class="form-label small">Motivo del rechazo</label>
            <textarea name="motivo_rechazo" rows="3" class="form-control" maxlength="500" required></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger">Rechazar</button></div>
    </form>
</div></div>
@endcan


@can('puntos_venta.crear')
    @if($solicitud->estado === \App\Models\SolicitudPuntoVenta::ESTADO_CONTRATO)
    <div class="modal fade" id="modalActivar" tabindex="-1"><div class="modal-dialog">
        <form method="POST" action="{{ route('puntos-venta.activar', $solicitud) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Firmar contrato y activar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted">Se creará la cuenta del punto de venta (rol punto de venta); si el correo ya existe, se vincula esa cuenta. Descuento del 10% (Art. 31 / 21).</p>
                <div class="mb-2"><label class="form-label small">Correo del responsable</label><input type="email" name="email" class="form-control" value="{{ old('email', $solicitud->email) }}" required></div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small">N.º de contrato</label><input type="text" name="numero_contrato" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small">Elaborado por</label><input type="text" name="elaborado_por" class="form-control" value="Procuraduría Síndica"></div>
                    <div class="col-md-4"><label class="form-label small">Fecha de firma</label><input type="date" name="fecha_firma" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-4"><label class="form-label small">Vigente desde</label><input type="date" name="fecha_inicio" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-4"><label class="form-label small">Vigente hasta</label><input type="date" name="fecha_fin" class="form-control"></div>
                </div>
                <div class="mt-2"><label class="form-label small">Observaciones</label><textarea name="observaciones" rows="2" class="form-control" maxlength="500"></textarea></div>
                @if($solicitud->tieneUbicacion())
                    <p class="small text-muted mt-2 mb-0"><i class="bi bi-geo-alt me-1"></i> Se validará que no exista otro punto de venta activo a menos de 3 cuadras (Art. 31).</p>
                @endif
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-success">Activar punto de venta</button></div>
        </form>
    </div></div>
    @endif
@endcan


@endsection