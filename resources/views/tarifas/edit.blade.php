@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('tarifas.edit', $tarifa) }}
@endsection 
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('tarifas.update', $tarifa) }}">@csrf @method('PUT')
            @include('tarifas._form', ['modo' => 'editar'])
        </form>
    </div>
</div>
@endsection