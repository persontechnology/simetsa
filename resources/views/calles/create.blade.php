@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('calles.create') }}
@endsection

@section('content')
    <form method="POST" action="{{ route('calles.store') }}" novalidate>@csrf
        @include('calles._form', ['calle' => null, 'modo' => 'crear'])
    </form>
@endsection