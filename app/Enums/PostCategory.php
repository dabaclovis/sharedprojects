<?php

namespace App\Enums;

enum PostCategory: string
{
    case Community = 'Community';
    case Ideas = 'Ideas';
    case Updates = 'Updates';
    case Technology = 'Technology';
    case Business = 'Business';
    case Education = 'Education';
    case Lifestyle = 'Lifestyle';
    case Health = 'Health';
    case Travel = 'Travel';
    case Events = 'Events';
    case Tutorials = 'Tutorials';
    case Reviews = 'Reviews';
    case Other = 'Other';

    public function label(): string
    {
        return $this->value;
    }
}
