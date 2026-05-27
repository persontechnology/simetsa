@extends('layouts.app')
@section('breadcrumb')
{{ Breadcrumbs::render('tipos-plaza.edit', $tipoPlaza) }}
@endsection
@section('content')
    <div class="card border-0 shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('tipos-plaza.update', $tipoPlaza) }}">@csrf @method('PUT')
            @include('tipos-plaza._form', ['modo' => 'editar'])
        </form>
    </div></div>
@endsection