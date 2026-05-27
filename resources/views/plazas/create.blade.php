@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('plazas.create') }}
@endsection

@section('content')
    <form method="POST" action="{{ route('plazas.store') }}" novalidate>@csrf
        @include('plazas._form', ['plaza' => null, 'modo' => 'crear'])
    </form>
@endsection