<?php

namespace Tests\Feature;

use App\Livewire\Pages\Products;
use App\Livewire\Services\Affiliates;
use App\Models\AffiliateProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class AffiliateProductsTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $user, string $status = 'published'): AffiliateProduct
    {
        return $user->affiliateProducts()->create([
            'title' => 'Useful headphones', 'description' => 'Comfortable headphones', 'merchant' => 'Example store',
            'affiliate_url' => 'https://example.com/item?ref=partner', 'status' => $status, 'category' => 'Audio',
        ]);
    }

    public function test_active_user_and_admin_can_manage_and_publish_products(): void
    {
        foreach (['user', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('services.affiliates'))->assertOk();
            $component = Livewire::actingAs($user)->test(Affiliates::class)->call('create')
                ->call('save')->assertHasErrors(['title', 'description', 'merchant', 'affiliate_url'])
                ->set('title', 'Headphones')->set('description', 'Product description')->set('merchant', 'Store')
                ->set('affiliate_url', 'https://example.com/item?ref=partner')->set('price', '49.99')
                ->call('save')->assertHasNoErrors()->assertSet('showEditor', false);
            $product = $user->affiliateProducts()->firstOrFail();
            $this->assertSame('draft', $product->status);
            $this->assertSame('49.99', $product->price);
            $this->get(route('products.visit', $product->id))->assertNotFound();
            $component->call('edit', $product->id)->set('status', 'published')->call('save')->assertHasNoErrors();
            $this->get(route('pages.products'))->assertOk()->assertSee('Headphones')->assertSee('Affiliate disclosure');
            $this->get(route('products.visit', $product->id))->assertRedirect('https://example.com/item?ref=partner');
            $this->assertSame(1, $product->fresh()->clicks);
            $component->call('delete', $product->id);
            $this->assertSoftDeleted($product);
            $this->get(route('products.visit', $product->id))->assertNotFound();
        }
    }

    public function test_urls_price_and_currency_are_validated(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Affiliates::class)->call('create')
            ->set('title', 'Product')->set('description', 'Description')->set('merchant', 'Shop')
            ->set('affiliate_url', 'javascript:alert(1)')->set('image_url', 'data:text/html,bad')
            ->set('price', '-1')->set('currency', 'BAD')->call('save')
            ->assertHasErrors(['affiliate_url', 'image_url', 'price', 'currency']);
        $this->assertDatabaseCount('affiliate_products', 0);
    }

    public function test_catalog_filters_paginate_and_hide_nonpublic_products(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            $this->product($user);
        }
        $draft = $this->product($user, 'draft');
        $draft->update(['title' => 'Hidden draft']);
        $archived = $this->product($user, 'archived');
        $archived->update(['title' => 'Archived item']);
        $inactive = $this->product(User::factory()->create(['status' => 'inactive']));
        Livewire::test(Products::class)->assertDontSee('Hidden draft')->assertDontSee('Archived item')
            ->assertViewHas('products', fn ($p) => $p->total() === 10 && $p->count() === 9)
            ->call('nextPage')->assertViewHas('products', fn ($p) => $p->count() === 1)
            ->set('search', 'missing')->assertSet('paginators.page', 1)->assertSee('No products found')
            ->set('search', '')->set('category', 'Audio')->assertViewHas('products', fn ($p) => $p->total() === 10);
        $this->get(route('products.visit', $inactive->id))->assertNotFound();
    }

    public function test_ownership_and_authentication_are_enforced(): void
    {
        $this->get(route('services.affiliates'))->assertRedirect(route('auth.login'));
        $product = $this->product(User::factory()->create());
        $other = User::factory()->create();
        foreach (['edit', 'delete'] as $action) {
            try {
                Livewire::actingAs($other)->test(Affiliates::class)->assertDontSee('Useful headphones')->call($action, $product->id);
                $this->fail('Foreign products must be inaccessible.');
            } catch (ModelNotFoundException $exception) {
                $this->assertSame(AffiliateProduct::class, $exception->getModel());
            }
        }
        $component = Livewire::actingAs($other)->test(Affiliates::class);
        $other->status = 'inactive';
        $other->save();
        $component->call('create')->assertForbidden();
        $this->assertNotSoftDeleted($product);
    }

    public function test_product_identity_cannot_be_changed_in_the_browser(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(Affiliates::class);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('productId', 123);
    }
}
