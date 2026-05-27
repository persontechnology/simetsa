@extends('layouts.guest')
@section('content')
    <!-- Session Status -->
    

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center mb-4 mt-2">
                        <img src="{{ asset('assets/images/logo_icon.svg') }}" class="h-48px" alt="">
                    </div>
                    <h5 class="mb-0">Olvidé mi contraseña</h5>
                    <span class="d-block text-muted">Ingrese su correo electrónico para enviar el enlace de restablecimiento</span>
                    
                </div>
                <x-auth-session-status class="mb-4" :status="session('status')" />
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" :value="old('email')" required autofocus placeholder="nombre@ejemplo.com">
                        <div class="form-control-feedback-icon">
                            <i class="ph-envelope-open text-muted"></i>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
                </div>
            </div>
        </div>
    </form>
@endsection