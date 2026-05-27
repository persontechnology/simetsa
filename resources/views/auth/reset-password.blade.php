@extends('layouts.guest')
@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="login-form">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="card mb-0">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center mb-4 mt-2">
                        <img src="{{ asset('assets/images/logo_icon.svg') }}" class="h-48px" alt="">
                    </div>
                    <h5 class="mb-0">Restablecer contraseña</h5>
                    <span class="d-block text-muted">Escriba una nueva contraseña para su cuenta</span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" placeholder="nombre@ejemplo.com">
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

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary w-100">Restablecer contraseña</button>
                </div>
            </div>
        </div>
    </form>
@endsection