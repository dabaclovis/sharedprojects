<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardFund extends Model
{
    protected $guarded = [];

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }
}
