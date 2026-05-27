{{-- resources/views/dias-feriado/edit.blade.php --}}
@extends('layouts.app')
@section('breadcrumb')
{{ Breadcrumbs::render('dias-feriado.edit', $feriado) }}
@endsection
@section('content')
<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('dias-feriado.update', $feriado) }}">@csrf @method('PUT')
        @include('dias-feriado._form', ['modo' => 'editar'])
    </form>
</div></div>
@endsection