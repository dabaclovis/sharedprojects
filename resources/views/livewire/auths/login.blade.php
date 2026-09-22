<div class="auth-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <section class="card auth-card border-0 p-4 p-md-5" aria-labelledby="login-heading">
                    <div class="text-center mb-4">
                        <span class="auth-symbol mb-3" aria-hidden="true"><i
                                class="fa-solid fa-right-to-bracket"></i></span>
                        <h1 id="login-heading" class="h3 font-weight-bold">Welcome back</h1>
                        <p class="text-muted mb-0">Sign in to continue to your account.</p>
                    </div>

                    @if (session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    <form wire:submit="login" novalidate>
                        <div class="form-group">
                            <label for="login-identifier">Email or username</label>
                            <x-forms.inputs id="login-identifier" type="text" wire:model="form.identifier"
                                placeholder="Email address or username"
                                :class="$errors->has('form.identifier') ? 'form-control is-invalid' : 'form-control'"
                                autocomplete="username" maxlength="255" required autofocus
                                :aria-invalid="$errors->has('form.identifier') ? 'true' : 'false'"
                                :aria-describedby="$errors->has('form.identifier') ? 'login-identifier-error' : null" />
                            @error('form.identifier') <span id="login-identifier-error" class="invalid-feedback"
                                role="alert">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <x-forms.inputs id="login-password" type="password" wire:model="form.password"
                                placeholder="Enter your password"
                                :class="$errors->has('form.password') ? 'form-control is-invalid' : 'form-control'"
                                autocomplete="current-password" required
                                :aria-invalid="$errors->has('form.password') ? 'true' : 'false'"
                                :aria-describedby="$errors->has('form.password') ? 'login-password-error' : null" />
                            @error('form.password') <span id="login-password-error" class="invalid-feedback"
                                role="alert">{{ $message }}</span> @enderror
                        </div>
                        <div class="custom-control custom-checkbox mb-4">
                            <input id="login-remember" type="checkbox" wire:model="form.remember"
                                class="custom-control-input">
                            <label class="custom-control-label" for="login-remember">Remember me</label>
                        </div>
                        @error('login') <p class="text-danger" role="alert">{{ $message }}</p> @enderror
                        <button type="submit" class="btn btn-primary btn-block auth-submit" wire:loading.attr="disabled"
                            wire:target="login">
                            <span wire:loading.remove wire:target="login">Sign in</span>
                            <span wire:loading wire:target="login">Signing in...</span>
                        </button>
                    </form>
                    <p class="text-center text-muted small mt-4 mb-0">New here? <a
                            href="{{ route('auth.register') }}">Create an account</a></p>
                </section>
            </div>
        </div>
    </div>
</div>
