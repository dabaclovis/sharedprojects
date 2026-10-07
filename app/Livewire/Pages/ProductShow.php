<?php

namespace App\Livewire\Pages;

use App\Models\AffiliateProduct;
use App\Models\Post;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProductShow extends Component
{
    #[Locked]
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $product = AffiliateProduct::published()->with('user:id,name')->where('slug', $this->slug)->firstOrFail();
        $articleCategory = match ($product->category) {
            'Electronics', 'Computers', 'Audio', 'Software', 'Video Games' => 'Technology',
            'Books' => 'Education',
            'Office Supplies', 'Industrial & Scientific' => 'Business',
            default => 'Lifestyle',
        };

        return view('livewire.pages.product-show', [
            'product' => $product,
            'relatedProducts' => AffiliateProduct::published()->where('id', '!=', $product->id)->where('category', $product->category)->latest()->limit(3)->get(),
            'relatedArticles' => Post::published()->whereRaw('LOWER(category) = ?', [strtolower($articleCategory)])->latest('published_at')->limit(3)->get(),
        ])->layout('components.layouts.app', [
            'title' => $product->seo_title ?: $product->title.' | Brotherfall',
            'description' => $product->meta_description ?: Str::limit(preg_replace('/\s+/u', ' ', strip_tags($product->description)), 160),
            'canonical' => route('pages.product-show', $product->slug),
        ]);
    }
}
