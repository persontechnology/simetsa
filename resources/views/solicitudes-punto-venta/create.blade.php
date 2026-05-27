@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('solicitudes-punto-venta.create') }}
@endsection
@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('solicitudes-punto-venta.store') }}">
        @csrf
        @include('solicitudes-punto-venta._form')
        <div class="text-end mt-4">
            <a href="{{ route('solicitudes-punto-venta.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-simetsa">Registrar solicitud</button>
        </div>
    </form>
</div></div>
@endsection