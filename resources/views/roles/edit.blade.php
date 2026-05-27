{{-- resources/views/roles/edit.blade.php --}}
@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('roles.edit', $rol) }}
@endsection

@section('content')
    <form method="POST" action="{{ route('roles.update', $rol) }}">
        @csrf
        @method('PUT')
        @include('roles._form', ['modo' => 'editar'])
    </form>
@endsection