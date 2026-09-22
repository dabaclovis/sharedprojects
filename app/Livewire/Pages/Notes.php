<?php

namespace App\Livewire\Pages;

use App\Enums\QuoteCategory;
use App\Models\Quote;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Community quotes')]
class Notes extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $content = '';

    public string $title = '';

    public string $author = '';

    public string $source = '';

    public string $tags = '';

    public string $category = '';

    public string $language = '';

    public string $search = '';

    public string $licon = 'fa-quote-left';

    public string $ricon = 'fa-quote-right';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        foreach (['content', 'title', 'author', 'source', 'tags', 'category', 'language'] as $field) {
            $this->$field = trim($this->$field);
        }

        $data = $this->validate([
            'content' => ['required', 'string', 'max:250'],
            'title' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(QuoteCategory::class)],
            'language' => ['nullable', 'string', 'max:255'],
            'licon' => ['required', Rule::in(array_keys(Quote::ICONS))],
            'ricon' => ['required', Rule::in(array_keys(Quote::ICONS))],
        ]);

        $key = 'quotes:create:' . hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('content', 'Please wait ' . RateLimiter::availableIn($key) . ' seconds before sharing another quote.');

            return;
        }

        $quote = new Quote($data);
        $quote->ipaddr = request()->ip();
        $quote->save();
        RateLimiter::hit($key, 60);

        $this->reset('content', 'title', 'author', 'source', 'tags', 'category', 'language', 'search', 'licon', 'ricon');
        $this->resetPage();
        session()->flash('quote-saved', 'Your quote has been shared!');
    }

    public function render()
    {
        $search = mb_substr(trim($this->search), 0, 200);

        return view('livewire.pages.notes', [
            'iconOptions' => Quote::ICONS,
            'categories' => QuoteCategory::cases(),
            'quotes' => Quote::query()
                ->select(['id', 'title', 'content', 'author', 'source', 'tags', 'category', 'language', 'licon', 'ricon', 'created_at'])
                ->when($search !== '', fn($query) => $query->where(fn($query) => $query
                    ->where('content', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhere('author', 'like', '%' . $search . '%')
                    ->orWhere('tags', 'like', '%' . $search . '%')))
                ->latest('id')->paginate(3),
        ]);
    }
}
