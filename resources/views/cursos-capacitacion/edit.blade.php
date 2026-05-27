@extends('layouts.app')
@section('breadcrumb')
{{  Breadcrumbs::render('cursos-capacitacion.edit', $curso) }}
@endsection
@section('content')
    <form method="POST" action="{{ route('cursos-capacitacion.update', $curso) }}" novalidate>@csrf @method('PUT')
        @include('cursos-capacitacion._form', ['modo' => 'editar'])
    </form>
@endsection