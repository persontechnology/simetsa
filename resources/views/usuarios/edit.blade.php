{{-- resources/views/usuarios/edit.blade.php --}}
@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('usuarios.edit', $usuario) }}
@endsection

@section('content')
    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        @include('usuarios._form', ['usuario' => $usuario, 'modo' => 'editar'])
    </form>
@endsection