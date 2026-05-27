@extends('layouts.guest')
@section('content')
    <!-- Session Status -->
    

    <!-- Login form -->
    <form class="login-form" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center mb-4 mt-2">
                        <img src="{{ asset('assets/images/logo_icon.svg') }}" class="h-48px" alt="">
                    </div>
                    <h5 class="mb-0">Iniciar sesión</h5>
                    <span class="d-block text-muted">Ingrese sus credenciales a continuación</span>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />
                
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nombre@ejemplo.com">
                        <div class="form-control-feedback-icon">
                            <i class="ph-user-circle text-muted"></i>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="•••••••••••">
                        <div class="form-control-feedback-icon">
                            <i class="ph-lock text-muted"></i>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                </div>

                <div class="text-center">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
                    @endif
                </div>
            </div>
        </div>
    </form>
    <!-- /login form -->
@endsection