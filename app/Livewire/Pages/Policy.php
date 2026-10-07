<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Privacy, Cookies and Advertising Policy | Brotherfall',
        'description' => 'Learn how Brotherfall handles personal information, cookies, advertising choices, affiliate links and community content.',
        'keywords' => 'privacy policy, advertising cookies, consent, affiliate disclosure, community content',
    ]
)]
class Policy extends Component
{
    public function render()
    {
        return view('livewire.pages.policy');
    }
}
