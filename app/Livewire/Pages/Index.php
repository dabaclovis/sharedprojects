<?php

namespace App\Livewire\Pages;

use App\Models\ServiceOrder;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'CD Community Hub | Free Tools, Articles and Resources',
        'description' => 'Discover free online tools, useful community articles, product recommendations and practical business resources in one place.',
        'keywords' => 'free online tools, community articles, business resources, product recommendations',
    ]
)]
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
