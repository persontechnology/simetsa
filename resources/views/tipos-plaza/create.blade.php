{{-- resources/views/tipos-plaza/create.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('tipos-plaza.create') }}
@endsection

@section('content')
    <div class="card border-0 shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('tipos-plaza.store') }}">@csrf
            @include('tipos-plaza._form', ['tipoPlaza' => null, 'modo' => 'crear'])
        </form>
    </div></div>
@endsection