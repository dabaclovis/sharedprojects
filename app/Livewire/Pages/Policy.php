<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Privacy and community policy')]
class Policy extends Component
{
    public function render()
    {
        return view('livewire.pages.policy');
    }
}
