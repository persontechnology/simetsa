@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('calles.edit', $calle) }}
@endsection 
@section('content')
    <form method="POST" action="{{ route('calles.update', $calle) }}" novalidate>@csrf @method('PUT')
        @include('calles._form', ['modo' => 'editar'])
    </form>
@endsection