<?php

namespace App\Livewire\Admins;

use App\Enums\PostCategory;
use App\Enums\QuoteCategory;
use App\Models\AffiliateProduct;
use App\Models\AdminDashboardLink;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Post;
use App\Models\Quote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Administration Dashboard | CD',
        'description' => 'Monitor users, content, products, events, quotes and messages across the CD platform.',
        'keywords' => 'admin dashboard, content administration, platform management',
    ]
)]
class Dashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $section = 'articles';

    public string $search = '';

    public string $status = '';

    #[Locked]
    public ?int $editingQuickLinkId = null;

    public bool $showQuickLinkEditor = false;

    public array $quickLinkFields = [];

    #[Locked]
    public ?int $editingQuoteId = null;

    public array $quoteFields = [];

    public function createQuickLink(): void
    {
        $this->closeQuickLinkEditor();
        $this->showQuickLinkEditor = true;
    }

    public function editQuickLink(int $id): void
    {
        $link = AdminDashboardLink::findOrFail($id);
        $this->closeQuickLinkEditor();
        $this->editingQuickLinkId = $link->id;
        $this->quickLinkFields = $link->only(['title', 'description', 'url']);
        $this->showQuickLinkEditor = true;
    }

    public function closeQuickLinkEditor(): void
    {
        $this->reset('editingQuickLinkId', 'showQuickLinkEditor', 'quickLinkFields');
        $this->resetValidation();
    }

    public function saveQuickLink(): void
    {
        foreach ($this->quickLinkFields as $field => $value) {
            if (is_string($value)) {
                $this->quickLinkFields[$field] = trim($value);
            }
        }

        $data = $this->validate([
            'quickLinkFields.title' => ['required', 'string', 'max:120'],
            'quickLinkFields.description' => ['nullable', 'string', 'max:500'],
            'quickLinkFields.url' => ['required', 'string', 'url', 'max:2048', 'regex:/^https?:\\/\\//i'],
        ]);

        $link = $this->editingQuickLinkId
            ? AdminDashboardLink::findOrFail($this->editingQuickLinkId)
            : new AdminDashboardLink;
        $link->fill($data['quickLinkFields'])->save();
        $this->closeQuickLinkEditor();
        session()->flash('quickLinkStatus', 'Quick link saved.');
    }

    public function deleteQuickLink(int $id): void
    {
        AdminDashboardLink::findOrFail($id)->delete();
        if ($this->editingQuickLinkId === $id) {
            $this->closeQuickLinkEditor();
        }
        session()->flash('quickLinkStatus', 'Quick link removed.');
    }

    public function editQuote(int $id): void
    {
        $quote = Quote::findOrFail($id);
        $this->closeReview();
        $this->editingQuoteId = $quote->id;
        $this->quoteFields = $quote->only(['title', 'content', 'author', 'source', 'tags', 'category', 'language', 'licon', 'ricon']);
        $this->quoteFields['licon'] ??= 'fa-quote-left';
        $this->quoteFields['ricon'] ??= 'fa-quote-right';
    }

    public function closeQuoteEditor(): void
    {
        $this->reset('editingQuoteId', 'quoteFields');
        $this->resetValidation();
    }

    public function saveQuote(): void
    {
        $quote = Quote::findOrFail($this->editingQuoteId);
        foreach ($this->quoteFields as $field => $value) {
            if (is_string($value)) {
                $this->quoteFields[$field] = trim($value);
            }
        }
        $rules = ['quoteFields.content' => ['required', 'string', 'max:250']];
        foreach (['title', 'author', 'source', 'tags', 'language'] as $field) {
            $rules['quoteFields.' . $field] = ['nullable', 'string', 'max:255'];
        }
        $rules['quoteFields.category'] = ['nullable', Rule::enum(QuoteCategory::class)];
        foreach (['licon', 'ricon'] as $field) {
            $rules['quoteFields.' . $field] = ['required', Rule::in(array_keys(Quote::ICONS))];
        }
        $data = $this->validate($rules);
        $quote->fill(Arr::only($data['quoteFields'], ['title', 'content', 'author', 'source', 'tags', 'category', 'language', 'licon', 'ricon']))->save();
        $this->closeQuoteEditor();
        session()->flash('quoteStatus', 'Quote updated.');
    }

    #[Locked]
    public ?int $reviewPostId = null;

    #[Locked]
    public ?string $reviewRevision = null;

    public bool $reviewPostEditing = false;

    public array $reviewPostFields = [];

    public string $remarkMessage = '';

    public function sendRemark(): void
    {
        $this->remarkMessage = trim($this->remarkMessage);
        $this->validate(['remarkMessage' => ['required', 'string', 'max:5000']]);
        $post = Post::findOrFail($this->reviewPostId);
        $remark = $post->remarks()->make(['message' => $this->remarkMessage]);
        $remark->admin()->associate(auth()->user());
        $remark->save();
        $this->reset('remarkMessage');
        session()->flash('remarkStatus', 'Message sent to the author’s articles page.');
    }

    public function reviewArticle(int $id): void
    {
        $this->reset('remarkMessage');
        $this->cancelReviewedArticleEdit();
        $this->resetValidation();
        $this->closeQuoteEditor();
        $post = Post::findOrFail($id);
        $this->reviewPostId = $post->id;
        $this->reviewRevision = $this->reviewFingerprint($post);
    }

    public function editReviewedArticle(): void
    {
        abort_unless($this->reviewPostId, 404);
        $post = Post::findOrFail($this->reviewPostId);
        if ($this->reviewRevision !== $this->reviewFingerprint($post)) {
            $this->addError('reviewConflict', 'This article changed or was deleted. Close this review and reopen the article before taking action.');

            return;
        }
        $this->reviewPostFields = $post->only(['title', 'content', 'category', 'icon']);
        $this->reviewPostEditing = true;
        $this->resetValidation();
    }

    public function cancelReviewedArticleEdit(): void
    {
        $this->reset('reviewPostEditing', 'reviewPostFields');
        $this->resetValidation();
    }

    public function saveReviewedArticle(): void
    {
        abort_unless($this->reviewPostId && $this->reviewPostEditing, 404);
        foreach ($this->reviewPostFields as $field => $value) {
            if (is_string($value)) {
                $this->reviewPostFields[$field] = trim($value);
            }
        }
        $data = $this->validate([
            'reviewPostFields.title' => ['required', 'string', 'max:255'],
            'reviewPostFields.content' => ['required', 'string', 'max:100000'],
            'reviewPostFields.category' => ['nullable', Rule::enum(PostCategory::class)],
            'reviewPostFields.icon' => ['nullable', Rule::in(array_keys(Post::ICONS))],
        ]);
        $saved = DB::transaction(function () use ($data) {
            $post = Post::lockForUpdate()->find($this->reviewPostId);
            if (! $post || $this->reviewRevision !== $this->reviewFingerprint($post)) {
                $this->addError('reviewConflict', 'This article changed or was deleted. Close this review and reopen the article before taking action.');

                return false;
            }
            $fields = $data['reviewPostFields'];
            $post->fill(Arr::only($fields, ['title', 'content', 'category', 'icon']));
            $post->excerpt = Str::limit(preg_replace('/\s+/u', ' ', trim(strip_tags($fields['content']))), 180);
            $post->save();

            return $post->refresh();
        });
        if (! $saved) {
            return;
        }
        $this->reviewRevision = $this->reviewFingerprint($saved);
        $this->cancelReviewedArticleEdit();
        session()->flash('adminPostStatus', 'Article updated by admin.');
    }

    public function closeReview(): void
    {
        $this->reset('remarkMessage');
        $this->cancelReviewedArticleEdit();
        $this->resetValidation();
        $this->reviewPostId = null;
        $this->reviewRevision = null;
    }

    private function reviewFingerprint(Post $post): string
    {
        return hash('sha256', json_encode($post->getRawOriginal(), JSON_THROW_ON_ERROR));
    }

    public function publishReviewedArticle(): void
    {
        if (! $this->changeReviewedStatus('published')) {
            return;
        }
        $this->closeReview();
        session()->flash('approvalStatus', 'Article approved and published.');
    }

    public function archiveReviewedArticle(): void
    {
        if (! $this->changeReviewedStatus('archived')) {
            return;
        }
        $this->closeReview();
        session()->flash('approvalStatus', 'Article archived.');
    }

    private function changeReviewedStatus(string $status): bool
    {
        return DB::transaction(function () use ($status) {
            $post = Post::lockForUpdate()->find($this->reviewPostId);
            if (! $post || $this->reviewRevision !== $this->reviewFingerprint($post)) {
                $this->addError('reviewConflict', 'This article changed or was deleted. Close this review and reopen the article before taking action.');

                return false;
            }
            $post->status = $status;
            $post->published_at = $status === 'published' ? ($post->published_at && $post->published_at->lte(now()) ? $post->published_at : now()) : null;
            $post->save();

            return true;
        });
    }

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'admin' && auth()->user()->status === 'active', 403);
    }

    public function updatedSection(): void
    {
        $this->closeReview();
        $this->closeQuoteEditor();
        $this->reset('search', 'status');
        $this->resetPage();
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

    public function approveArticle(int $id): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'admin' && auth()->user()->status === 'active', 403);
        if ($this->reviewPostId === $id) {
            $this->publishReviewedArticle();

            return;
        }
        $updated = Post::whereKey($id)->where('status', 'draft')->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        abort_unless($updated, 404);
        session()->flash('approvalStatus', 'Article approved and published.');
    }

    public function render()
    {
        // Client state must not select arbitrary models or relationships.
        $section = in_array($this->section, ['articles', 'products', 'quotes'], true) ? $this->section : 'articles';
        $query = match ($section) {
            'products' => AffiliateProduct::query()->with('user:id,name,status')->select(['id', 'user_id', 'title', 'status', 'clicks', 'updated_at']),
            'quotes' => Quote::query()->select(['id', 'title', 'content', 'author', 'source', 'category', 'language', 'updated_at']),
            default => Post::query()->with('author:id,name')->select(['id', 'author_id', 'title', 'slug', 'status', 'published_at', 'updated_at']),
        };
        $search = mb_substr(trim($this->search), 0, 200);
        $query->when($search !== '', fn($query) => $query->where(function ($query) use ($search, $section) {
            $query->where('title', 'like', '%' . $search . '%');
            if ($section === 'quotes') {
                $query->orWhere('content', 'like', '%' . $search . '%')->orWhere('author', 'like', '%' . $search . '%');
            }
        }))->when($section !== 'quotes' && in_array($this->status, ['draft', 'published', 'archived'], true), fn($query) => $query->where('status', $this->status));

        $now = CarbonImmutable::now('UTC');
        $cutoff = $now->subDays(30);
        $users = User::query()->selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active, SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent',
            ['active', $cutoff]
        )->first();
        $posts = Post::query()->selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as drafts, SUM(CASE WHEN status = ? AND published_at IS NOT NULL AND published_at <= ? THEN 1 ELSE 0 END) as live',
            ['draft', 'published', $now]
        )->first();
        $products = AffiliateProduct::query()->selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as drafts, COALESCE(SUM(clicks), 0) as clicks',
            ['draft']
        )->first();
        $events = Event::query()->selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN status = ? AND starts_at >= ? THEN 1 ELSE 0 END) as upcoming',
            ['scheduled', $now]
        )->first();
        $quotes = Quote::query()->selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent',
            [$cutoff]
        )->first();
        $contacts = ContactMessage::query()->selectRaw(
            'COUNT(*) as total, SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as recent',
            [$cutoff]
        )->first();

        return view('livewire.admins.dashboard', [
            'reviewPost' => $this->reviewPostId ? Post::with(['author:id,name', 'remarks.admin:id,name'])->find($this->reviewPostId) : null,
            'quickLinks' => AdminDashboardLink::orderBy('id')->get(),
            'activeSection' => $section,
            'records' => $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(8),
            'stats' => [
                'users' => (int) $users->total,
                'activeUsers' => (int) $users->active,
                'newUsers' => (int) $users->recent,
                'articles' => (int) $posts->total,
                'liveArticles' => (int) $posts->live,
                'draftArticles' => (int) $posts->drafts,
                'products' => (int) $products->total,
                'liveProducts' => AffiliateProduct::published()->count(),
                'draftProducts' => (int) $products->drafts,
                'clicks' => (int) $products->clicks,
                'events' => (int) $events->total,
                'upcomingEvents' => (int) $events->upcoming,
                'quotes' => (int) $quotes->total,
                'newQuotes' => (int) $quotes->recent,
                'contactMessages' => (int) $contacts->total,
                'newContactMessages' => (int) $contacts->recent,
            ],
            'recentUsers' => User::latest('id')->limit(5)->get(['id', 'name', 'email', 'role', 'status', 'created_at']),
            'contactMessages' => ContactMessage::latest('id')
                ->paginate(5, ['id', 'name', 'email', 'subject', 'message', 'created_at'], 'contactsPage'),
            'scheduledEvents' => Event::with('user:id,name')->where('status', 'scheduled')
                ->orderBy('starts_at')->orderBy('id')
                ->paginate(8, ['id', 'user_id', 'title', 'starts_at', 'ends_at', 'timezone'], 'eventsPage'),
        ]);
    }
}
