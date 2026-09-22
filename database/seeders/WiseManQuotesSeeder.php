<?php

namespace Database\Seeders;

use App\Models\Quote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WiseManQuotesSeeder extends Seeder
{
    public function run(): void
    {
        $quotes = [
            ['Listen before speaking', 'A wise person listens long enough to understand, then speaks only what can improve the silence.', 'Wisdom', 'fa-feather'],
            ['The patient path', 'Patience does not shorten the road, but it helps you arrive without losing yourself along the way.', 'Life', 'fa-mountain'],
            ['Plant for tomorrow', 'The best time to plant hope is before you need its shade.', 'Hope', 'fa-seedling'],
            ['Quiet strength', 'True strength is shown by the burdens you carry without placing them on someone else.', 'Courage', 'fa-tree'],
            ['Choose your circle', 'Walk with people who correct you in private and defend your dignity in public.', 'Friendship', 'fa-dove'],
            ['A teachable heart', 'The moment you believe you have nothing left to learn is the moment wisdom leaves the room.', 'Wisdom', 'fa-feather'],
            ['Measure success well', 'Success is not only what you gather, but also what you give and who you become.', 'Success', 'fa-star'],
            ['Guard your peace', 'Not every argument deserves your voice; sometimes peace is the wiser answer.', 'Wisdom', 'fa-moon'],
            ['Respect small steps', 'A long journey is completed by honoring the small steps that others are tempted to ignore.', 'Motivation', 'fa-mountain'],
            ['Learn from the storm', 'The storm may bend the tree, but it also teaches the roots how deeply they must grow.', 'Life', 'fa-tree'],
            ['Use time wisely', 'Money lost may return, but an hour wasted can only return as a lesson.', 'Wisdom', 'fa-sun'],
            ['Kindness remembered', 'People may forget your clever words, but they remember the kindness that arrived when they needed it.', 'Love', 'fa-heart'],
            ['Keep your word', 'A promise is a mirror of character; once broken, every reflection carries the crack.', 'Wisdom', 'fa-water'],
            ['Control your anger', 'Anger gives quick instructions and leaves wisdom to repair the damage.', 'Life', 'fa-fire'],
            ['Value good counsel', 'Advice is a lamp offered at the crossroads, but you must still choose the road.', 'Wisdom', 'fa-star'],
            ['Remain humble', 'Humility is knowing your worth without needing to announce it in every room.', 'Wisdom', 'fa-leaf'],
            ['Let actions speak', 'A good intention becomes meaningful only when your hands give it a place in the world.', 'Inspiration', 'fa-seedling'],
            ['Protect trust', 'Trust grows slowly like a tree and can fall quickly like one struck by lightning.', 'Friendship', 'fa-lightning'],
            ['Welcome correction', 'Correction from a sincere friend is more valuable than praise from a dishonest crowd.', 'Friendship', 'fa-dove'],
            ['Practice gratitude', 'A grateful heart sees abundance where an impatient mind sees only delay.', 'Gratitude', 'fa-sun'],
            ['Leave a good path', 'Live so that those who follow your footsteps find fewer stones in their way.', 'Inspiration', 'fa-mountain'],
        ];

        $created = 0;
        DB::transaction(function () use ($quotes, &$created): void {
            foreach ($quotes as [$title, $content, $category, $icon]) {
                $quote = Quote::firstOrCreate(
                    ['title' => $title, 'source' => 'Wise Man collection'],
                    [
                        'content' => $content,
                        'author' => 'A Wise Man',
                        'tags' => 'wisdom, life lessons, wise man',
                        'category' => $category,
                        'language' => 'English',
                        'licon' => $icon,
                        'ricon' => 'fa-quote-right',
                    ],
                );
                $created += (int) $quote->wasRecentlyCreated;
            }
        });

        $this->command?->info("Created {$created} Wise Man quotes; all 21 entries are available.");
    }
}
