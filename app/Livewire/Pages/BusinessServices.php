<?php

namespace App\Livewire\Pages;

use App\Models\ServiceOrder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Business services')]
class BusinessServices extends Component
{
    public string $service = 'website-audit';

    public string $name = '';

    public string $email = '';

    public string $website = '';

    public string $brief = '';

    #[Locked]
    public ?string $reference = null;

    public function chooseService(string $service): void
    {
        validator(['service' => $service], [
            'service' => ['required', Rule::in(array_keys(ServiceOrder::SERVICES))],
        ])->validate();

        if ($this->reference !== null) {
            $this->reset('reference', 'name', 'email', 'website', 'brief');
            $this->resetValidation();
        }

        $this->service = $service;
        $this->resetValidation('service');
        $this->dispatch('business-service-selected');
    }

    public function submit(): void
    {
        if ($this->reference) {
            return;
        }
        $this->resetValidation();
        foreach (['name', 'email', 'website', 'brief'] as $field) {
            $this->{$field} = trim($this->{$field});
        }
        if ($this->website !== '' && ! preg_match('~^[a-z][a-z0-9+.-]*:~i', $this->website)) {
            $this->website = 'https://'.ltrim($this->website, '/');
        }
        try {
            $data = $this->validate([
                'service' => ['required', Rule::in(array_keys(ServiceOrder::SERVICES))],
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255'],
                'website' => ['required', 'url:http,https', 'max:2048'],
                'brief' => ['required', 'string', 'min:20', 'max:5000'],
            ], [
                'website.url' => 'Enter a valid website address, such as example.com or https://example.com.',
                'brief.min' => 'Please describe your request in at least 20 characters.',
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('business-submit-failed');
            throw $exception;
        }
        $key = 'business-inquiry:'.hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('submit', 'Too many requests. Please try again in an hour.');
            $this->dispatch('business-submit-failed');

            return;
        }
        $data['reference'] = (string) Str::uuid();
        try {
            $order = DB::transaction(fn () => ServiceOrder::create($data));
        } catch (QueryException $exception) {
            report($exception);
            $this->addError('submit', 'Your request could not be saved. Your details are still here; please try again shortly.');
            $this->dispatch('business-submit-failed');

            return;
        }
        RateLimiter::hit($key, 3600);
        $this->reference = $order->reference;
        $this->reset('name', 'email', 'website', 'brief');
        $this->dispatch('business-request-saved');
    }

    public function render()
    {
        return view('livewire.pages.business-services');
    }
}
