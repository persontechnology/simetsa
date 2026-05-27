@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('manzanas.create') }}
@endsection

@section('content')
    <form method="POST" action="{{ route('manzanas.store') }}" novalidate>@csrf
        @include('manzanas._form', ['manzana' => null, 'modo' => 'crear'])
    </form>
@endsection