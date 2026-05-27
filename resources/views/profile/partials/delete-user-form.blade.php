<div class="card border-danger">
    <div class="card-header">
        <h5 class="mb-0">{{ __('Delete Account') }}</h5>
        <span class="text-muted">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}</span>
    </div>

    <div class="card-body">
        <p class="mb-3">
            {{ __('Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>

        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
            <i class="ph-trash me-2"></i>
            {{ __('Delete Account') }}
        </button>
    </div>
</div>

<div class="modal fade" id="confirm-user-deletion" tabindex="-1" aria-labelledby="confirm-user-deletion-label" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="{{ route('profile.destroy') }}" class="modal-content">
            @csrf
            @method('delete')

            <div class="modal-header">
                <h5 class="modal-title" id="confirm-user-deletion-label">
                    {{ __('Are you sure you want to delete your account?') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cancel') }}"></button>
            </div>

            <div class="modal-body">
                <p class="text-muted">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <div class="form-control-feedback form-control-feedback-start">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control @if ($errors->userDeletion->has('password')) is-invalid @endif"
                            placeholder="{{ __('Password') }}"
                        >
                        <div class="form-control-feedback-icon">
                            <i class="ph-lock text-muted"></i>
                        </div>
                    </div>
                    @foreach ($errors->userDeletion->get('password') as $message)
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="ph-trash me-2"></i>
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </div>
</div>

@if ($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = new bootstrap.Modal(document.getElementById('confirm-user-deletion'));
                modal.show();
            });
        </script>
    @endpush
@endif
