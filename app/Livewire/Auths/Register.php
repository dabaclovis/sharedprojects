<?php

namespace App\Livewire\Auths;

use App\Livewire\Forms\RegisterForm;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Create an account',
        'description' => 'Register a new account',
        'keywords' => 'register, sign up, create account',
    ]
)]
class Register extends Component
{
    use WithRateLimiting;

    public RegisterForm $form;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectRoute(Auth::user()->role === 'admin' ? 'admins.index' : 'users.index');
        }
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirectRoute(Auth::user()->role === 'admin' ? 'admins.index' : 'users.index');

            return;
        }

        $this->resetValidation();

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('registration', "Too many attempts. Please try again in {$exception->secondsUntilAvailable} seconds.");

            return;
        }

        $user = $this->form->register();
        event(new Registered($user));
        $this->form->reset();

        session()->flash('status', "Account created. Your username is {$user->username}. Please sign in.");
        $this->redirectRoute('auth.login');
    }

    public function render()
    {
        return view()->file(resource_path('views/livewire/auths/register.blade.php'));
    }
}
