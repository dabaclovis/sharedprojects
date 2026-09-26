<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    public const SERVICES = [
        'website-audit' => 'Website audit reports',
        'sponsorship' => 'Sponsored homepage placements',
    ];

    public const STATUSES = ['new', 'quoted', 'in_progress', 'completed', 'cancelled'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'audit_result' => 'array',
            'history' => 'array',
            'paid_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeLiveSponsors(Builder $query): Builder
    {
        return $query->where('service', 'sponsorship')->where('status', 'in_progress')
            ->where('payment_status', 'paid')->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public function revision(): string
    {
        return hash('sha256', json_encode($this->getRawOriginal(), JSON_THROW_ON_ERROR));
    }

    public function price(): string
    {
        return $this->amount_cents === null ? 'Not quoted' : $this->currency.' '.number_format($this->amount_cents / 100, 2);
    }
}
