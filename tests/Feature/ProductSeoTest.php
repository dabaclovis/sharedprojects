<?php

namespace Tests\Feature;

use App\Livewire\Users\Products;
use App\Models\AffiliateProduct;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSeoTest extends TestCase
{
    use RefreshDatabase;

    private function recommendation(array $attributes = []): AffiliateProduct
    {
        return User::factory()->create(['status' => 'active'])->affiliateProducts()->create(array_merge([
            'title' => 'Useful desk lamp', 'description' => 'A light for a small work desk.',
            'merchant' => 'Example shop', 'affiliate_url' => 'https://example.com/lamp',
            'status' => 'published', 'category' => 'Office Supplies',
            'seo_title' => 'Desk lamp recommendation', 'meta_description' => 'Choose lighting for a small desk.',
            'best_for' => 'Small workspaces', 'pros' => "Compact footprint\nAdjustable light", 'cons' => 'Check outlet compatibility',
        ], $attributes));
    }

    public function test_recommendation_pages_have_metadata_content_and_crawlable_links(): void
    {
        $product = $this->recommendation();
        $related = $this->recommendation(['title' => 'Desk organizer']);
        $post = new Post(['title' => 'Office planning', 'slug' => 'office-planning', 'content' => 'A guide', 'category' => 'Business']);
        $post->author()->associate($product->user);
        $post->postsable()->associate($product->user);
        $post->status = 'published';
        $post->published_at = now();
        $post->save();
        $url = route('pages.product-show', $product->slug);
        $this->get(route('pages.products'))->assertOk()->assertSee($url)->assertSee(route('pages.product-category', 'office-supplies'));
        $this->get($url)->assertOk()->assertSee('<title>Desk lamp recommendation</title>', false)
            ->assertSee('Choose lighting for a small desk.')->assertSee('Affiliate disclosure')
            ->assertSee('Small workspaces')->assertSee('Compact footprint')->assertSee('Check outlet compatibility')
            ->assertSee('rel="sponsored nofollow noopener noreferrer"', false)
            ->assertSee(route('pages.product-show', $related->slug))->assertSee(route('pages.postshow', $post->slug));
        $this->get(route('pages.product-category', 'office-supplies'))->assertOk()->assertSee('Office Supplies Recommendations | Brotherfall')->assertSee($product->title);
        $this->get(route('pages.product-category', 'unknown'))->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertSee($url)->assertSee(route('pages.product-category', 'office-supplies'));
    }

    public function test_nonpublic_products_are_excluded_from_reader_and_sitemap(): void
    {
        $product = $this->recommendation();
        $url = route('pages.product-show', $product->slug);
        foreach (['draft', 'archived'] as $status) {
            $product->update(['status' => $status]);
            $this->get($url)->assertNotFound();
            $this->get('/sitemap.xml')->assertDontSee($url);
        }
        $product->update(['status' => 'published']);
        $product->user->update(['status' => 'inactive']);
        $this->get($url)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee($url);
        $product->user->update(['status' => 'active']);
        $product->delete();
        $this->get($url)->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee($url);
    }

    public function test_editor_retains_stable_slug_and_escapes_user_content(): void
    {
        $product = $this->recommendation();
        $duplicate = $this->recommendation();
        $this->assertSame('useful-desk-lamp-2', $duplicate->slug);
        Livewire::actingAs($product->user)->test(Products::class)->call('edit', $product->id)
            ->assertSet('best_for', 'Small workspaces')->set('title', 'Updated desk lamp')
            ->set('pros', '<script>alert(1)</script>')->set('image_alt', 'Lamp on a desk')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('useful-desk-lamp', $product->fresh()->slug);
        $this->assertSame('Lamp on a desk', $product->fresh()->image_alt);
        $this->get(route('pages.product-show', $product->slug))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_empty_catalog_has_honest_content_and_helpful_links(): void
    {
        $this->get(route('pages.products'))->assertOk()->assertSee('Recommendations are on the way')
            ->assertDontSee('Try a different search')->assertSee('Make a more informed choice')
            ->assertSee(route('pages.percentage-calculator'));
    }

    public function test_migration_backfills_existing_products_without_changing_content(): void
    {
        $product = $this->recommendation();
        $migration = require database_path('migrations/2026_10_06_000002_add_product_recommendation_pages.php');
        $migration->down();
        $migration->up();
        $this->assertSame('useful-desk-lamp', $product->fresh()->slug);
        $this->assertSame('A light for a small work desk.', $product->fresh()->description);
    }
}
