<?php

namespace App\Livewire\Pages;

use App\Models\ContactMessage;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout(
    'components.layouts.app',
    [
        'title' => 'Contact Brotherfall | Questions, Feedback and Support',
        'description' => 'Contact the Brotherfall team with questions, feedback, support requests or ideas for improving our tools and community resources.',
        'keywords' => 'contact Brotherfall, customer support, website feedback, help request',
    ]
)]
class Contact extends Component
{
    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $message = '';

    public function send(): void
    {
        $this->resetValidation();
        foreach (['name', 'email', 'subject', 'message'] as $field) {
            $this->{$field} = trim($this->{$field});
        }
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
        ContactMessage::create($data);
        RateLimiter::hit($key, 3600);
        $this->reset('name', 'email', 'subject', 'message');
        session()->flash('contactStatus', 'Your message has been received. Thank you for getting in touch.');
    }

    public function render()
    {
        return view('livewire.pages.contact');
    }
}
