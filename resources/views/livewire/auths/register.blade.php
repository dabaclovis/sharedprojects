<div class="auth-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <section class="card auth-card border-0 p-4 p-md-5" aria-labelledby="register-heading">
                    <div class="text-center mb-4">
                        <span class="auth-symbol mb-3" aria-hidden="true"><i class="fa-solid fa-user-plus"></i></span>
                        <h1 id="register-heading" class="h3 font-weight-bold">Create an account</h1>
                        <p class="text-muted mb-0">Join the community and share your perspective.</p>
                    </div>
                    <form wire:submit="register" novalidate>
                        <div class="form-group">
                            <label for="name">Full name</label>
                            <x-forms.inputs id="name" type="text" wire:model="form.name" placeholder="Joe Doe"
                                :class="$errors->has('form.name') ? 'form-control is-invalid' : 'form-control'"
                                autocomplete="name" maxlength="255" required autofocus />
                            @error('form.name')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                            {{-- <small class="form-text text-muted">Your username will be generated from your
                                name.</small> --}}
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <x-forms.inputs id="email" type="email" wire:model="form.email"
                                placeholder="you@example.com"
                                :class="$errors->has('form.email') ? 'form-control is-invalid' : 'form-control'"
                                autocomplete="email" maxlength="255" required />
                            @error('form.email')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <x-forms.inputs id="password" type="password" wire:model="form.password"
                                placeholder="Create a password (at least 8 characters)"
                                :class="$errors->has('form.password') ? 'form-control is-invalid' : 'form-control'"
                                autocomplete="new-password" minlength="8" required />
                            @error('form.password')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm password</label>
                            <x-forms.inputs id="password_confirmation" type="password"
                                placeholder="Re-enter your password" wire:model="form.password_confirmation"
                                class="form-control" autocomplete="new-password" required />
                        </div>

                        @error('registration') <p class="text-danger" role="alert">{{ $message }}</p> @enderror

                        <button type="submit" class="btn btn-primary btn-block auth-submit" wire:loading.attr="disabled"
                            wire:target="register">
                            <span wire:loading.remove wire:target="register">Create account</span>
                            <span wire:loading wire:target="register">Creating account...</span>
                        </button>
                    </form>
                    <p class="text-center text-muted small mt-4 mb-0">Already have an account? <a
                            href="{{ route('auth.login') }}">Sign in</a></p>
                </section>
            </div>
        </div>
    </div>
</div>