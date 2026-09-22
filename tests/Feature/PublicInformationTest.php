<?php

namespace Tests\Feature;

use App\Livewire\Pages\Contact;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PublicInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_accessible(): void
    {
        foreach (['pages.about', 'pages.contact', 'pages.policy'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Privacy');
        }
        $this->get(route('pages.contact'))->assertSee('info@auditlab.com');
    }

    public function test_contact_validates_fields_and_saves_message_to_database(): void
    {
        Livewire::test(Contact::class)->call('send')->assertHasErrors(['name', 'email', 'subject', 'message'])
            ->set('name', 'Joe Doe')->set('email', 'joe@example.test')->set('subject', 'Question')
            ->set('message', 'Can you help with my account?')->call('send')->assertHasNoErrors()
            ->assertSet('message', '')->assertSee('Your message has been received');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Joe Doe', 'email' => 'joe@example.test', 'subject' => 'Question',
            'message' => 'Can you help with my account?',
        ]);
    }

    public function test_contact_is_rate_limited(): void
    {
        $component = Livewire::test(Contact::class)->set('name', 'Joe Doe')->set('email', 'joe@example.test')
            ->set('subject', 'Question')->set('message', 'Can you help with my account?');
        $key = 'contact:'.hash('sha256', '127.0.0.1');
        for ($attempt = 0; $attempt < 3; $attempt++) {
            RateLimiter::hit($key, 3600);
        }
        $component->call('send')->assertHasErrors('send')->assertSee('Too many messages');
        $this->assertSame(0, ContactMessage::count());
    }
}
