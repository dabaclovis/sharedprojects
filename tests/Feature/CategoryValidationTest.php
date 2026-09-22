<?php

namespace Tests\Feature;

use App\Enums\PostCategory;
use App\Enums\ProductCategory;
use App\Livewire\Services\Affiliates;
use App\Livewire\Users\Articles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_category_must_be_a_post_enum_value(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Articles::class)
            ->call('create')->set('title', 'Example')->set('content', 'Article content')
            ->set('category', ProductCategory::Audio->value)->call('save')->assertHasErrors('category')
            ->set('category', PostCategory::Community->value)->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('posts', ['title' => 'Example', 'category' => 'Community']);
    }

    public function test_product_category_must_be_a_product_enum_value(): void
    {
        Livewire::actingAs(User::factory()->create())->test(Affiliates::class)
            ->call('create')->set('title', 'Headphones')->set('description', 'Description')
            ->set('merchant', 'Shop')->set('affiliate_url', 'https://example.com/product')
            ->set('category', PostCategory::Community->value)->call('save')->assertHasErrors('category')
            ->set('category', ProductCategory::Audio->value)->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('affiliate_products', ['title' => 'Headphones', 'category' => 'Audio']);
    }
}
