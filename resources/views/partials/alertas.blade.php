{{-- resources/views/layouts/partials/alertas.blade.php --}}
{{--
    Mensajes flash reutilizables (success, error, warning, info).
    Se renderizan automáticamente en cualquier vista que extienda
    layouts.app, gracias al @include en el layout principal.
--}}

@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $clave => $tipoBs)
    @if (session($clave))
        <div class="alert alert-{{ $tipoBs }} alert-dismissible fade show" role="alert">
            <i class="bi
                @if($tipoBs === 'success') bi-check-circle
                @elseif($tipoBs === 'danger') bi-x-circle
                @elseif($tipoBs === 'warning') bi-exclamation-triangle
                @else bi-info-circle
                @endif me-1"></i>
            {{ session($clave) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
@endforeach

{{-- Errores de validación globales (cuando no se muestran inline) --}}
@if ($errors->any() && !$errors->has('_inline'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-octagon me-1"></i>
        Se encontraron los siguientes errores:
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif