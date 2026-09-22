<?php

namespace App\Livewire\Pages;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contact CD')]
class Contact extends Component
{
    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $message = '';

    public function send(): void
    {
        $this->resetValidation();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);
        $key = 'contact:'.hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('send', 'Too many messages. Please try again in an hour.');
            return;
        }
        $recipient = config('site.support_email');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->addError('send', 'The contact form is currently unavailable. Please use the contact details on this page.');
            return;
        }
        RateLimiter::hit($key, 3600);
        try {
            Mail::raw($data['message'], function ($mail) use ($data, $recipient) {
                $mail->to($recipient)->replyTo($data['email'], $data['name'])
                    ->subject('['.config('app.name').'] '.$data['subject']);
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('send', 'Your message could not be sent. Please try again later or email us directly.');
            return;
        }
        $this->reset('name', 'email', 'subject', 'message');
        session()->flash('contactStatus', 'Your message has been sent. Thank you for getting in touch.');
    }

    public function render()
    {
        return view('livewire.pages.contact');
    }
}
