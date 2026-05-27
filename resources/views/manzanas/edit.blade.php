@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('manzanas.edit', $manzana) }}
@endsection

@section('content')
    <form method="POST" action="{{ route('manzanas.update', $manzana) }}" novalidate>@csrf @method('PUT')
        @include('manzanas._form', ['modo' => 'editar'])
    </form>
@endsection