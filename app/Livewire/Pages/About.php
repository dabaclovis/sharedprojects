<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'About the Brotherfall Community and Tools Platform',
        'description' => 'Learn how Brotherfall brings together helpful tools, original community content and practical resources in one accessible platform.',
        'keywords' => 'about Brotherfall, community platform, free online tools, practical resources',
    ]
)]
class About extends Component
{
    public function render()
    {
        return view('livewire.pages.about');
    }
}
