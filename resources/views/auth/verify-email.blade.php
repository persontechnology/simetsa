@extends('layouts.guest')
@section('content')
    <div class="card mb-0">
        <div class="card-body">
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center mb-4 mt-2">
                    <img src="{{ asset('assets/images/logo_icon.svg') }}" class="h-48px" alt="">
                </div>
                <h5 class="mb-0">Verificar correo electrónico</h5>
                <span class="d-block text-muted">Antes de continuar, confirme su correo electrónico desde el enlace que le enviamos.</span>
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-4 font-medium text-sm text-success">
                    Se ha enviado un nuevo enlace de verificación a su correo electrónico.
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mt-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Reenviar correo</button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link text-secondary">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
@endsection