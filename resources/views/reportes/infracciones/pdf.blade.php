{{-- resources/views/reportes/infracciones/pdf.blade.php --}}
{{-- Vista imprimible del reporte de infracciones (Fase 8.C). --}}
@extends('layouts.impresion')

@section('titulo', 'Reporte de Infracciones')

@section('content')

    <div class="mb-3 text-muted" style="font-size:.8rem;">
        @if(request('fecha_desde') || request('fecha_hasta'))
            Período: <strong>{{ request('fecha_desde', '—') }}</strong> al <strong>{{ request('fecha_hasta', '—') }}</strong> ·
        @endif
        @if(request('estado'))
            Estado: <strong>{{ request('estado') }}</strong> ·
        @endif
        Total: <strong>{{ $infracciones->count() }}</strong>
    </div>

    <div class="row g-2 totales mb-3">
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Total multas</div>
                <div class="fw-bold">$ {{ number_format($totales['total_multas'], 2) }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Cobrado</div>
                <div class="fw-bold">$ {{ number_format($totales['total_cobrado'], 2) }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Pendiente</div>
                <div class="fw-bold">$ {{ number_format($totales['pendiente_cobro'], 2) }}</div>
            </div>
        </div>
        <div class="col-3">
            <div class="border rounded p-2 text-center">
                <div class="small text-muted">Inmovilizadas activas</div>
                <div class="fw-bold">{{ $totales['inmovilizaciones_activas'] }}</div>
            </div>
        </div>
    </div>

    @if($infracciones->isEmpty())
        <p class="text-muted text-center">No hay infracciones para los filtros seleccionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Placa</th>
                    <th>Zona</th>
                    <th>Agente</th>
                    <th>Estado</th>
                    <th>Inmov.</th>
                    <th style="text-align:right;">Multa (USD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($infracciones as $inf)
                    <tr>
                        <td>{{ $inf->created_at->format('d/m/Y') }}</td>
                        <td>{{ $inf->tipo_infraccion->etiqueta() }}</td>
                        <td>{{ $inf->placa }}</td>
                        <td>{{ $inf->zona?->nombre ?? '—' }}</td>
                        <td>{{ $inf->agente?->nombre_completo ?? '—' }}</td>
                        <td>{{ $inf->estado->etiqueta() }}</td>
                        <td>{{ $inf->inmovilizacion ? $inf->inmovilizacion->estado->etiqueta() : '—' }}</td>
                        <td style="text-align:right;">{{ number_format((float) $inf->monto_multa, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" style="text-align:right;font-weight:700;">TOTAL</td>
                    <td style="text-align:right;font-weight:700;">
                        $ {{ number_format($totales['total_multas'], 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

@endsection
