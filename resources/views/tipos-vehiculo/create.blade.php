@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('tipos-vehiculo.create') }}
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('tipos-vehiculo.store') }}">
            @csrf
            @include('tipos-vehiculo._form', ['modo' => 'crear'])
        </form>
    </div>
</div>
@endsection
