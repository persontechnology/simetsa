@extends('layouts.guest')
@section('content')
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="card mb-0">
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center mb-4 mt-2">
                        <img src="{{ asset('assets/images/logo_icon.svg') }}" class="h-48px" alt="">
                    </div>
                    <h5 class="mb-0">Confirmar contraseña</h5>
                    <span class="d-block text-muted">Confirme su contraseña para continuar.</span>
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

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </div>
        </div>
    </form>
@endsection