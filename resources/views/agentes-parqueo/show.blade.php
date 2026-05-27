{{-- resources/views/agentes-parqueo/show.blade.php --}}
@extends('layouts.app')
@section('titulo', 'Agente ' . $agente->codigo)


   


@section('content')
@php $terminado = $agente->estado === \App\Models\AgenteParqueo::ESTADO_TERMINADO; @endphp

@if(session('password_temporal'))
    <div class="alert alert-warning">
        <i class="bi bi-key me-1"></i>
        <strong>Contraseña temporal del agente:</strong> <code>{{ session('password_temporal') }}</code>.
        Comunicásela al agente; deberá cambiarla al ingresar. (Se muestra una sola vez.)
    </div>
@endif

<div class="row g-4">
    {{-- Datos del agente --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="card-header">
                 <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h3 mb-0">
                        <i class="bi bi-person-vcard text-simetsa me-1"></i> Agente <code>{{ $agente->codigo }}</code>
                        <span class="badge bg-{{ $agente->estado_color }} ms-2">{{ $agente->estado_label }}</span>
                    </h1>
                    <a href="{{ route('agentes-parqueo.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            </div>
            <h2 class="h6 text-simetsa mb-3">Datos del agente</h2>
            <dl class="row mb-0 small">
                <dt class="col-5">Nombre</dt><dd class="col-7">{{ $agente->nombre_completo }}</dd>
                <dt class="col-5">Correo</dt><dd class="col-7">{{ $agente->user?->email ?? '—' }}</dd>
                <dt class="col-5">N.º credencial</dt><dd class="col-7">{{ $agente->numero_credencial ?? '—' }}</dd>
                <dt class="col-5">Oficio Comisario</dt><dd class="col-7">{{ $agente->numero_oficio_comisario ?? '—' }}</dd>
                <dt class="col-5">Carta compromiso</dt><dd class="col-7">{{ $agente->carta_compromiso_firmada ? 'Firmada' : 'Pendiente' }}</dd>
                <dt class="col-5">Autorizado</dt><dd class="col-7">{{ $agente->fecha_autorizacion?->format('d/m/Y') ?? '—' }}</dd>
                @if($agente->solicitud)
                    <dt class="col-5">Solicitud</dt>
                    <dd class="col-7"><a href="{{ route('solicitudes-agente.show', $agente->solicitud) }}">{{ $agente->solicitud->codigo }}</a></dd>
                @endif
            </dl>

            @can('agentes.editar')
            <hr>
            @if($terminado)
                <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i> Agente terminado. Se revierte eliminando una amonestación.</p>
            @else
                <form method="POST" action="{{ route('agentes-parqueo.estado', $agente) }}" class="d-flex gap-2 align-items-end">
                    @csrf @method('PATCH')
                    <div class="flex-grow-1">
                        <label for="estado" class="form-label small mb-1">Estado</label>
                        <select name="estado" id="estado" class="form-select form-select-sm">
                            <option value="activo" @selected($agente->estado === 'activo')>Activo</option>
                            <option value="suspendido" @selected($agente->estado === 'suspendido')>Suspendido</option>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-simetsa">Actualizar</button>
                </form>
            @endif
            @endcan
        </div></div>
    </div>

    {{-- Expediente --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h6 text-simetsa mb-3"><i class="bi bi-folder2-open me-1"></i> Expediente personal (Art. 40)</h2>
            @can('agentes.editar')
            <form method="POST" action="{{ route('agentes-parqueo.expediente', $agente) }}">
                @csrf @method('PATCH')
                <label for="observaciones" class="form-label small">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="4" class="form-control">{{ old('observaciones', $agente->expediente?->observaciones) }}</textarea>
                <div class="text-end mt-2"><button class="btn btn-sm btn-simetsa">Guardar expediente</button></div>
            </form>
            @else
                <p class="small mb-0">{{ $agente->expediente?->observaciones ?? 'Sin observaciones.' }}</p>
            @endcan
        </div></div>
    </div>
</div>

{{-- Asignaciones de zona (Art. 16) --}}
<div class="card border-0 shadow-sm mt-4"><div class="card-body">
    <h2 class="h6 text-simetsa mb-3"><i class="bi bi-geo me-1"></i> Asignación de zonas (Art. 16)</h2>
    <table class="table table-sm align-middle">
        <thead class="table-light"><tr><th>Zona</th><th>Desde</th><th>Hasta</th><th>Estado</th><th class="text-end"></th></tr></thead>
        <tbody>
            @forelse($agente->asignaciones as $asig)
                <tr>
                    <td>{{ $asig->zona?->nombre }}</td>
                    <td class="small">{{ $asig->fecha_inicio?->format('d/m/Y') }}</td>
                    <td class="small">{{ $asig->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                    <td>@if($asig->activa)<span class="badge bg-success">Activa</span>@else<span class="badge bg-secondary">Inactiva</span>@endif</td>
                    <td class="text-end">
                        @can('agentes.editar')
                            @unless($terminado)
                            <button type="button" class="btn btn-sm btn-outline-primary btn-editar-asignacion"
                                data-bs-toggle="modal" data-bs-target="#modalEditarAsignacion"
                                data-url="{{ route('asignaciones-zona.update', $asig) }}"
                                data-zona-id="{{ $asig->zona_id }}"
                                data-fecha-inicio="{{ $asig->fecha_inicio?->format('Y-m-d') }}"
                                data-fecha-fin="{{ $asig->fecha_fin?->format('Y-m-d') }}"
                                data-observacion="{{ $asig->observacion }}"
                                data-activa="{{ $asig->activa ? '1' : '0' }}"><i class="bi bi-pencil"></i></button>
                            @endunless
                            <form method="POST" action="{{ route('asignaciones-zona.destroy', $asig) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Quitar esta asignación?')"><i class="bi bi-trash"></i></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted small">Sin zonas asignadas.</td></tr>
            @endforelse
        </tbody>
    </table>
    @can('agentes.editar')
    @unless($terminado)
    <form method="POST" action="{{ route('asignaciones-zona.store', $agente) }}" class="row g-2 align-items-end border-top pt-3">
        @csrf
        <input type="hidden" name="activa" value="0">
        <input type="hidden" name="activa" value="1"> {{-- nueva asignación siempre activa --}}
        <div class="col-md-4">
            <label class="form-label small mb-1">Zona</label>
            <select name="zona_id" class="form-select form-select-sm" required>
                <option value="">— Seleccione —</option>
                @foreach($zonas as $z)<option value="{{ $z->id }}">{{ $z->nombre }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label small mb-1">Desde</label><input type="date" name="fecha_inicio" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required></div>
        <div class="col-md-3"><label class="form-label small mb-1">Hasta (opcional)</label><input type="date" name="fecha_fin" class="form-control form-control-sm"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-simetsa w-100"><i class="bi bi-plus-lg"></i> Asignar</button></div>
    </form>
    @endunless
    @endcan
</div></div>

{{-- Horarios rotativos (Art. 37.4) --}}
<div class="card border-0 shadow-sm mt-3"><div class="card-body">
    <h2 class="h6 text-simetsa mb-3"><i class="bi bi-clock-history me-1"></i> Horarios rotativos (Art. 37.4)</h2>
    <p class="small text-muted">El SIMETSA opera martes a viernes y domingo, 08:00–18:00 (Art. 12). Cada horario es semanal recurrente durante su vigencia.</p>
    <table class="table table-sm align-middle">
        <thead class="table-light"><tr><th>Día</th><th>Zona</th><th>Horario</th><th>Vigencia</th><th class="text-end"></th></tr></thead>
        <tbody>
            @forelse($agente->horarios as $h)
                <tr>
                    <td>{{ $h->dia_label }}</td>
                    <td>{{ $h->zona?->nombre }}</td>
                    <td class="small">{{ \Carbon\Carbon::parse($h->hora_inicio)->format('H:i') }}–{{ \Carbon\Carbon::parse($h->hora_fin)->format('H:i') }}</td>
                    <td class="small">{{ $h->vigente_desde?->format('d/m/Y') }} → {{ $h->vigente_hasta?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-end">
                        @can('agentes.editar')
                            @unless($terminado)
                            <button type="button" class="btn btn-sm btn-outline-primary btn-editar-horario"
                                data-bs-toggle="modal" data-bs-target="#modalEditarHorario"
                                data-url="{{ route('horarios-rotativos.update', $h) }}"
                                data-dia="{{ $h->dia_semana }}"
                                data-zona-id="{{ $h->zona_id }}"
                                data-inicio="{{ \Carbon\Carbon::parse($h->hora_inicio)->format('H:i') }}"
                                data-fin="{{ \Carbon\Carbon::parse($h->hora_fin)->format('H:i') }}"
                                data-desde="{{ $h->vigente_desde?->format('Y-m-d') }}"
                                data-hasta="{{ $h->vigente_hasta?->format('Y-m-d') }}"><i class="bi bi-pencil"></i></button>
                            @endunless
                            <form method="POST" action="{{ route('horarios-rotativos.destroy', $h) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Quitar este horario?')"><i class="bi bi-trash"></i></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted small">Sin horarios rotativos.</td></tr>
            @endforelse
        </tbody>
    </table>
    @can('agentes.editar')
    @unless($terminado)
        @if(empty($diaHorarios))
            <p class="small text-danger border-top pt-3 mb-0">No hay días de operación configurados (revisá Catálogos → Horarios).</p>
        @else
        <form method="POST" action="{{ route('horarios-rotativos.store', $agente) }}" class="row g-2 align-items-end border-top pt-3">
            @csrf
            <div class="col-md-2"><label class="form-label small mb-1">Día</label>
                <select name="dia_semana" id="horario_dia" class="form-select form-select-sm" required>
                    @foreach($diaHorarios as $dia => $info)<option value="{{ $dia }}">{{ $info['nombre'] }}</option>@endforeach
                </select></div>
            <div class="col-md-3"><label class="form-label small mb-1">Zona</label>
                <select name="zona_id" class="form-select form-select-sm" required>
                    <option value="">—</option>
                    @foreach($zonas as $z)<option value="{{ $z->id }}">{{ $z->nombre }}</option>@endforeach
                </select></div>
            <div class="col-md-2"><label class="form-label small mb-1">Inicio</label><input type="time" name="hora_inicio" id="horario_inicio" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><label class="form-label small mb-1">Fin</label><input type="time" name="hora_fin" id="horario_fin" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><label class="form-label small mb-1">Vigente desde</label><input type="date" name="vigente_desde" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-1"><button class="btn btn-sm btn-simetsa w-100"><i class="bi bi-plus-lg"></i></button></div>
        </form>
        @endif
    @endunless
    @endcan
</div></div>

{{-- Amonestaciones (Art. 40) --}}
<div class="card border-0 shadow-sm mt-3"><div class="card-body">
    <h2 class="h6 text-simetsa mb-2"><i class="bi bi-exclamation-triangle me-1"></i> Amonestaciones (Art. 40)</h2>
    <p class="small text-muted">Faltas registradas: <strong>{{ $agente->amonestaciones->count() }}</strong>. La 3.ª falta termina la autorización del agente.</p>

    @if($terminado)
        <div class="alert alert-dark small"><i class="bi bi-x-octagon me-1"></i> La autorización de este agente fue <strong>terminada</strong> (Art. 40.c). Eliminá una amonestación para reactivarlo.</div>
    @endif

    <table class="table table-sm align-middle">
        <thead class="table-light"><tr><th>N.º</th><th>Tipo</th><th>Motivo</th><th>Fecha</th><th>Registró</th><th class="text-end"></th></tr></thead>
        <tbody>
            @forelse($agente->amonestaciones as $am)
                <tr>
                    <td>{{ $am->numero_falta }}</td>
                    <td><span class="badge bg-{{ $am->tipo_color }}">{{ $am->tipo_label }}</span></td>
                    <td class="small">{{ $am->motivo }}</td>
                    <td class="small">{{ $am->fecha?->format('d/m/Y') }}</td>
                    <td class="small">{{ $am->registradaPor?->name ?? '—' }}</td>
                    <td class="text-end">
                        @can('agentes.editar')
                            <button type="button" class="btn btn-sm btn-outline-primary btn-editar-amonestacion"
                                data-bs-toggle="modal" data-bs-target="#modalEditarAmonestacion"
                                data-url="{{ route('amonestaciones-agente.update', $am) }}"
                                data-motivo="{{ $am->motivo }}"
                                data-fecha="{{ $am->fecha?->format('Y-m-d') }}"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('amonestaciones-agente.destroy', $am) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta amonestación?')"><i class="bi bi-trash"></i></button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted small">Sin amonestaciones.</td></tr>
            @endforelse
        </tbody>
    </table>

    @can('agentes.editar')
    @unless($terminado)
    <form method="POST" action="{{ route('amonestaciones-agente.store', $agente) }}" class="row g-2 align-items-end border-top pt-3">
        @csrf
        <div class="col-md-7"><label class="form-label small mb-1">Motivo de la falta</label><input type="text" name="motivo" class="form-control form-control-sm" maxlength="500" required></div>
        <div class="col-md-3"><label class="form-label small mb-1">Fecha</label><input type="date" name="fecha" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-warning w-100"><i class="bi bi-exclamation-circle me-1"></i> Amonestar</button></div>
    </form>
    <small class="form-text text-muted">El tipo se asigna automáticamente según el número de falta (Art. 40).</small>
    @endunless
    @endcan
</div></div>

{{-- ===== Modales de edición ===== --}}
@can('agentes.editar')
<div class="modal fade" id="modalEditarAsignacion" tabindex="-1"><div class="modal-dialog">
    <form method="POST" id="formEditarAsignacion" class="modal-content">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Editar asignación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label small">Zona</label>
                <select id="editAsigZona" name="zona_id" class="form-select" required>
                    @foreach($zonas as $z)<option value="{{ $z->id }}">{{ $z->nombre }}</option>@endforeach
                </select></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label small">Desde</label><input type="date" id="editAsigInicio" name="fecha_inicio" class="form-control" required></div>
                <div class="col-6"><label class="form-label small">Hasta</label><input type="date" id="editAsigFin" name="fecha_fin" class="form-control"></div>
            </div>
            <div class="mt-2"><label class="form-label small">Observación</label><input type="text" id="editAsigObs" name="observacion" class="form-control" maxlength="255"></div>
            <div class="form-check form-switch mt-2">
                <input type="hidden" name="activa" value="0">
                <input class="form-check-input" type="checkbox" id="editAsigActiva" name="activa" value="1">
                <label class="form-check-label small" for="editAsigActiva">Activa</label>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-simetsa">Guardar</button></div>
    </form>
</div></div>

<div class="modal fade" id="modalEditarHorario" tabindex="-1"><div class="modal-dialog">
    <form method="POST" id="formEditarHorario" class="modal-content">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Editar horario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-2">
                <div class="col-6"><label class="form-label small">Día</label>
                    <select id="editHorDia" name="dia_semana" class="form-select" required>
                        @foreach($diaHorarios as $dia => $info)<option value="{{ $dia }}">{{ $info['nombre'] }}</option>@endforeach
                    </select></div>
                <div class="col-6"><label class="form-label small">Zona</label>
                    <select id="editHorZona" name="zona_id" class="form-select" required>
                        @foreach($zonas as $z)<option value="{{ $z->id }}">{{ $z->nombre }}</option>@endforeach
                    </select></div>
                <div class="col-6"><label class="form-label small">Inicio</label><input type="time" id="editHorInicio" name="hora_inicio" class="form-control" required></div>
                <div class="col-6"><label class="form-label small">Fin</label><input type="time" id="editHorFin" name="hora_fin" class="form-control" required></div>
                <div class="col-6"><label class="form-label small">Vigente desde</label><input type="date" id="editHorDesde" name="vigente_desde" class="form-control" required></div>
                <div class="col-6"><label class="form-label small">Vigente hasta</label><input type="date" id="editHorHasta" name="vigente_hasta" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-simetsa">Guardar</button></div>
    </form>
</div></div>

<div class="modal fade" id="modalEditarAmonestacion" tabindex="-1"><div class="modal-dialog">
    <form method="POST" id="formEditarAmonestacion" class="modal-content">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Editar amonestación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label small">Motivo</label><input type="text" id="editAmoMotivo" name="motivo" class="form-control" maxlength="500" required></div>
            <div><label class="form-label small">Fecha</label><input type="date" id="editAmoFecha" name="fecha" class="form-control"></div>
            <small class="form-text text-muted">El tipo y el número de falta se recalculan automáticamente (Art. 40).</small>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-simetsa">Guardar</button></div>
    </form>
</div></div>
@endcan
@endsection

@push('scriptsFooter')
<script>
(function () {
    // Autocompletar horas al elegir el día (formulario de alta de horario)
    const diaHorarios = @json((object) $diaHorarios);
    const selDia = document.getElementById('horario_dia');
    const inpInicio = document.getElementById('horario_inicio');
    const inpFin = document.getElementById('horario_fin');
    function autoHoras() {
        if (!selDia) return;
        const h = diaHorarios[selDia.value];
        if (h) { inpInicio.value = h.inicio; inpFin.value = h.fin; }
    }
    selDia?.addEventListener('change', autoHoras);
    autoHoras();

    // Poblar modal de editar asignación
    document.querySelectorAll('.btn-editar-asignacion').forEach(b => b.addEventListener('click', () => {
        document.getElementById('formEditarAsignacion').action = b.dataset.url;
        document.getElementById('editAsigZona').value   = b.dataset.zonaId;
        document.getElementById('editAsigInicio').value = b.dataset.fechaInicio || '';
        document.getElementById('editAsigFin').value    = b.dataset.fechaFin || '';
        document.getElementById('editAsigObs').value    = b.dataset.observacion || '';
        document.getElementById('editAsigActiva').checked = b.dataset.activa === '1';
    }));

    // Poblar modal de editar horario
    document.querySelectorAll('.btn-editar-horario').forEach(b => b.addEventListener('click', () => {
        document.getElementById('formEditarHorario').action = b.dataset.url;
        document.getElementById('editHorDia').value    = b.dataset.dia;
        document.getElementById('editHorZona').value   = b.dataset.zonaId;
        document.getElementById('editHorInicio').value = b.dataset.inicio || '';
        document.getElementById('editHorFin').value    = b.dataset.fin || '';
        document.getElementById('editHorDesde').value  = b.dataset.desde || '';
        document.getElementById('editHorHasta').value  = b.dataset.hasta || '';
    }));

    // Poblar modal de editar amonestación
    document.querySelectorAll('.btn-editar-amonestacion').forEach(b => b.addEventListener('click', () => {
        document.getElementById('formEditarAmonestacion').action = b.dataset.url;
        document.getElementById('editAmoMotivo').value = b.dataset.motivo || '';
        document.getElementById('editAmoFecha').value  = b.dataset.fecha || '';
    }));
})();
</script>
@endpush