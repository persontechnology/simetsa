@extends('layouts.app')
@section('titulo', 'Nuevo curso')
@section('encabezado')<h1 class="h3 mb-0"><i class="bi bi-plus-circle text-simetsa me-1"></i> Nuevo curso</h1>@endsection
@section('content')
    <form method="POST" action="{{ route('cursos-capacitacion.store') }}" novalidate>@csrf
        @include('cursos-capacitacion._form', ['curso' => null, 'modo' => 'crear'])
    </form>
@endsection