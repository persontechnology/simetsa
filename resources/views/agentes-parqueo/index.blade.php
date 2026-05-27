{{-- resources/views/agentes-parqueo/index.blade.php --}}
@extends('layouts.app')
@section('titulo', 'Agentes de parqueo')

@section('encabezado')
    <h1 class="h3 mb-0"><i class="bi bi-person-vcard text-simetsa me-1"></i> Agentes de parqueo</h1>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="estado" class="form-label small mb-1">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos</option>
                @foreach($estados as $clave => $label)
                    <option value="{{ $clave }}" @selected($estado === $clave)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-simetsa flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="{{ route('agentes-parqueo.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div></div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Credencial</th><th>Agente</th><th>N.º credencial</th><th>Autorizado</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                @forelse($agentes as $a)
                    <tr>
                        <td><code>{{ $a->codigo }}</code></td>
                        <td>{{ $a->nombre_completo }}</td>
                        <td>{{ $a->numero_credencial ?? '—' }}</td>
                        <td class="small">{{ $a->fecha_autorizacion?->format('d/m/Y') ?? '—' }}</td>
                        <td><span class="badge bg-{{ $a->estado_color }}">{{ $a->estado_label }}</span></td>
                        <td class="text-end">
                            @can('agentes.ver')
                                <a href="{{ route('agentes-parqueo.show', $a) }}" class="btn btn-sm btn-outline-secondary" title="Expediente"><i class="bi bi-folder2-open"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay agentes autorizados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($agentes->hasPages())<div class="card-footer bg-white border-top-0">{{ $agentes->links() }}</div>@endif
</div>
@endsection