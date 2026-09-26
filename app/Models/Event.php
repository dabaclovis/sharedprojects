<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    public const COLORS = ['blue' => 'Blue', 'green' => 'Green', 'purple' => 'Purple', 'orange' => 'Orange', 'pink' => 'Pink', 'teal' => 'Teal'];

    protected $fillable = ['title', 'description', 'location', 'timezone', 'starts_at', 'ends_at', 'status', 'color'];

    protected $attributes = ['status' => 'scheduled', 'timezone' => 'UTC', 'color' => 'blue'];

    protected function casts(): array
    {
        return ['starts_at' => UtcDateTime::class, 'ends_at' => UtcDateTime::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
