@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('zonas.edit', $zona) }}
@endsection
@section('content')
    <form method="POST" action="{{ route('zonas.update', $zona) }}" novalidate>@csrf @method('PUT')
        @include('zonas._form', ['modo' => 'editar'])
    </form>
@endsection