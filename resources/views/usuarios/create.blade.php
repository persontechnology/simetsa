{{-- resources/views/usuarios/create.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('usuarios.create') }}
@endsection

@section('content')
    <form method="POST" action="{{ route('usuarios.store') }}" enctype="multipart/form-data">
        @csrf
        @include('usuarios._form', ['usuario' => null, 'modo' => 'crear'])
    </form>
@endsection