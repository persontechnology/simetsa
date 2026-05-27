@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('plazas.edit', $plaza) }}
@endsection

@section('content')
    <form method="POST" action="{{ route('plazas.update', $plaza) }}" novalidate>@csrf @method('PUT')
        @include('plazas._form', ['modo' => 'editar'])
    </form>
@endsection