{{-- resources/views/reportes/recaudacion/pdf.blade.php --}}
{{-- Vista imprimible del reporte de recaudación (Fase 8.B). --}}
{{-- Extiende el layout mínimo sin sidebar. El usuario usa Ctrl+P → Guardar como PDF. --}}
@extends('layouts.impresion')

@section('titulo', 'Reporte de Recaudación')

@section('content')

    {{-- Parámetros del reporte --}}
    <div class="mb-3 text-muted" style="font-size:.8rem;">
        @if(request('fecha_desde') || request('fecha_hasta'))
            Período:
            <strong>{{ request('fecha_desde', '—') }}</strong> al
            <strong>{{ request('fecha_hasta', '—') }}</strong> ·
        @endif
        @if(request('proveedor'))
            Proveedor: <strong>{{ request('proveedor') }}</strong> ·
        @endif
        @if(request('tipo'))
            Tipo: <strong>{{ ucfirst(request('tipo')) }}</strong> ·
        @endif
        Total transacciones: <strong>{{ $transacciones->count() }}</strong>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="row g-2 totales mb-3">
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Total recaudado</div>
                <div class="fw-bold">$ {{ number_format($totales['total'], 2) }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Tickets</div>
                <div class="fw-bold">$ {{ number_format($totales['total_tickets'], 2) }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Multas</div>
                <div class="fw-bold">$ {{ number_format($totales['total_infracciones'], 2) }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Transacciones</div>
                <div class="fw-bold">{{ number_format($totales['cantidad']) }}</div>
            </div>
        </div>
    </div>

    {{-- Tabla principal --}}
    @if($transacciones->isEmpty())
        <p class="text-muted text-center">No hay transacciones para los filtros seleccionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Referencia</th>
                    <th>Placa</th>
                    <th>Zona</th>
                    <th>Proveedor</th>
                    <th style="text-align:right;">Monto (USD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transacciones as $tx)
                    @php
                        $concepto   = $tx->concepto;
                        $esTicket   = $tx->concepto_type === \App\Models\Ticket::class;
                        $referencia = $esTicket
                            ? ($concepto?->codigo ?? '—')
                            : 'INF-' . str_pad($concepto?->id ?? 0, 6, '0', STR_PAD_LEFT);
                        $placa      = $esTicket
                            ? ($concepto?->vehiculo?->placa ?? '—')
                            : ($concepto?->placa ?? '—');
                        $zona       = $concepto?->zona?->nombre ?? '—';
                    @endphp
                    <tr>
                        <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $esTicket ? 'Ticket' : 'Infracción' }}</td>
                        <td>{{ $referencia }}</td>
                        <td>{{ $placa }}</td>
                        <td>{{ $zona }}</td>
                        <td>{{ $tx->proveedor->etiqueta() }}</td>
                        <td style="text-align:right;">{{ number_format((float) $tx->monto, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align:right;font-weight:700;">TOTAL</td>
                    <td style="text-align:right;font-weight:700;">
                        $ {{ number_format($totales['total'], 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

@endsection
