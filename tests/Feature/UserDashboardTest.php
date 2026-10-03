<?php

namespace Tests\Feature;

use App\Livewire\Users\Index;
use App\Models\AdminDashboardLink;
use App\Models\Post;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(User $user, string $title, string $status = 'draft'): Post
    {
        $post = new Post(['title' => $title, 'slug' => fake()->uuid(), 'content' => 'Example content']);
        $post->author()->associate($user);
        $post->postsable()->associate($user);
        $post->status = $status;
        $post->save();

        return $post;
    }

    public function test_dashboard_requires_an_active_signed_in_account(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('auth.login'));
        $this->actingAs(User::factory()->create(['status' => 'inactive']))
            ->get(route('users.index'))->assertForbidden();
    }

    public function test_dashboard_displays_account_and_empty_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('users.index'))->assertOk()
            ->assertSee($user->name)->assertDontSee($user->email)
            ->assertSee('Your stories')->assertSee('Logout')->assertSee(route('users.withdrawals'))
            ->assertDontSee('Public site');
    }

    public function test_admin_reminders_are_shown_on_every_user_dashboard_without_links(): void
    {
        AdminDashboardLink::create([
            'title' => 'Support center',
            'description' => 'Get help with your account.',
        ])->forceFill(['url' => 'https://example.test/support'])->save();
        AdminDashboardLink::create([
            'title' => 'Learning library',
            'description' => 'Browse helpful guides.',
        ])->forceFill(['url' => 'https://example.test/guides'])->save();

        foreach ([User::factory()->create(), User::factory()->create()] as $user) {
            Livewire::actingAs($user)->test(Index::class)
                ->assertSee('Reminders')
                ->assertSee('Support center')->assertSee('Get help with your account.')
                ->assertSee('Learning library')->assertSee('Browse helpful guides.')
                ->assertDontSee('href="https://example.test/support"', false)
                ->assertDontSee('href="https://example.test/guides"', false)
                ->assertDontSee('https://example.test/support')
                ->assertDontSee('https://example.test/guides')
                ->assertViewHas('quickLinks', fn($links) => $links->count() === 2);
        }
    }

    public function test_posts_and_statistics_are_scoped_to_the_signed_in_author(): void
    {
        $user = User::factory()->create();
        $this->createPost($user, 'My draft');
        $this->createPost($user, 'My published post', 'published');
        $this->createPost($user, 'Removed post')->delete();
        $this->createPost(User::factory()->create(), 'Private post from someone else');

        Livewire::actingAs($user)->test(Index::class)
            ->assertSee('My draft')->assertSee('My published post')
            ->assertDontSee('Removed post')->assertDontSee('Private post from someone else')
            ->assertViewHas('stats', fn($stats) => $stats['Total posts'] === 2 && $stats['Drafts'] === 1)
            ->set('search', 'Private post')->assertSee('No matching posts');
    }

    public function test_dashboard_capitalizes_the_post_content_preview(): void
    {
        $user = User::factory()->create();
        $post = $this->createPost($user, 'A story');
        $post->forceFill(['excerpt' => 'A separate summary', 'content' => 'lowercase body text'])->save();

        Livewire::actingAs($user)->test(Index::class)
            ->assertSee('Lowercase body text')
            ->assertDontSee('A separate summary')
            ->assertViewHas('posts', fn($posts) => $posts->first()->content === 'lowercase body text');
    }

    public function test_dashboard_shows_only_the_signed_in_users_post_earnings(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $post = $this->createPost($user, 'Rewarded story', 'published');
        $otherPost = $this->createPost(User::factory()->create(), 'Another author story', 'published');

        foreach ([[$user, 'post', $post->id, 100], [$user, 'quote', 1, 2500], [$admin, 'post', $otherPost->id, 500]] as [$recipient, $type, $contentId, $amount]) {
            $reward = new Reward(['content_type' => $type, 'content_id' => $contentId, 'amount_cents' => $amount]);
            $reward->user()->associate($recipient);
            $reward->awardedBy()->associate($admin);
            $reward->save();
        }

        Livewire::actingAs($user)->test(Index::class)
            ->assertSee('Post earnings')->assertSee('$1.00')->assertSee('Rewarded story')
            ->assertSee('1 rewarded')->assertDontSee('Another author story')
            ->assertViewHas('postEarningsCents', 100)->assertViewHas('postRewardCount', 1);
    }

    public function test_search_status_filters_and_pagination_work(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 6; $i++) {
            $this->createPost($user, 'Draft ' . $i);
        }
        $this->createPost($user, 'Archived story', 'archived');

        Livewire::actingAs($user)->test(Index::class)
            ->assertViewHas('posts', fn($posts) => $posts->total() === 7 && $posts->count() === 5)
            ->call('nextPage')->assertSet('paginators.page', 2)
            ->assertViewHas('posts', fn($posts) => $posts->count() === 2)
            ->set('search', 'Archived')->assertSet('paginators.page', 1)->assertSee('Archived story')
            ->set('status', 'draft')->assertSee('No matching posts')
            ->call('clearFilters')->assertSet('search', '')->assertSet('status', '')
            ->set('status', 'archived')->assertViewHas('posts', fn($posts) => $posts->total() === 1);
    }

    public function test_access_is_rechecked_on_livewire_updates(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(Index::class);
        $user->status = 'inactive';
        $user->save();
        $component->call('clearFilters')->assertForbidden();
    }
}
