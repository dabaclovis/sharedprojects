<?php

namespace Tests\Feature;

use App\Livewire\Users\Articles;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleSeoTest extends TestCase
{
    use RefreshDatabase;

    private function samplePost(): Post
    {
        $user = User::factory()->create(['status' => 'active']);
        $post = new Post(['title' => 'File organization', 'slug' => 'file-organization', 'content' => 'Useful instructions', 'category' => 'Technology', 'seo_title' => 'Organize your files', 'meta_description' => 'A practical filing guide.']);
        $post->author()->associate($user);
        $post->postsable()->associate($user);
        $post->status = 'published';
        $post->published_at = now()->subDay();
        $post->save();

        return $post;
    }

    public function test_metadata_categories_sitemap_and_alias_redirects(): void
    {
        $post = $this->samplePost();
        $post->update(['slug' => 'organize-files']);
        $url = route('pages.postshow', $post->slug);
        $this->get('/articles/file-organization')->assertStatus(301)->assertRedirect($url);
        $this->get('/pages/articles/file-organization')->assertStatus(301)->assertRedirect($url);
        $this->get('/pages/articles')->assertStatus(301)->assertRedirect('/articles');
        $response = $this->get($url)->assertOk()->assertSee('<title>Organize your files</title>', false)->assertSee('A practical filing guide.');
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $response->getContent(), $matches);
        $schema = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Article', $schema['@type']);
        $this->assertSame($url, $schema['mainEntityOfPage']);
        $this->get('/articles/category/technology')->assertOk()->assertSee('File organization')->assertSee('Technology Articles and Guides | Brotherfall');
        $this->get('/articles/category/invalid')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertSee($url)->assertDontSee('/articles/file-organization');
        $post->status = 'draft';
        $post->save();
        $this->get('/sitemap.xml')->assertDontSee($url);
        $this->get('/articles/file-organization')->assertNotFound();
    }

    public function test_editor_saves_seo_and_generates_collision_safe_slugs(): void
    {
        $post = $this->samplePost();
        Livewire::actingAs($post->author)->test(Articles::class)->call('create')
            ->set('title', 'File organization')->set('content', 'A guide')
            ->set('seo_title', 'Better filing')->set('meta_description', 'Find your files.')
            ->set('tags', 'files, productivity, files')->call('save')->assertHasNoErrors();
        $new = Post::where('slug', 'file-organization-2')->firstOrFail();
        $this->assertSame(['files', 'productivity'], $new->tags);
        $this->assertSame('Better filing', $new->seo_title);
        Livewire::actingAs($post->author)->test(Articles::class)->call('edit', $new->id)
            ->set('slug', $post->slug)->call('save')->assertHasErrors('slug');
    }

    public function test_uuid_backfill_preserves_old_links(): void
    {
        $post = $this->samplePost();
        $old = 'legacy-guide-2c147f09-0bc5-4c67-9919-24fa365044de';
        DB::table('posts')->where('id', $post->id)->update(['slug' => $old]);
        $migration = require database_path('migrations/2026_10_06_000001_add_article_seo.php');
        $migration->down();
        $migration->up();
        $this->assertSame('legacy-guide', $post->fresh()->slug);
        $this->get('/pages/articles/'.$old)->assertStatus(301)->assertRedirect(route('pages.postshow', 'legacy-guide'));
    }
}
