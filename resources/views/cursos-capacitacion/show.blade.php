{{-- resources/views/cursos-capacitacion/show.blade.php --}}
@extends('layouts.app')
@section('breadcrumb')
{{  Breadcrumbs::render('cursos-capacitacion.show', $curso) }}
@endsection

@section('encabezado')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">
            <i class="bi bi-mortarboard text-simetsa me-1"></i> Curso <code>{{ $curso->codigo }}</code>
            <span class="badge bg-{{ $curso->estado_color }} ms-2">{{ $curso->estado_label }}</span>
        </h1>
        <a href="{{ route('cursos-capacitacion.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <h2 class="h5 mb-1">{{ $curso->nombre }}</h2>
    <p class="small text-muted mb-2">{{ $curso->descripcion }}</p>
    <span class="small">Del {{ $curso->fecha_inicio?->format('d/m/Y') }} al {{ $curso->fecha_fin?->format('d/m/Y') ?? '—' }} · Nota mínima de aprobación: <strong>{{ number_format($notaMinima, 2) }}</strong> (Art. 33).</span>
</div></div>

{{-- Inscribir postulante --}}
@can('agentes.editar')
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <h2 class="h6 text-simetsa mb-2">Inscribir postulante</h2>
    @if($disponibles->isEmpty())
        <p class="small text-muted mb-0">No hay postulantes en etapa de capacitación disponibles para inscribir.</p>
    @else
        <form method="POST" action="{{ route('inscripciones-curso.store', $curso) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-8">
                <select name="solicitud_agente_id" class="form-select" required>
                    <option value="">— Seleccione un postulante —</option>
                    @foreach($disponibles as $s)
                        <option value="{{ $s->id }}">{{ $s->codigo }} · {{ $s->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-simetsa w-100"><i class="bi bi-person-plus me-1"></i> Inscribir</button>
            </div>
        </form>
    @endif
</div></div>
@endcan

{{-- Inscripciones (ranking por promedio — Art. 33.d) --}}
<h2 class="h6 text-simetsa mb-2">Inscripciones y calificaciones <span class="text-muted small">(ordenadas por promedio)</span></h2>
@forelse($curso->inscripciones as $insc)
    <div class="card border-0 shadow-sm mb-2"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <strong>{{ $insc->solicitud?->nombre_completo }}</strong>
                <span class="text-muted small ms-1">{{ $insc->solicitud?->codigo }}</span>
            </div>
            <div>
                @if($insc->promedio_final !== null)
                    <span class="me-2">Promedio: <strong>{{ number_format((float) $insc->promedio_final, 2) }}</strong></span>
                @endif
                <span class="badge bg-{{ $insc->estado_color }}">{{ $insc->estado_label }}</span>
            </div>
        </div>

        @can('agentes.editar')
        <form method="POST" action="{{ route('inscripciones-curso.calificar', $insc) }}" class="row g-2 align-items-end">
            @csrf
            @foreach($tematicas as $clave => $label)
                <div class="col-md-3">
                    <label class="form-label small mb-1">{{ $label }}</label>
                    <input type="number" step="0.01" min="0" max="100" name="notas[{{ $clave }}]"
                           class="form-control form-control-sm" value="{{ $insc->notaPorTematica($clave) }}" required>
                </div>
            @endforeach
            <div class="col-md-3 d-flex gap-1">
                <button class="btn btn-sm btn-simetsa flex-grow-1"><i class="bi bi-calculator me-1"></i> Registrar notas</button>
            </div>
        </form>
        <form method="POST" action="{{ route('inscripciones-curso.destroy', $insc) }}" class="mt-2">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('¿Quitar esta inscripción?')">Quitar inscripción</button>
        </form>
        @endcan
    </div></div>
@empty
    <div class="alert alert-light border">Aún no hay postulantes inscritos en este curso.</div>
@endforelse
@endsection