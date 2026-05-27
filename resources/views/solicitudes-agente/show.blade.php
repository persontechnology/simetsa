{{-- resources/views/solicitudes-agente/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('solicitudes-agente.show', $solicitud) }}
@endsection

@section('encabezado')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">
            <i class="bi bi-person-badge text-simetsa me-1"></i>
            Solicitud <code>{{ $solicitud->codigo }}</code>
            <span class="badge bg-{{ $solicitud->estado_color }} ms-2">{{ $solicitud->estado_label }}</span>
        </h1>
        <a href="{{ route('solicitudes-agente.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
@endsection

@section('content')
<div class="row g-4">
    {{-- Datos del postulante --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3">Postulante</h2>
            <dl class="row mb-0 small">
                <dt class="col-5">Nombre</dt><dd class="col-7">{{ $solicitud->nombre_completo }}</dd>
                <dt class="col-5">Cédula</dt><dd class="col-7">{{ $solicitud->cedula }}</dd>
                <dt class="col-5">Edad</dt><dd class="col-7">{{ $solicitud->edad }} años</dd>
                <dt class="col-5">Educación</dt><dd class="col-7">{{ \App\Models\SolicitudAgente::listadoNivelesEducacion()[$solicitud->nivel_educacion] ?? '—' }}</dd>
                <dt class="col-5">Celular</dt><dd class="col-7">{{ $solicitud->telefono_celular ?? '—' }}</dd>
                <dt class="col-5">Correo</dt><dd class="col-7">{{ $solicitud->email ?? '—' }}</dd>
            </dl>
            @if($solicitud->estado === 'rechazada' && $solicitud->motivo_rechazo)
                <div class="alert alert-danger mt-3 small mb-0"><strong>Motivo de rechazo:</strong> {{ $solicitud->motivo_rechazo }}</div>
            @endif
        </div></div>
    </div>

    {{-- Documentos (Etapa 1) --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3">Documentación requerida (Art. 33-34)</h2>

            <table class="table table-sm align-middle">
                <thead class="table-light"><tr><th>Documento</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                    @foreach($tiposDocumento as $tipoClave => $tipoLabel)
                        @php $doc = $solicitud->documentos->firstWhere('tipo', $tipoClave); @endphp
                        <tr>
                            <td class="small">
                                {{ $tipoLabel }}
                                @if(in_array($tipoClave, $documentosRequeridos))<span class="text-danger">*</span>@endif
                            </td>
                            <td>
                                @if(!$doc)
                                    <span class="badge bg-secondary">No cargado</span>
                                @elseif($doc->validado)
                                    <span class="badge bg-success">Validado</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($doc)
                                    <a href="{{ route('documentos-agente.descargar', $doc) }}" class="btn btn-sm btn-outline-secondary" title="Descargar"><i class="bi bi-download"></i></a>
                                    @can('agentes.editar')
                                        @unless($doc->validado)
                                            <form method="POST" action="{{ route('documentos-agente.validar', $doc) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm btn-outline-success" title="Validar"><i class="bi bi-check2"></i></button>
                                            </form>
                                        @endunless
                                        <form method="POST" action="{{ route('documentos-agente.destroy', $doc) }}" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar este documento?')"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Cargar documento --}}
            @can('agentes.editar')
            <form method="POST" action="{{ route('documentos-agente.store', $solicitud) }}" enctype="multipart/form-data" class="row g-2 align-items-end border-top pt-3">
                @csrf
                <div class="col-md-5">
                    <label for="tipo" class="form-label small mb-1">Tipo de documento</label>
                    <select name="tipo" id="tipo" class="form-select form-select-sm @error('tipo') is-invalid @enderror" required>
                        @foreach($tiposDocumento as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
                    </select>
                    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label for="archivo" class="form-label small mb-1">Archivo (PDF/imagen, máx 5 MB)</label>
                    <input type="file" name="archivo" id="archivo" class="form-control form-control-sm @error('archivo') is-invalid @enderror" required>
                    @error('archivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-simetsa w-100"><i class="bi bi-upload"></i> Cargar</button>
                </div>
            </form>
            @endcan
        </div></div>

        {{-- Acciones de etapa 1 --}}
        @can('agentes.editar')
        @if($solicitud->enEtapaDocumentacion())
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body d-flex gap-2 align-items-center flex-wrap">
                <form method="POST" action="{{ route('solicitudes-agente.aprobar-documentacion', $solicitud) }}">
                    @csrf
                    <button class="btn btn-success" @disabled(!$documentacionCompleta)>
                        <i class="bi bi-check2-all me-1"></i> Aprobar documentación → Capacitación
                    </button>
                </form>
                @unless($documentacionCompleta)
                    <span class="small text-muted">Faltan documentos requeridos (*) validados.</span>
                @endunless

                <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#modalRechazo">
                    <i class="bi bi-x-circle me-1"></i> Rechazar
                </button>
            </div>
        </div>

        {{-- Modal de rechazo --}}
        <div class="modal fade" id="modalRechazo" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('solicitudes-agente.rechazar', $solicitud) }}" class="modal-content">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Rechazar solicitud</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <label for="motivo_rechazo" class="form-label">Motivo *</label>
                        <textarea name="motivo_rechazo" id="motivo_rechazo" rows="3" class="form-control" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger">Rechazar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endcan

        {{-- Etapa 3: Autorización (Art. 36) --}}
        @if($solicitud->estado === \App\Models\SolicitudAgente::ESTADO_AUTORIZACION)
            @can('agentes.crear')
            <div class="card border-0 shadow-sm mt-3 border-start border-4 border-warning"><div class="card-body">
                <h2 class="h6 text-simetsa mb-2"><i class="bi bi-patch-check me-1"></i> Etapa 3 — Autorización</h2>
                <p class="small text-muted mb-3">El postulante aprobó la capacitación. La Dirección de Seguridad Ciudadana puede autorizarlo previo informe favorable del Comisario y carta compromiso (Art. 36).</p>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalAutorizar">
                    <i class="bi bi-check2-circle me-1"></i> Autorizar como agente
                </button>
            </div></div>

            <div class="modal fade" id="modalAutorizar" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('agentes-parqueo.autorizar', $solicitud) }}" class="modal-content">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Autorizar agente</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo (cuenta del agente) *</label>
                                <input type="email" name="email" id="email" class="form-control"
                                    value="{{ old('email', $solicitud->email) }}" required>
                                <small class="form-text text-muted">Si el correo ya tiene cuenta, se vinculará como agente; si no, se creará una cuenta nueva.</small>
                            </div>
                            <div class="mb-3">
                                <label for="numero_credencial" class="form-label">N.º de credencial *</label>
                                <input type="text" name="numero_credencial" id="numero_credencial" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="numero_oficio_comisario" class="form-label">N.º oficio informe del Comisario</label>
                                <input type="text" name="numero_oficio_comisario" id="numero_oficio_comisario" class="form-control">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="carta_compromiso_firmada" id="carta" value="1" required>
                                <label class="form-check-label small" for="carta">Confirmo la firma de la carta compromiso (Art. 36).</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button class="btn btn-warning">Autorizar</button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan
        @elseif($solicitud->estado === \App\Models\SolicitudAgente::ESTADO_AUTORIZADA && $solicitud->agente)
            <div class="alert alert-success mt-3">
                <i class="bi bi-check2-circle me-1"></i>
                Solicitud autorizada. Agente generado:
                <a href="{{ route('agentes-parqueo.show', $solicitud->agente) }}" class="alert-link">{{ $solicitud->agente->codigo }}</a>.
            </div>
        @endif
    </div>
</div>
@endsection