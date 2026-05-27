
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('horarios-operacion.index') }}
@endsection

@section('content')
<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Según el Art. 12 de la Ordenanza, el SIMETSA opera de
    <strong>martes a viernes y domingo, 08:00-18:00</strong>.
    Lunes y sábado están inactivos por defecto.
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Día</th>
                    <th>Hora inicio</th>
                    <th>Hora fin</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($horarios as $h)
                    <tr>
                        <td><strong>{{ $h->nombre_dia }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($h->hora_inicio)->format('H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($h->hora_fin)->format('H:i') }}</td>
                        <td>
                            @if($h->activo)
                                <span class="badge bg-success">Opera</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('horarios.editar')
                                <a href="{{ route('horarios-operacion.edit', $h) }}"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection