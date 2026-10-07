<?php

namespace App\Models;

use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AffiliateProduct extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'description', 'merchant', 'category', 'affiliate_url', 'image_url', 'price', 'currency', 'status', 'seo_title', 'meta_description', 'image_alt', 'best_for', 'pros', 'cons'];

    protected $attributes = ['status' => 'draft', 'currency' => 'USD', 'clicks' => 0];

    protected static function booted(): void
    {
        static::creating(function (AffiliateProduct $product) {
            $base = Str::limit(Str::slug($product->title) ?: 'product', 190, '');
            $slug = $base;
            $suffix = 2;
            while (static::withTrashed()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            $product->slug = $slug;
        });
    }

    public function getCategorySlugAttribute(): ?string
    {
        $category = ProductCategory::tryFrom($this->category ?? '');

        return $category ? Str::slug($category->value) : null;
    }

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'clicks' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getImageSourceAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : ($this->image_url ?: null);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereHas('user', fn ($user) => $user->where('status', 'active'));
    }
}
