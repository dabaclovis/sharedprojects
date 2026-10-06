<?php

namespace App\Livewire\Users;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Your Brotherfall Profile',
        'description' => 'Review your Brotherfall account identity and profile information.',
        'keywords' => 'user profile, Brotherfall account, profile details',
    ]
)]
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
