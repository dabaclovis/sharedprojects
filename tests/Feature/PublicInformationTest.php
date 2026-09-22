<?php

namespace Tests\Feature;

use App\Livewire\Pages\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_contact_validates_fields_and_does_not_claim_log_mail_was_delivered(): void
    {
        config(['mail.default' => 'log']);
        Livewire::test(Contact::class)->call('send')->assertHasErrors(['name', 'email', 'subject', 'message'])
            ->set('name', 'Joe Doe')->set('email', 'joe@example.test')->set('subject', 'Question')
            ->set('message', 'Can you help with my account?')->call('send')->assertHasErrors('send');
    }

    public function test_contact_uses_configured_recipient_and_reply_to(): void
    {
        config(['mail.default' => 'smtp', 'site.support_email' => 'info@auditlab.com']);
        Mail::shouldReceive('raw')->once()->withArgs(function ($body, $callback) {
            $message = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email);
            $callback($message);
            $mail = $message->getSymfonyMessage();
            return $body === 'Can you help with my account?'
                && $mail->getTo()[0]->getAddress() === 'info@auditlab.com'
                && $mail->getReplyTo()[0]->getAddress() === 'joe@example.test';
        });
        Livewire::test(Contact::class)->set('name', 'Joe Doe')->set('email', 'joe@example.test')
            ->set('subject', 'Question')->set('message', 'Can you help with my account?')
            ->call('send')->assertHasNoErrors()->assertSet('message', '')->assertSee('Your message has been sent');
    }

    public function test_contact_is_rate_limited_and_handles_mail_failure(): void
    {
        config(['mail.default' => 'smtp']);
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Delivery failed'));
        $component = Livewire::test(Contact::class)->set('name', 'Joe Doe')->set('email', 'joe@example.test')
            ->set('subject', 'Question')->set('message', 'Can you help with my account?');
        $component->call('send')->assertHasErrors('send')->assertSet('message', 'Can you help with my account?');
        $key = 'contact:'.hash('sha256', '127.0.0.1');
        for ($attempt = 0; $attempt < 3; $attempt++) {
            RateLimiter::hit($key, 3600);
        }
        $component->call('send')->assertHasErrors('send')->assertSee('Too many messages');
    }
}
