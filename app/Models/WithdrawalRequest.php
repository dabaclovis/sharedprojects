<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    public const METHODS = [
        'paypal' => 'PayPal',
        'bank_transfer' => 'Bank transfer',
        'mobile_money' => 'Mobile money',
        'other' => 'Other',
    ];

    protected $fillable = [
        'user_id',
        'amount_cents',
        'payout_method',
        'payout_details',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected $hidden = ['payout_details'];

    protected function casts(): array
    {
        return [
            'payout_details' => 'encrypted',
            'amount_cents' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
