<?php

namespace App\Livewire\Pages;

use App\Models\AffiliateProduct;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Recommended Products and Useful Finds | Brotherfall',
        'description' => 'Explore useful product recommendations with clear details, pricing information and transparent affiliate disclosures.',
        'keywords' => 'product recommendations, useful products, affiliate products, product discoveries',
    ]
)]
class Products extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $category = '';

    public array $categories = [];

    public function mount(): void
    {
        // Categories change infrequently; carry them in Livewire state instead of
        // repeating a distinct query after every search/filter interaction.
        $this->categories = AffiliateProduct::published()
            ->whereNotNull('category')->where('category', '!=', '')
            ->distinct()->orderBy('category')->pluck('category')->all();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = mb_substr(trim($this->search), 0, 200);

        return view('livewire.pages.products', [
            'products' => AffiliateProduct::published()->with('user:id,name')
                ->select(['id', 'user_id', 'title', 'description', 'merchant', 'category', 'affiliate_url', 'image_url', 'image_path', 'price', 'currency', 'created_at', 'updated_at'])
                ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('merchant', 'like', '%'.$search.'%')))
                ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
                ->latest()->orderByDesc('id')->paginate(9),
            'categories' => $this->categories,
        ]);
    }
}
