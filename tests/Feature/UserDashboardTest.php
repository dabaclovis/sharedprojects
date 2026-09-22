<?php

namespace Tests\Feature;

use App\Livewire\Users\Index;
use App\Models\Post;
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
            ->assertSee('Your stories')->assertSee('Logout');
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
            ->assertViewHas('stats', fn ($stats) => $stats['Total posts'] === 2 && $stats['Drafts'] === 1)
            ->set('search', 'Private post')->assertSee('No matching posts');
    }

    public function test_search_status_filters_and_pagination_work(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 6; $i++) {
            $this->createPost($user, 'Draft '.$i);
        }
        $this->createPost($user, 'Archived story', 'archived');

        Livewire::actingAs($user)->test(Index::class)
            ->assertViewHas('posts', fn ($posts) => $posts->total() === 7 && $posts->count() === 5)
            ->call('nextPage')->assertSet('paginators.page', 2)
            ->assertViewHas('posts', fn ($posts) => $posts->count() === 2)
            ->set('search', 'Archived')->assertSet('paginators.page', 1)->assertSee('Archived story')
            ->set('status', 'draft')->assertSee('No matching posts')
            ->call('clearFilters')->assertSet('search', '')->assertSet('status', '')
            ->set('status', 'archived')->assertViewHas('posts', fn ($posts) => $posts->total() === 1);
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

