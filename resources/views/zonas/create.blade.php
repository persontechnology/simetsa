@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('zonas.create') }}
@endsection

@section('content')
    <form method="POST" action="{{ route('zonas.store') }}" novalidate>
        @csrf
        @include('zonas._form', ['zona' => null, 'modo' => 'crear'])
    </form>
@endsection