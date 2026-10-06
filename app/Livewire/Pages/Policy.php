<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Privacy, Content and Affiliate Policy | Brotherfall',
        'description' => 'Review Brotherfall policies covering privacy, submitted content, affiliate links, acceptable use and platform responsibilities.',
        'keywords' => 'privacy policy, content policy, affiliate disclosure, acceptable use',
    ]
)]
class Policy extends Component
{
    public function render()
    {
        return view('livewire.pages.policy');
    }
}
