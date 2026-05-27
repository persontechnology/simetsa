@extends('layouts.app')
@section('breadcrumb')
{{  Breadcrumbs::render('solicitudes-agente.edit', $solicitud) }}
@endsection
@section('content')
    <form method="POST" action="{{ route('solicitudes-agente.update', $solicitud) }}" novalidate>@csrf @method('PUT')
        @include('solicitudes-agente._form', ['modo' => 'editar'])
    </form>
@endsection