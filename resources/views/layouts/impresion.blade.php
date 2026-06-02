<!DOCTYPE html>
{{-- resources/views/layouts/impresion.blade.php --}}
{{-- Layout mínimo para vistas imprimibles (PDF via Ctrl+P). Sin sidebar ni navbar. --}}
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Reporte') — {{ config('app.name', 'SIMETSA') }}</title>

    <link href="{{ asset('assets/css/ltr/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/icons/bootstrap-icons/font/bootstrap-icons.min.css') }}">

    <style>
        body { font-size: 13px; padding: 1.5rem; background: #fff; color: #212529; }
        .encabezado-reporte { border-bottom: 2px solid #0d6efd; padding-bottom: .75rem; margin-bottom: 1.25rem; }
        .encabezado-reporte h1 { font-size: 1.15rem; font-weight: 700; margin-bottom: .1rem; }
        .encabezado-reporte .meta { font-size: .78rem; color: #6c757d; }
        table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        th, td { padding: .3rem .5rem; border: 1px solid #dee2e6; }
        th { background: #f8f9fa; font-weight: 600; }
        .totales { margin-top: 1rem; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            a { text-decoration: none; color: inherit; }
        }
    </style>

    @stack('estilos')
</head>
<body>

<div class="encabezado-reporte d-flex justify-content-between align-items-start">
    <div>
        <h1>@yield('titulo', 'Reporte')</h1>
        <div class="meta">
            GAD Municipal Salcedo · SIMETSA ·
            Generado: {{ now()->isoFormat('D MMM YYYY, HH:mm') }}
        </div>
    </div>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-sm btn-primary">
            <i class="bi bi-printer me-1"></i> Imprimir / Guardar PDF
        </button>
        <button onclick="window.close()" class="btn btn-sm btn-secondary ms-1">Cerrar</button>
    </div>
</div>

@yield('content')

<script>
    // Auto-print al cargar (opcional — comentar si se prefiere manual)
    // window.addEventListener('load', () => window.print());
</script>
@stack('scripts')
</body>
</html>
