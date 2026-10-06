<?php

namespace App\Livewire\Users;

use App\Models\AdminDashboardLink;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Your Content Dashboard | Brotherfall',
        'description' => 'Review your content activity, publication status, products and upcoming events.',
        'keywords' => 'user dashboard, content dashboard, account overview',
    ]
)]
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
        $search = mb_substr(trim($this->search), 0, 200);
        $counts = $user->posts()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $postRewardsQuery = $user->rewards()->where('content_type', 'post');
        $postRewards = (clone $postRewardsQuery)->latest()->limit(5)->get();
        $postTitles = $user->posts()->whereIn('id', $postRewards->pluck('content_id'))
            ->pluck('title', 'id');
        $postRewards->each(fn($reward) => $reward->setAttribute('post_title', $postTitles->get($reward->content_id)));
        $posts = $user->posts()
            ->when($search !== '', fn($query) => $query->where('title', 'like', '%' . $search . '%'))
            ->when(in_array($this->status, ['draft', 'published', 'archived'], true), fn($query) => $query->where('status', $this->status))
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(5, ['id', 'title', 'excerpt', 'content', 'category', 'status', 'published_at', 'updated_at']);

        return view('livewire.users.index', [
            'user' => $user,
            'quickLinks' => AdminDashboardLink::orderBy('id')->get(),
            'posts' => $posts,
            'postEarningsCents' => (clone $postRewardsQuery)->sum('amount_cents'),
            'postRewardCount' => (clone $postRewardsQuery)->count(),
            'postRewards' => $postRewards,
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
