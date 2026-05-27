{{-- resources/views/roles/create.blade.php --}}
@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('roles.create') }}
@endsection

@section('content')
    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        @include('roles._form', ['rol' => null, 'modo' => 'crear'])
    </form>
@endsection