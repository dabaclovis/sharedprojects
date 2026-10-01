<?php

namespace App\Livewire\Admins;

use App\Models\Post;
use App\Models\Quote;
use App\Models\Reward;
use App\Models\RewardFund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Content Rewards Management | CD Admin',
        'description' => 'Manage the contributor reward fund and issue eligible article and quote rewards.',
        'keywords' => 'content rewards, contributor payments, reward management',
    ]
)]
class Rewards extends Component
{
    public string $deposit = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->role === 'admin' && auth()->user()?->status === 'active', 403);
    }

    public function depositFunds(): void
    {
        $data = $this->validate(['deposit' => ['required', 'decimal:0,2', 'min:0.01', 'max:1000000']]);
        $cents = (int) round((float) $data['deposit'] * 100);
        DB::transaction(function () use ($cents) {
            $fund = RewardFund::query()->lockForUpdate()->find(1) ?? RewardFund::current();
            $fund->increment('balance_cents', $cents);
        });
        $this->reset('deposit');
        session()->flash('rewardStatus', 'Reward fund increased by $'.number_format($cents / 100, 2).'.');
    }

    public function award(string $type, int $id): void
    {
        abort_unless(in_array($type, ['quote', 'post'], true), 404);

        DB::transaction(function () use ($type, $id) {
            $fund = RewardFund::query()->lockForUpdate()->find(1) ?? RewardFund::current();
            $content = $type === 'quote'
                ? Quote::query()->findOrFail($id)
                : Post::query()->findOrFail($id);
            $userId = $type === 'quote' ? $content->user_id : $content->author_id;
            abort_unless($userId, 422);

            if ($type === 'post') {
                $words = str_word_count(strip_tags($content->content));
                abort_unless($content->status === 'published' && $content->category
                    && $words >= $fund->post_min_words && $words <= $fund->post_max_words, 422);
            }

            $amount = $type === 'quote' ? $fund->quote_reward_cents : $fund->post_reward_cents;
            if (Reward::where(['content_type' => $type, 'content_id' => $id])->exists()) {
                throw ValidationException::withMessages(['reward' => 'This content has already received a reward.']);
            }
            if ($fund->balance_cents < $amount) {
                throw ValidationException::withMessages(['reward' => 'The reward fund does not have enough money.']);
            }

            $reward = new Reward(['content_type' => $type, 'content_id' => $id, 'amount_cents' => $amount]);
            $reward->user()->associate($userId);
            $reward->awardedBy()->associate(auth()->user());
            $reward->save();
            $fund->decrement('balance_cents', $amount);
        });

        session()->flash('rewardStatus', 'Reward awarded.');
    }

    public function render()
    {
        $fund = RewardFund::current();
        $rewardedQuotes = Reward::where('content_type', 'quote')->select('content_id');
        $rewardedPosts = Reward::where('content_type', 'post')->select('content_id');

        $quotes = Quote::with('user:id,name')->whereNotNull('user_id')
            ->whereNotIn('id', $rewardedQuotes)->latest('id')->limit(25)->get();
        $posts = Post::with('author:id,name')->where('status', 'published')->whereNotNull('category')
            ->whereNotIn('id', $rewardedPosts)->latest('published_at')->limit(50)->get()
            ->filter(function (Post $post) use ($fund) {
                $words = str_word_count(strip_tags($post->content));
                $post->setAttribute('reward_word_count', $words);
                return $words >= $fund->post_min_words && $words <= $fund->post_max_words;
            });

        return view('livewire.admins.rewards', [
            'fund' => $fund,
            'quotes' => $quotes,
            'posts' => $posts,
            'recentRewards' => Reward::with(['user:id,name', 'awardedBy:id,name'])->latest()->limit(20)->get(),
        ]);
    }
}
