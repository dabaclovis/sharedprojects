<?php

namespace App\Livewire\Pages;

use App\Models\AffiliateProduct;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Product recommendations')]
class Products extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $category = '';

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
        return view('livewire.pages.products', [
            'products' => AffiliateProduct::published()->with('user')
                ->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('title', 'like', '%'.trim($this->search).'%')
                    ->orWhere('merchant', 'like', '%'.trim($this->search).'%')))
                ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
                ->latest()->orderByDesc('id')->paginate(9),
            'categories' => AffiliateProduct::published()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'),
        ]);
    }
}
