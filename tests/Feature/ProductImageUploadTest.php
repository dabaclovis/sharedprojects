<?php

namespace Tests\Feature;

use App\Livewire\Users\Products as Affiliates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private function imageFile(string $name = 'photo.png'): \Illuminate\Http\Testing\File
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9V8AAAAASUVORK5CYII='));
    }

    public function test_upload_can_be_saved_preserved_replaced_and_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(Affiliates::class)->call('create')
            ->set('title', 'Product')->set('description', 'Description')->set('merchant', 'Store')
            ->set('affiliate_url', 'https://example.com/product')->set('image_url', 'https://example.com/image.jpg')
            ->set('image', $this->imageFile())
            ->call('save')->assertHasNoErrors()->assertSet('image', null);
        $product = $user->affiliateProducts()->firstOrFail();
        $firstPath = $product->image_path;
        Storage::disk('public')->assertExists($firstPath);
        $this->assertSame(Storage::disk('public')->url($firstPath), $product->image_source);
        $component->call('edit', $product->id)->call('save')->assertHasNoErrors();
        $this->assertSame($firstPath, $product->fresh()->image_path);
        $component->call('edit', $product->id)->set('image', $this->imageFile('replacement.png'))
            ->call('save')->assertHasNoErrors();
        $secondPath = $product->fresh()->image_path;
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
        $component->call('edit', $product->id)->set('removeImage', true)->call('save')->assertHasNoErrors();
        Storage::disk('public')->assertMissing($secondPath);
        $this->assertNull($product->fresh()->image_path);
        $this->assertSame('https://example.com/image.jpg', $product->fresh()->image_source);
    }

    public function test_invalid_and_oversize_files_are_rejected(): void
    {
        Storage::fake('public');
        $component = Livewire::actingAs(User::factory()->create())->test(Affiliates::class)->call('create')
            ->set('title', 'Product')->set('description', 'Description')->set('merchant', 'Store')
            ->set('affiliate_url', 'https://example.com/product');
        $component->set('image', UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'))
            ->call('save')->assertHasErrors('image');
        $component->set('image', $this->imageFile('large.png')->size(5121))
            ->call('save')->assertHasErrors('image');
        $this->assertDatabaseCount('affiliate_products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
