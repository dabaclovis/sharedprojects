<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    public const ICONS = [
        'fa-quote-left' => 'Opening quote',
        'fa-quote-right' => 'Closing quote',
        'fa-heart' => 'Heart',
        'fa-star' => 'Star',
        'fa-leaf' => 'Leaf',
        'fa-feather' => 'Feather',
        'fa-sun' => 'Sun',
        'fa-moon' => 'Moon',
        'fa-seedling' => 'Seedling',
        'fa-dove' => 'Dove',
        // add more icons
        'fa-mountain' => 'Mountain',
        'fa-tree' => 'Tree',
        'fa-water' => 'Water',
        'fa-fire' => 'Fire',
        'fa-cloud' => 'Cloud',
        'fa-rain' => 'Rain',
        'fa-snowflake' => 'Snowflake',
        'fa-lightning' => 'Lightning',
        'fa-wind' => 'Wind',
        'fa-ocean' => 'Ocean',
        'fa-volcano' => 'Volcano',
        'fa-desert' => 'Desert',
        'fa-waterfall' => 'Waterfall',
        'fa-cave' => 'Cave',
        'fa-cliff' => 'Cliff',
        'fa-canyon' => 'Canyon',
        'fa-glacier' => 'Glacier',
        'fa-island' => 'Island',

    ];

    // table name
    protected $table = 'quotes';

    protected $fillable = [
        'title',
        'content',
        'author',
        'source',
        'tags',
        'category',
        'language',
        'licon',
        'ricon'
    ];

    protected $hidden = ['ipaddr'];
}
