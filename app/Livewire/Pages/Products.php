<?php

namespace App\Livewire\Pages;

use App\Enums\ProductCategory;
use App\Models\AffiliateProduct;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

    #[Locked]
    public string $landingCategory = '';

    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = $this->landingCategory;
        $this->resetPage();
    }

    public array $categories = [];

    public function mount(?string $categorySlug = null): void
    {
        if ($categorySlug !== null) {
            $match = collect(ProductCategory::cases())->first(fn ($item) => Str::slug($item->value) === $categorySlug);
            abort_unless($match, 404);
            $this->landingCategory = $this->category = $match->value;
        }
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
                ->select(['id', 'slug', 'image_alt', 'user_id', 'title', 'description', 'merchant', 'category', 'affiliate_url', 'image_url', 'image_path', 'price', 'currency', 'created_at', 'updated_at'])
                ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('merchant', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%')))
                ->when($this->landingCategory !== '', fn ($query) => $query->where('category', $this->landingCategory))
                ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
                ->latest()->orderByDesc('id')->paginate(9),
            'categories' => $this->categories,
            'categoryLinks' => collect(ProductCategory::cases())->filter(fn ($item) => in_array($item->value, $this->categories, true)),
        ])->layoutData([
            'title' => $this->landingCategory.' Recommendations | Brotherfall',
            'description' => 'Explore '.strtolower($this->landingCategory).' recommendations, practical benefits, limitations and merchant links from the Brotherfall community.',
        ]);
    }
}
