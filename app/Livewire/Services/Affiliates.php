<?php

namespace App\Livewire\Services;

use App\Enums\ProductCategory;
use App\Models\AffiliateProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('My affiliate products')]
class Affiliates extends Component
{
    use WithFileUploads, WithPagination;

    public $image = null;

    public bool $removeImage = false;

    #[Locked]
    public ?string $currentImageUrl = null;

    protected $paginationTheme = 'bootstrap';

    public const CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'XAF', 'XOF', 'NGN', 'INR'];

    #[Locked]
    public ?int $productId = null;

    public bool $showEditor = false;

    public string $search = '';

    public string $filter = '';

    public string $title = '';

    public string $description = '';

    public string $merchant = '';

    public string $category = '';

    public string $affiliate_url = '';

    public string $image_url = '';

    public string $price = '';

    public string $currency = 'USD';

    public string $status = 'draft';

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->cancel();
        $this->showEditor = true;
    }

    public function cancel(): void
    {
        $this->reset('image', 'removeImage', 'currentImageUrl');
        $this->reset('productId', 'showEditor', 'title', 'description', 'merchant', 'category', 'affiliate_url', 'image_url', 'price', 'currency', 'status');
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $product = Auth::user()->affiliateProducts()->findOrFail($id);
        $this->cancel();
        $this->productId = $product->id;
        $this->currentImageUrl = $product->image_path ? $product->image_source : null;
        foreach (['title', 'description', 'merchant', 'category', 'affiliate_url', 'image_url', 'price', 'currency', 'status'] as $field) {
            $this->{$field} = (string) ($product->{$field} ?? '');
        }
        $this->showEditor = true;
    }

    public function save(): void
    {
        $product = $this->productId ? Auth::user()->affiliateProducts()->findOrFail($this->productId) : new AffiliateProduct;
        foreach (['title', 'description', 'merchant', 'category', 'affiliate_url', 'image_url', 'price'] as $field) {
            $this->{$field} = trim($this->{$field});
        }
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'merchant' => ['required', 'string', 'max:120'],
            'category' => ['nullable', Rule::enum(ProductCategory::class)],
            'affiliate_url' => ['required', 'url:http,https', 'max:2048'],
            'image_url' => ['nullable', 'url:https', 'max:2048'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'removeImage' => ['boolean'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'currency' => ['required', Rule::in(self::CURRENCIES)],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
        $data['price'] = $data['price'] === '' ? null : $data['price'];
        unset($data['image'], $data['removeImage']);
        $product->fill($data);
        if (! $product->exists) {
            $product->user()->associate(Auth::user());
        }
        $oldPath = $product->image_path;
        $newPath = null;
        if ($this->image) {
            $newPath = $this->image->store('affiliate-products/'.Auth::id(), 'public');
            if (! $newPath) {
                throw ValidationException::withMessages(['image' => 'The image could not be stored. Please try again.']);
            }
            $product->image_path = $newPath;
        } elseif ($this->removeImage) {
            $product->image_path = null;
        }
        try {
            $product->save();
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }
        if ($oldPath && $oldPath !== $product->image_path) {
            Storage::disk('public')->delete($oldPath);
        }
        $this->cancel();
        $this->resetPage();
        session()->flash('productStatus', 'Product saved.');
    }

    public function delete(int $id): void
    {
        Auth::user()->affiliateProducts()->findOrFail($id)->delete();
        $this->cancel();
        $this->resetPage();
        session()->flash('productStatus', 'Product deleted.');
    }

    public function render()
    {
        return view('livewire.services.affiliates', [
            'products' => Auth::user()->affiliateProducts()
                ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($this->search).'%'))
                ->when($this->filter !== '', fn ($query) => $query->where('status', $this->filter))
                ->latest('updated_at')->orderByDesc('id')->paginate(10),
        ]);
    }
}
