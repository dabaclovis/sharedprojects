<?php

namespace App\Livewire\Admins;

use App\Enums\QuoteCategory;
use App\Models\AffiliateProduct;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Post;
use App\Models\Quote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Admin dashboard')]
class Dashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $section = 'articles';

    public string $search = '';

    public string $status = '';

    #[Locked]
    public ?int $editingQuoteId = null;

    public array $quoteFields = [];

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
            $rules['quoteFields.'.$field] = ['nullable', 'string', 'max:255'];
        }
        $rules['quoteFields.category'] = ['nullable', Rule::enum(QuoteCategory::class)];
        foreach (['licon', 'ricon'] as $field) {
            $rules['quoteFields.'.$field] = ['required', Rule::in(array_keys(Quote::ICONS))];
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
        $this->resetValidation();
        $this->closeQuoteEditor();
        $post = Post::findOrFail($id);
        $this->reviewPostId = $post->id;
        $this->reviewRevision = hash('sha256', json_encode($post->getRawOriginal(), JSON_THROW_ON_ERROR));
    }

    public function closeReview(): void
    {
        $this->reset('remarkMessage');
        $this->resetValidation();
        $this->reviewPostId = null;
        $this->reviewRevision = null;
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
            if (! $post || $this->reviewRevision !== hash('sha256', json_encode($post->getRawOriginal(), JSON_THROW_ON_ERROR))) {
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
        $query->when($search !== '', fn ($query) => $query->where(function ($query) use ($search, $section) {
            $query->where('title', 'like', '%'.$search.'%');
            if ($section === 'quotes') {
                $query->orWhere('content', 'like', '%'.$search.'%')->orWhere('author', 'like', '%'.$search.'%');
            }
        }))->when($section !== 'quotes' && in_array($this->status, ['draft', 'published', 'archived'], true), fn ($query) => $query->where('status', $this->status));

        $users = User::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $posts = Post::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $products = AffiliateProduct::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $now = CarbonImmutable::now('UTC');

        return view('livewire.admins.dashboard', [
            'reviewPost' => $this->reviewPostId ? Post::with(['author:id,name', 'remarks.admin:id,name'])->find($this->reviewPostId) : null,
            'activeSection' => $section,
            'records' => $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(8),
            'stats' => [
                'users' => (int) $users->sum(),
                'activeUsers' => (int) ($users['active'] ?? 0),
                'newUsers' => User::where('created_at', '>=', $now->subDays(30))->count(),
                'articles' => (int) $posts->sum(),
                'liveArticles' => Post::published()->count(),
                'draftArticles' => (int) ($posts['draft'] ?? 0),
                'products' => (int) $products->sum(),
                'liveProducts' => AffiliateProduct::published()->count(),
                'draftProducts' => (int) ($products['draft'] ?? 0),
                'clicks' => (int) AffiliateProduct::sum('clicks'),
                'events' => Event::count(),
                'upcomingEvents' => Event::where('status', 'scheduled')->where('starts_at', '>=', $now)->count(),
                'quotes' => Quote::count(),
                'newQuotes' => Quote::where('created_at', '>=', $now->subDays(30))->count(),
                'contactMessages' => ContactMessage::count(),
                'newContactMessages' => ContactMessage::where('created_at', '>=', $now->subDays(30))->count(),
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
