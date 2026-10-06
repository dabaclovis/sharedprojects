<?php

namespace App\Livewire\Users;

use App\Enums\PostCategory;
use App\Models\Post;
use App\Models\Reward;
use App\Models\RewardFund;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Manage Your Articles | Brotherfall',
        'description' => 'Write, revise, submit and manage your community articles from one workspace.',
        'keywords' => 'manage articles, write articles, content workspace',
    ]
)]
class Articles extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Locked]
    public ?int $postId = null;

    public bool $showEditor = false;

    public string $search = '';

    public string $filter = '';

    public string $sort = 'updated';

    #[Locked]
    public ?string $revision = null;

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'filter', 'sort');
        $this->resetPage();
    }

    private function fingerprint(Post $post): string
    {
        return hash('sha256', json_encode($post->getRawOriginal(), JSON_THROW_ON_ERROR));
    }

    public string $title = '';

    public string $slug = '';

    public string $seo_title = '';

    public string $meta_description = '';

    public string $featured_image = '';

    public string $image_alt = '';

    public string $target_keyword = '';

    public string $tags = '';

    public string $content = '';

    public string $category = '';

    public string $icon = '';

    public string $status = 'draft';

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->cancel();
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $post = Auth::user()->posts()->findOrFail($id);
        $this->cancel();
        $this->postId = $post->id;
        $this->revision = $this->fingerprint($post);
        foreach (['title', 'content', 'category', 'icon', 'status', 'slug', 'seo_title', 'meta_description', 'featured_image', 'image_alt', 'target_keyword'] as $field) {
            $this->{$field} = $post->{$field} ?? '';
        }
        $this->tags = implode(', ', $post->tags ?? []);
        $this->showEditor = true;
    }

    public function cancel(): void
    {
        $this->reset('postId', 'revision', 'showEditor', 'title', 'content', 'category', 'icon', 'status', 'slug', 'seo_title', 'meta_description', 'featured_image', 'image_alt', 'target_keyword', 'tags');
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->title = trim($this->title);
        $this->content = trim($this->content);
        $this->slug = trim($this->slug) ?: ($this->postId ? Auth::user()->posts()->findOrFail($this->postId)->slug : Post::availableSlug($this->title));
        $data = $this->validate([
            'slug' => ['required', 'max:220', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($this->postId), Rule::unique('post_slug_aliases', 'slug')],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'featured_image' => ['nullable', 'url:http,https', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:255', 'required_with:featured_image'],
            'target_keyword' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:100000'],
            'category' => ['nullable', Rule::enum(PostCategory::class)],
            'icon' => ['nullable', Rule::in(array_keys(Post::ICONS))],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
        $data['tags'] = array_values(array_unique(array_filter(array_map('trim', explode(',', $this->tags)))));
        $result = DB::transaction(function () use ($data) {
            $post = $this->postId ? Auth::user()->posts()->lockForUpdate()->find($this->postId) : new Post;
            if (! $post || ($post->exists && $this->revision !== $this->fingerprint($post))) {
                $this->addError('conflict', 'This article was changed or deleted after you opened it. Copy your edits, then reopen the article to load the latest version.');

                return null;
            }
            $post->fill(collect($data)->except('status')->all());
            $post->excerpt = Str::limit(preg_replace('/\s+/u', ' ', trim(strip_tags($this->content))), 180);
            if (! $post->exists) {
                $post->author()->associate(Auth::user());
                $post->postsable()->associate(Auth::user());
            }
            // New articles and revisions must be reviewed before becoming public.
            $post->status = 'draft';
            $post->published_at = null;
            $post->save();

            return ['rewardAmountCents' => $this->awardEligiblePost($post)];
        });
        if ($result === null) {
            return;
        }
        $this->cancel();
        $this->resetPage();
        session()->flash('articleStatus', $result['rewardAmountCents'] > 0
            ? 'Article saved as a draft awaiting admin approval. Reward added: $'.number_format($result['rewardAmountCents'] / 100, 2).'.'
            : 'Article saved as a draft awaiting admin approval.');
    }

    private function awardEligiblePost(Post $post): int
    {
        if (! $post->isEligibleForReward() || Reward::where(['content_type' => 'post', 'content_id' => $post->id])->exists()) {
            return 0;
        }

        $fund = RewardFund::query()->lockForUpdate()->find(1);
        $amount = (int) ($fund?->post_reward_cents ?? 0);
        if (! $fund || $amount < 1 || $fund->balance_cents < $amount) {
            return 0;
        }

        $reward = new Reward(['content_type' => 'post', 'content_id' => $post->id, 'amount_cents' => $amount]);
        $reward->user()->associate($post->author_id);
        $reward->save();
        $fund->decrement('balance_cents', $amount);

        return $amount;
    }

    public function delete(int $id): void
    {
        Auth::user()->posts()->findOrFail($id)->delete();
        $this->cancel();
        $this->resetPage();
        session()->flash('articleStatus', 'Article moved to trash. You can restore it from the Trash filter.');
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id) {
            $post = Auth::user()->posts()->onlyTrashed()->lockForUpdate()->findOrFail($id);
            $post->status = 'draft';
            $post->published_at = null;
            $post->restore();
        });
        $this->resetPage();
        session()->flash('articleStatus', 'Article restored as a draft awaiting admin approval.');
    }

    public function archive(int $id): void
    {
        Auth::user()->posts()->findOrFail($id)->forceFill(['status' => 'archived', 'published_at' => null])->save();
        $this->resetPage();
        session()->flash('articleStatus', 'Article archived and removed from public view.');
    }

    public function render()
    {
        $counts = Auth::user()->posts()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $query = Auth::user()->posts()
            ->select(['id', 'author_id', 'title', 'excerpt', 'status', 'published_at', 'updated_at', 'deleted_at'])
            ->with('remarks.admin:id,name')
            ->when($this->filter === 'trash', fn ($query) => $query->onlyTrashed())
            ->when(in_array($this->filter, ['draft', 'published', 'archived'], true), fn ($query) => $query->where('status', $this->filter))
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%'.mb_substr(trim($this->search), 0, 200).'%'));
        match ($this->sort) {
            'title' => $query->orderBy('title'),
            'oldest' => $query->orderBy('updated_at'),
            default => $query->orderByDesc('updated_at'),
        };

        return view('livewire.users.articles', [
            'counts' => $counts,
            'trashCount' => Auth::user()->posts()->onlyTrashed()->count(),
            'posts' => $query->orderByDesc('id')->paginate(10),
        ]);
    }
}
