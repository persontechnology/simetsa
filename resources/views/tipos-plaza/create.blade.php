{{-- resources/views/tipos-plaza/create.blade.php --}}
@extends('layouts.app')
@section('titulo', 'Nuevo tipo de plaza')
@section('encabezado')<h1 class="h3 mb-0"><i class="bi bi-plus-circle text-simetsa me-1"></i> Nuevo tipo de plaza</h1>@endsection
@section('content')
    <div class="card border-0 shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('tipos-plaza.store') }}">@csrf
            @include('tipos-plaza._form', ['tipoPlaza' => null, 'modo' => 'crear'])
        </form>
    </div></div>
@endsection