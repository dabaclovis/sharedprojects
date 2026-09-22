<div class="py-4">
    <header class="dashboard-welcome p-4 mb-4"><h1 class="h3">Account settings</h1><p class="text-muted mb-0">Keep your details current and your account secure.</p></header>
    @error('settings') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
    <div class="row">
        <section class="col-lg-6 mb-4" aria-labelledby="profile-settings-heading">
            <div class="card w3-round-xlarge h-100"><div class="card-body">
                <h2 id="profile-settings-heading" class="h5 mb-3"><i class="fa-solid fa-user-pen text-primary mr-2" aria-hidden="true"></i>Account details</h2>
                @if (session('profileStatus')) <p class="alert alert-success" role="status">{{ session('profileStatus') }}</p> @endif
                <form wire:submit="saveProfile" novalidate>
                    <label for="settings-name">Full name</label><input id="settings-name" class="form-control mb-2" wire:model="name" autocomplete="name" placeholder="Joe Doe">
                    @error('name') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="settings-email">Email</label><input id="settings-email" type="email" class="form-control mb-2" wire:model="email" autocomplete="email" placeholder="you@example.com">
                    @error('email') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="settings-current-password">Confirm current password</label><input id="settings-current-password" type="password" class="form-control mb-2" wire:model="currentPassword" autocomplete="current-password">
                    @error('currentPassword') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <p class="small text-muted">Use your first and last name. Your username will stay the same.</p>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save details</button>
                </form>
            </div></div>
        </section>
        <section class="col-lg-6 mb-4" aria-labelledby="password-settings-heading">
            <div class="card w3-round-xlarge h-100"><div class="card-body">
                <h2 id="password-settings-heading" class="h5 mb-3"><i class="fa-solid fa-lock text-success mr-2" aria-hidden="true"></i>Change password</h2>
                @if (session('passwordStatus')) <p class="alert alert-success" role="status">{{ session('passwordStatus') }}</p> @endif
                <form wire:submit="savePassword" novalidate>
                    @foreach (['passwordCurrent' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
                        <label for="settings-{{ $field }}">{{ $label }}</label><input id="settings-{{ $field }}" type="password" class="form-control mb-2" wire:model="{{ $field }}" autocomplete="{{ $field === 'passwordCurrent' ? 'current-password' : 'new-password' }}">
                        @error($field) <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    @endforeach
                    <p class="small text-muted">Choose a unique password with at least 8 characters.</p>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Update password</button>
                </form>
            </div></div>
        </section>
    </div>
    <p class="small text-muted">Need help with your account or data? @guest <a href="{{ route('pages.contact') }}">Contact us</a> @else Contact us @endguest or read our @guest <a href="{{ route('pages.policy') }}">privacy policy</a> @else privacy policy @endguest.</p>
</div>
