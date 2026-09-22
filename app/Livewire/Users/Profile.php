<?php

namespace App\Livewire\Users;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Your profile')]
class Profile extends Component
{
    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function render()
    {
        return view('livewire.users.profile', ['user' => Auth::user()]);
    }
}
