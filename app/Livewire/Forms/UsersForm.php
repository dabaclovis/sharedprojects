<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class UsersForm extends Form
{
    #[Locked]
    public ?int $userId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'user';

    public string $status = 'active';

    public string $suspension_reason = '';

    public function setUser(User $user): void
    {
        $this->reset();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username ?? '';
        $this->email = $user->email;
        $this->role = $user->role;
        $this->status = $user->status;
        $this->suspension_reason = $user->suspension_reason ?? '';
    }

    public function save(): bool
    {
        $this->name = trim($this->name);
        $this->username = trim($this->username);
        $this->email = strtolower(trim($this->email));
        $this->suspension_reason = trim($this->suspension_reason);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => [
                $this->userId ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:72',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('The password must not exceed 72 bytes.');
                    }
                },
            ],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'suspension_reason' => ['required_if:status,suspended', 'nullable', 'string', 'max:2000'],
        ]);

        if ($this->userId === Auth::id() && ($this->role !== 'admin' || $this->status !== 'active')) {
            $this->addError('role', 'Your own account must remain an active admin.');

            return false;
        }

        $user = $this->userId ? User::findOrFail($this->userId) : new User;
        $user->name = $this->name;
        $user->username = $this->username !== '' ? $this->username : null;
        if ($user->email !== $this->email) {
            $user->email_verified_at = null;
        }
        $user->email = $this->email;
        $user->role = $this->role;
        $user->status = $this->status;
        $user->suspension_reason = $this->status === 'suspended' ? $this->suspension_reason : null;
        if ($this->password !== '') {
            $user->password = $this->password;
        }
        $user->save();

        return true;
    }
}
