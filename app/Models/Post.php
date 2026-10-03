<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    public const ICONS = [
        'fa-file-lines' => 'Article',
        'fa-lightbulb' => 'Idea',
        'fa-comments' => 'Conversation',
        'fa-seedling' => 'Growth',
        'fa-book-open' => 'Reading',
        'fa-briefcase' => 'Business',
        'fa-calendar-days' => 'Events',
        'fa-camera' => 'Photography',
        'fa-code' => 'Technology',
        'fa-flask' => 'Science',
        'fa-globe' => 'World',
        'fa-graduation-cap' => 'Education',
        'fa-heart' => 'Wellbeing',
        'fa-music' => 'Music',
        'fa-palette' => 'Art',
        'fa-plane' => 'Travel',
    ];

    public function remarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Remark::class)->latest('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    // Assign authors, owners, and publishing state explicitly after authorization.
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'category',
        'icon',
        'featured_image',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function isEligibleForReward(): bool
    {
        return str_word_count(strip_tags($this->title)) >= 6
            && str_word_count(strip_tags($this->content)) >= 350;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function postsable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
