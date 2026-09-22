<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class RegisterForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): User
    {
        $this->name = Str::squish($this->name);
        $this->email = strtolower(trim($this->email));

        $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (count(preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY)) !== 2) {
                        $fail('The full name must contain exactly two words (first and last name).');
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('The password must not exceed 72 bytes.');
                    }
                },
            ],
        ]);

        return User::create([
            'name' => $this->name,
            'username' => $this->generateUsername(),
            'email' => $this->email,
            'password' => $this->password,
        ]);
    }

    private function generateUsername(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY);
        $firstName = $parts[0];
        $lastName = $parts[count($parts) - 1];
        $base = count($parts) > 1 ? Str::substr($firstName, 0, 1).$lastName : $firstName;
        $base = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($base)));
        $base = substr($base ?: 'user', 0, 240);
        $number = 1;

        do {
            $username = $base.str_pad((string) $number++, 2, '0', STR_PAD_LEFT);
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
