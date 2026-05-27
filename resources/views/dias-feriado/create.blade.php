@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('dias-feriado.create') }}
@endsection
@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('dias-feriado.store') }}">@csrf
        @include('dias-feriado._form', ['feriado' => null, 'modo' => 'crear'])
    </form>
</div></div>
@endsection