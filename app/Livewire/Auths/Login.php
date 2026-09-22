<?php

namespace App\Livewire\Auths;

use App\Livewire\Forms\LoginForm;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Sign in',
        'description' => 'Sign in to your account',
        'keywords' => 'login, sign in',
    ]
)]
class Login extends Component
{
    use WithRateLimiting;

    public LoginForm $form;

    public function login(): void
    {
        if (! Auth::check()) {
            $this->resetValidation();

            try {
                $this->rateLimit(5);
            } catch (TooManyRequestsException $exception) {
                $this->addError('login', "Too many attempts. Please try again in {$exception->secondsUntilAvailable} seconds.");

                return;
            }

            $this->form->authenticate();
            $this->clearRateLimiter();
        }

        $this->redirectRoute(Auth::user()->role === 'admin' ? 'admins.index' : 'users.index');
    }

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectRoute(Auth::user()->role === 'admin' ? 'admins.index' : 'users.index');
        }
    }

    public function render()
    {
        return view()->file(resource_path('views/livewire/auths/login.blade.php'));
    }
}
