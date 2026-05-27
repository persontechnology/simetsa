@extends('layouts.guest')
@section('content')
    <form class="login-form" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center mb-4 mt-2">
                        <img src="{{ asset('assets/images/logo_icon.svg') }}" class="h-48px" alt="">
                    </div>
                    <h5 class="mb-0">Crear cuenta</h5>
                    <span class="d-block text-muted">Complete los datos para registrarse</span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre completo</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Juan Pérez">
                        <div class="form-control-feedback-icon">
                            <i class="ph-user-circle text-muted"></i>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="nombre@ejemplo.com">
                        <div class="form-control-feedback-icon">
                            <i class="ph-envelope-open text-muted"></i>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="•••••••••••">
                        <div class="form-control-feedback-icon">
                            <i class="ph-lock text-muted"></i>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="•••••••••••">
                        <div class="form-control-feedback-icon">
                            <i class="ph-lock-key text-muted"></i>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('login') }}" class="text-secondary">¿Ya tiene cuenta? Ingresar</a>
                    <button type="submit" class="btn btn-primary">Registrarse</button>
                </div>
            </div>
        </div>
    </form>
@endsection