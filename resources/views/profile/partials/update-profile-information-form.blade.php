<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('Profile Information') }}</h5>
        <span class="text-muted">{{ __("Update your account's profile information and email address.") }}</span>
    </div>

    <div class="card-body">
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')

            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <div class="form-control-feedback form-control-feedback-start">
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}"
                        required
                        autofocus
                        autocomplete="name"
                    >
                    <div class="form-control-feedback-icon">
                        <i class="ph-user text-muted"></i>
                    </div>
                </div>
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <div class="form-control-feedback form-control-feedback-start">
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="username"
                    >
                    <div class="form-control-feedback-icon">
                        <i class="ph-envelope text-muted"></i>
                    </div>
                </div>
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="alert alert-warning mt-3 mb-0">
                        <div class="d-flex">
                            <i class="ph-warning-circle me-2"></i>
                            <div>
                                {{ __('Your email address is unverified.') }}
                                <button form="send-verification" class="btn btn-link p-0 align-baseline">
                                    {{ __('Click here to re-send the verification email.') }}
                                </button>

                                @if (session('status') === 'verification-link-sent')
                                    <div class="fw-semibold mt-1">
                                        {{ __('A new verification link has been sent to your email address.') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ph-floppy-disk me-2"></i>
                    {{ __('Save') }}
                </button>

                @if (session('status') === 'profile-updated')
                    <span class="text-success">{{ __('Saved.') }}</span>
                @endif
            </div>
        </form>
    </div>
</div>
