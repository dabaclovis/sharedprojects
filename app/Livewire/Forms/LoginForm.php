<?php

namespace App\Livewire\Forms;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class LoginForm extends Form
{
    public string $identifier = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(): void
    {
        $this->identifier = trim($this->identifier);
        $this->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $field = str_contains($this->identifier, '@') ? 'email' : 'username';
        $identifier = $field === 'email' ? strtolower($this->identifier) : $this->identifier;

        if (! Auth::attempt([$field => $identifier, 'password' => $this->password, 'status' => 'active'], $this->remember)) {
            $user = \App\Models\User::where($field, $identifier)->where('status', 'suspended')->first();
            if ($user && \Illuminate\Support\Facades\Hash::check($this->password, $user->password)) {
                $this->reset('password');
                throw ValidationException::withMessages(['form.identifier' => $user->suspensionMessage()]);
            }
            throw ValidationException::withMessages([
                'form.identifier' => 'These credentials do not match an active account.',
            ]);
        }

        session()->regenerate();
        $this->reset('password');
    }
}
