<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AffiliateProduct extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'description', 'merchant', 'category', 'affiliate_url', 'image_url', 'price', 'currency', 'status'];

    protected $attributes = ['status' => 'draft', 'currency' => 'USD', 'clicks' => 0];

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
