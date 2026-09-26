<?php

namespace App\Livewire\Users;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Your dashboard')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $status = '';

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $counts = $user->posts()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $posts = $user->posts()
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($this->search).'%'))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(5, ['id', 'title', 'excerpt', 'category', 'status', 'published_at', 'updated_at']);

        return view('livewire.users.index', [
            'user' => $user,
            'posts' => $posts,
            'productCount' => $user->affiliateProducts()->count(),
            'upcomingEvents' => $user->events()->where('status', 'scheduled')
                ->where('ends_at', '>=', now('UTC'))->orderBy('starts_at')->limit(3)
                ->get(['id', 'title', 'starts_at', 'timezone']),
            'stats' => [
                'Total posts' => $counts->sum(),
                'Drafts' => (int) ($counts['draft'] ?? 0),
                'Published / scheduled' => (int) ($counts['published'] ?? 0),
                'Archived' => (int) ($counts['archived'] ?? 0),
            ],
        ]);
    }
}
