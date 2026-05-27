@extends('layouts.app')
@section('titulo', 'Nueva solicitud de agente')
@section('encabezado')<h1 class="h3 mb-0"><i class="bi bi-plus-circle text-simetsa me-1"></i> Nueva solicitud de agente</h1>@endsection
@section('content')
    <form method="POST" action="{{ route('solicitudes-agente.store') }}" novalidate>@csrf
        @include('solicitudes-agente._form', ['solicitud' => null, 'modo' => 'crear'])
    </form>
@endsection