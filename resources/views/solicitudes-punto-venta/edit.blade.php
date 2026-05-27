@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('solicitudes-punto-venta.edit', $solicitud) }}
@endsection

@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('solicitudes-punto-venta.update', $solicitud) }}">
        @csrf @method('PUT')
        @include('solicitudes-punto-venta._form')
        <div class="text-end mt-4">
            <a href="{{ route('solicitudes-punto-venta.show', $solicitud) }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-simetsa">Guardar cambios</button>
        </div>
    </form>
</div></div>
@endsection