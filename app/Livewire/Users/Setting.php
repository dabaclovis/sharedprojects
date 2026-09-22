<?php

namespace App\Livewire\Users;

use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[Title('Account settings')]
class Setting extends Component
{
    public string $name = '';
    public string $email = '';
    public string $currentPassword = '';
    public string $passwordCurrent = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    private function allowAttempt(): bool
    {
        $key = 'settings:'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('settings', 'Too many attempts. Please wait a minute and try again.');
            return false;
        }
        RateLimiter::hit($key, 60);
        return true;
    }

    public function saveProfile(): void
    {
        $this->resetValidation();
        if (! $this->allowAttempt()) {
            return;
        }
        $this->name = Str::squish($this->name);
        $this->email = strtolower(trim($this->email));
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^\S+\s\S+$/u'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(Auth::id())],
            'currentPassword' => ['required', 'current_password:web'],
        ], ['name.regex' => 'Please enter exactly two words: your first and last name.']);
        $user = Auth::user();
        if ($user->email !== $this->email) {
            $user->email_verified_at = null;
        }
        $user->name = $this->name;
        $user->email = $this->email;
        $user->save();
        $this->reset('currentPassword');
        session()->flash('profileStatus', 'Account details updated. Your username stays the same.');
    }

    public function savePassword(): void
    {
        $this->resetValidation();
        if (! $this->allowAttempt()) {
            return;
        }
        $this->validate([
            'passwordCurrent' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:passwordCurrent', function ($attribute, $value, $fail) {
                if (strlen($value) > 72) {
                    $fail('The password must not exceed 72 bytes.');
                }
            }],
        ]);
        $user = Auth::user();
        $user->password = $this->password;
        $user->setRememberToken(Str::random(60));
        $user->save();
        session()->regenerate();
        $this->reset('passwordCurrent', 'password', 'password_confirmation');
        session()->flash('passwordStatus', 'Your password has been updated.');
    }

    public function render()
    {
        return view('livewire.users.setting');
    }
}
