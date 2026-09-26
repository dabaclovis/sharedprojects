<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationRating extends Model
{
    protected $fillable = ['user_id', 'visitor_hash', 'score', 'feedback', 'ip_address'];

    protected $hidden = ['visitor_hash', 'ip_address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
