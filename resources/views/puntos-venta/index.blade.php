@extends('layouts.app')
@section('titulo', 'Puntos de venta')
@section('encabezado')<h1 class="h3 mb-0"><i class="bi bi-shop text-simetsa me-1"></i> Puntos de venta</h1>@endsection

@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>Código</th><th>Local</th><th>Responsable</th><th>Contrato</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($puntos as $p)
                <tr>
                    <td><code>{{ $p->codigo }}</code></td>
                    <td>{{ $p->nombre_comercial }}</td>
                    <td class="small">{{ $p->user?->name ?? '—' }}</td>
                    <td class="small">{{ $p->contrato?->numero_contrato ?? '—' }}</td>
                    <td><span class="badge bg-{{ $p->estado_color }}">{{ $p->estado_label }}</span></td>
                    <td class="text-end"><a href="{{ route('puntos-venta.show', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted small">No hay puntos de venta activos.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $puntos->links() }}
</div></div>
@endsection