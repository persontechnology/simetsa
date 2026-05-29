@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('vehiculos-exonerados.create') }}
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="max-width: 860px">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-shield-plus me-2"></i>Registrar vehículo exonerado
        <small class="text-muted fw-normal ms-1">(Art. 27 Ordenanza SIMETSA)</small>
    </div>
    <form method="POST" action="{{ route('vehiculos-exonerados.store') }}">
        @csrf
        <div class="card-body">
            @include('vehiculos-exonerados._form')
        </div>
        <div class="card-footer bg-transparent text-end">
            <a href="{{ route('vehiculos-exonerados.index') }}" class="btn btn-outline-secondary me-2">Cancelar</a>
            <button type="submit" class="btn btn-primary">Registrar</button>
        </div>
    </form>
</div>
@endsection
