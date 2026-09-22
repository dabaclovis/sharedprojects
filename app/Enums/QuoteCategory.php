<?php

namespace App\Enums;

enum QuoteCategory: string
{
    case Inspiration = 'Inspiration';
    case Motivation = 'Motivation';
    case Life = 'Life';
    case Love = 'Love';
    case Friendship = 'Friendship';
    case Family = 'Family';
    case Happiness = 'Happiness';
    case Wisdom = 'Wisdom';
    case Success = 'Success';
    case Courage = 'Courage';
    case Hope = 'Hope';
    case Gratitude = 'Gratitude';
    case Faith = 'Faith';
    case Nature = 'Nature';
    case Humor = 'Humor';
    case Other = 'Other';
    case Beauty = 'Beauty';
    case Bible = 'Bible';

    public function label(): string
    {
        return $this->value;
    }
}
