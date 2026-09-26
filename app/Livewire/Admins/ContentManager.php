<?php

namespace App\Livewire\Admins;

use App\Models\AffiliateProduct;
use App\Models\Event;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ContentManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Locked]
    public string $kind = 'articles';

    #[Locked]
    public ?int $recordId = null;

    #[Locked]
    public ?string $revision = null;

    public string $search = '';

    public string $status = '';

    public string $remark = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->role === 'admin' && auth()->user()?->status === 'active', 403);
    }

    public function mount(string $kind = 'articles'): void
    {
        abort_unless(in_array($kind, ['articles', 'products', 'events'], true), 404);
        $this->kind = $kind;
    }

    private function query(): Builder
    {
        return match ($this->kind) {
            'articles' => Post::query(),
            'products' => AffiliateProduct::query(),
            'events' => Event::query(),
            default => abort(404),
        };
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

    public function review(int $id): void
    {
        $record = $this->query()->withTrashed()->findOrFail($id);
        $this->close();
        $this->recordId = $record->id;
        $this->revision = hash('sha256', json_encode($record->getRawOriginal(), JSON_THROW_ON_ERROR));
    }

    public function close(): void
    {
        $this->reset('recordId', 'revision', 'remark');
        $this->resetValidation();
    }

    private function change(string $action, callable $callback): void
    {
        DB::transaction(function () use ($callback) {
            $record = $this->query()->withTrashed()->lockForUpdate()->findOrFail($this->recordId);
            if ($this->revision !== hash('sha256', json_encode($record->getRawOriginal(), JSON_THROW_ON_ERROR))) {
                throw ValidationException::withMessages(['review' => 'This record changed. Close and reopen it before taking action.']);
            }
            $callback($record);
        });
        $this->close();
        $this->resetPage();
        session()->flash('managementStatus', $action);
    }

    public function publish(): void
    {
        abort_unless(in_array($this->kind, ['articles', 'products'], true), 403);
        $this->change('Content approved and published.', function ($record) {
            abort_if($record->trashed(), 404);
            $record->status = 'published';
            if ($record instanceof Post) {
                $record->published_at = now();
            }
            $record->save();
        });
    }

    public function archive(): void
    {
        $this->change($this->kind === 'events' ? 'Event cancelled.' : 'Content archived.', function ($record) {
            abort_if($record->trashed(), 404);
            $record->status = $this->kind === 'events' ? 'cancelled' : 'archived';
            if ($record instanceof Post) {
                $record->published_at = null;
            }
            $record->save();
        });
    }

    public function trash(): void
    {
        $this->change('Record moved to trash.', function ($record) {
            abort_if($record->trashed(), 404);
            $record->delete();
        });
    }

    public function restore(): void
    {
        $this->change('Record restored for review.', function ($record) {
            abort_unless($record->trashed(), 404);
            $record->status = $this->kind === 'events' ? 'cancelled' : 'draft';
            if ($record instanceof Post) {
                $record->published_at = null;
            }
            $record->restore();
        });
    }

    public function sendRemark(): void
    {
        abort_unless($this->kind === 'articles', 403);
        $this->remark = trim($this->remark);
        $this->validate(['remark' => ['required', 'string', 'max:5000']]);
        $post = Post::findOrFail($this->recordId);
        $remark = $post->remarks()->make(['message' => $this->remark]);
        $remark->admin()->associate(auth()->user());
        $remark->save();
        $this->reset('remark');
        session()->flash('managementStatus', 'Remark saved for the author.');
    }

    public function render()
    {
        $owner = $this->kind === 'articles' ? 'author' : 'user';
        $statuses = $this->kind === 'events' ? ['scheduled', 'cancelled'] : ['draft', 'published', 'archived'];
        $columns = $this->kind === 'articles' ? ['id', 'author_id', 'title', 'status', 'updated_at', 'deleted_at'] : ['id', 'user_id', 'title', 'status', 'updated_at', 'deleted_at'];
        if ($this->kind === 'events') {
            $columns = array_merge($columns, ['starts_at', 'ends_at', 'timezone']);
        }
        $query = $this->query()->select($columns)->with($owner.':id,name,status')
            ->when($this->status === 'trash', fn ($query) => $query->onlyTrashed())
            ->when(in_array($this->status, $statuses, true), fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($query) use ($owner) {
                $term = '%'.mb_substr(trim($this->search), 0, 200).'%';
                $query->where(fn ($query) => $query->where('title', 'like', $term)->orWhereHas($owner, fn ($query) => $query->where('name', 'like', $term)));
            });
        $selected = $this->recordId ? $this->query()->withTrashed()->with($owner.':id,name,status')->find($this->recordId) : null;
        if ($selected instanceof Post) {
            $selected->load('remarks.admin:id,name');
        }

        return view('livewire.admins.content-manager', [
            'records' => $query->latest('updated_at')->orderByDesc('id')->paginate(15),
            'selected' => $selected, 'ownerRelation' => $owner, 'statuses' => $statuses,
        ])->title('Manage '.ucfirst($this->kind));
    }
}
