<?php

namespace App\Livewire\Admins;

use App\Models\ApplicationRating;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Application Ratings and Feedback | CD Admin',
        'description' => 'Review private application ratings and user feedback for service improvement.',
        'keywords' => 'application ratings, user feedback, admin reviews',
    ]
)]
class Ratings extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function boot(): void
    {
        abort_unless(auth()->user()?->role === 'admin' && auth()->user()?->status === 'active', 403);
    }

    public function render()
    {
        return view('livewire.admins.ratings', [
            'summary' => ApplicationRating::selectRaw('COUNT(*) as total, AVG(score) as average')->first(),
            'ratings' => ApplicationRating::with('user:id,name')->latest('id')->paginate(20),
        ])->title('Visitor ratings');
    }
}
