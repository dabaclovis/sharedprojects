<?php

namespace Database\Seeders;

use App\Models\Quote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleQuotesSeeder extends Seeder
{
    public function run(): void
    {
        $quotes = [
            ['Small beginnings', 'A small step taken today is worth more than a perfect plan postponed forever.', 'Motivation', 'fa-seedling'],
            ['Quiet wisdom', 'Wisdom begins when curiosity becomes stronger than the need to be right.', 'Wisdom', 'fa-feather'],
            ['Choose kindness', 'Kindness is a gift whose value grows each time it is passed along.', 'Love', 'fa-heart'],
            ['Begin again', 'A difficult morning does not have to become a defeated day.', 'Hope', 'fa-sun'],
            ['Room to grow', 'Give yourself the patience you would offer a seed before expecting a forest.', 'Nature', 'fa-tree'],
            ['Courage in motion', 'Courage is taking the next honest step while uncertainty walks beside you.', 'Courage', 'fa-mountain'],
            ['Lasting friendship', 'A true friend makes room for both your laughter and your silence.', 'Friendship', 'fa-dove'],
            ['Everyday gratitude', 'Notice the ordinary comforts; they are often the treasures you would miss most.', 'Gratitude', 'fa-leaf'],
            ['Your own path', 'Progress becomes easier to see when you stop measuring it against someone else\'s journey.', 'Success', 'fa-star'],
            ['A place to belong', 'Home is built through small acts of care repeated day after day.', 'Family', 'fa-heart'],
            ['Make space for joy', 'Happiness often arrives quietly, while we are busy appreciating what is already here.', 'Happiness', 'fa-sun'],
            ['Learn from yesterday', 'Keep the lesson from yesterday, but leave room in your hands for tomorrow.', 'Life', 'fa-feather'],
            ['Steady effort', 'The work you repeat with care becomes the strength you can rely on.', 'Inspiration', 'fa-seedling'],
            ['A lighter moment', 'Some days the greatest achievement is remembering where you left your tea.', 'Humor', 'fa-moon'],
            ['Look closely', 'Beauty waits in familiar places for the moment we slow down enough to see it.', 'Beauty', 'fa-leaf'],
        ];

        $created = 0;
        DB::transaction(function () use ($quotes, &$created) {
            foreach ($quotes as [$title, $content, $category, $icon]) {
                $quote = Quote::firstOrCreate(
                    ['title' => $title, 'source' => 'Original sample quote for testing'],
                    ['content' => $content, 'author' => 'Sample collection', 'category' => $category,
                        'language' => 'English', 'tags' => 'sample, testing', 'licon' => $icon, 'ricon' => 'fa-quote-right'],
                );
                $created += (int) $quote->wasRecentlyCreated;
            }
        });
        $this->command?->info("Created {$created} sample quotes; all 15 sample entries are available.");
    }
}
