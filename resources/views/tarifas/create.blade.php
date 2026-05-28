@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('tarifas.create') }}
@endsection

@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('tarifas.store') }}">
        @csrf
        @include('tarifas._form', ['tarifa' => null, 'modo' => 'crear'])
    </form>
</div></div>
@endsection