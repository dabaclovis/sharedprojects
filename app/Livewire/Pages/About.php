<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'About the CD Community and Tools Platform',
        'description' => 'Learn how CD brings together helpful tools, original community content and practical resources in one accessible platform.',
        'keywords' => 'about CD, community platform, free online tools, practical resources',
    ]
)]
class About extends Component
{
    public function render()
    {
        return view('livewire.pages.about');
    }
}
