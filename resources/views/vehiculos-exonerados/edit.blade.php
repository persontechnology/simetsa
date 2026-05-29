@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('vehiculos-exonerados.edit', $vehiculo) }}
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width: 860px">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-shield-check me-2"></i>Editar exoneración — <strong>{{ $vehiculo->placa }}</strong>
    </div>
    <form method="POST" action="{{ route('vehiculos-exonerados.update', $vehiculo) }}">
        @csrf @method('PUT')
        <div class="card-body">
            @include('vehiculos-exonerados._form')
        </div>
        <div class="card-footer bg-transparent text-end">
            <a href="{{ route('vehiculos-exonerados.index') }}" class="btn btn-outline-secondary me-2">Cancelar</a>
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </div>
    </form>
</div>
@endsection
