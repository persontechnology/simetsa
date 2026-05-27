<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('Update Password') }}</h5>
        <span class="text-muted">{{ __('Ensure your account is using a long, random password to stay secure.') }}</span>
    </div>

    <div class="card-body">
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="mb-3">
                <label for="update_password_current_password" class="form-label">{{ __('Current Password') }}</label>
                <div class="form-control-feedback form-control-feedback-start">
                    <input
                        id="update_password_current_password"
                        name="current_password"
                        type="password"
                        class="form-control @if ($errors->updatePassword->has('current_password')) is-invalid @endif"
                        autocomplete="current-password"
                    >
                    <div class="form-control-feedback-icon">
                        <i class="ph-lock text-muted"></i>
                    </div>
                </div>
                @foreach ($errors->updatePassword->get('current_password') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>

            <div class="mb-3">
                <label for="update_password_password" class="form-label">{{ __('New Password') }}</label>
                <div class="form-control-feedback form-control-feedback-start">
                    <input
                        id="update_password_password"
                        name="password"
                        type="password"
                        class="form-control @if ($errors->updatePassword->has('password')) is-invalid @endif"
                        autocomplete="new-password"
                    >
                    <div class="form-control-feedback-icon">
                        <i class="ph-key text-muted"></i>
                    </div>
                </div>
                @foreach ($errors->updatePassword->get('password') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>

            <div class="mb-3">
                <label for="update_password_password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                <div class="form-control-feedback form-control-feedback-start">
                    <input
                        id="update_password_password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="form-control @if ($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
                        autocomplete="new-password"
                    >
                    <div class="form-control-feedback-icon">
                        <i class="ph-check-circle text-muted"></i>
                    </div>
                </div>
                @foreach ($errors->updatePassword->get('password_confirmation') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ph-floppy-disk me-2"></i>
                    {{ __('Save') }}
                </button>

                @if (session('status') === 'password-updated')
                    <span class="text-success">{{ __('Saved.') }}</span>
                @endif
            </div>
        </form>
    </div>
</div>
