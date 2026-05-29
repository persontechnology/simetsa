@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('tipos-vehiculo.edit', $tipoVehiculo) }}
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('tipos-vehiculo.update', $tipoVehiculo) }}">
            @csrf @method('PUT')
            @include('tipos-vehiculo._form', ['modo' => 'editar'])
        </form>
    </div>
</div>
@endsection
