<?php

namespace App\Livewire\Pages;

use App\Models\ServiceOrder;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Home')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.pages.index', [
            'sponsors' => ServiceOrder::liveSponsors()->orderBy('starts_at')->orderBy('id')
                ->get(['id', 'sponsor_name', 'sponsor_title', 'sponsor_description', 'sponsor_url']),
        ]);
    }
}
