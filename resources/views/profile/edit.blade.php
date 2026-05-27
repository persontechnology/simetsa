@extends('layouts.app')

@section('title', 'Perfil')
@section('page_title', 'Perfil')
@section('page_subtitle', 'Administra la informacion de tu cuenta')

@section('breadcrumbs')
    <span class="breadcrumb-item active">Perfil</span>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-8">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
        </div>

        <div class="col-xl-4">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection
