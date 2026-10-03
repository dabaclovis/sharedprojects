<?php

namespace App\Livewire\Pages;

use App\Models\ApplicationRating;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RatingPrompt extends Component
{
    public $score = null;

    public string $feedback = '';

    #[Locked]
    public bool $eligible = false;

    #[Locked]
    public int $delay = 90000;

    #[Locked]
    public bool $submitted = false;

    public function mount(): void
    {
        if (! session()->has('rating.started_at')) {
            session()->put('rating.started_at', now()->timestamp);
            session()->put('rating.visitor', (string) Str::uuid());
        }
        $this->delay = max(0, (91 - (now()->timestamp - session('rating.started_at'))) * 1000);
        $this->refreshEligibility();
    }

    public function refreshEligibility(): bool
    {
        $ipAddress = request()->ip();
        $this->eligible = ! session('rating.dismissed') && ! session('rating.submitted')
            && $ipAddress !== null && $ipAddress !== ''
            && ! ApplicationRating::where('ip_address', $ipAddress)->exists()
            && ! (auth()->check() && ApplicationRating::where('user_id', auth()->id())->exists());

        return $this->eligible;
    }

    public function dismiss(): void
    {
        session()->put('rating.dismissed', true);
        $this->eligible = false;
    }

    public function submit(): void
    {
        $this->resetValidation();
        if (! session('rating.visitor') || now()->timestamp - session('rating.started_at', now()->timestamp) <= 90) {
            $this->addError('submission', 'Please spend a little more time exploring before rating.');

            return;
        }
        if (session('rating.submitted')) {
            $this->submitted = true;

            return;
        }
        $ipAddress = request()->ip();
        if ($ipAddress === null || $ipAddress === '') {
            $this->addError('submission', 'We could not verify your network address. Your rating was not submitted.');

            return;
        }
        $this->feedback = trim($this->feedback);
        $this->validate(['score' => ['required', 'integer', 'between:1,5'], 'feedback' => ['nullable', 'string', 'max:1000']]);
        $key = 'application-rating:' . hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('submission', 'Too many submissions. Please try again later.');

            return;
        }
        if (ApplicationRating::where('ip_address', $ipAddress)->exists()) {
            $this->addError('submission', 'This IP address has already submitted a rating.');

            return;
        }
        $visitor = hash('sha256', session('rating.visitor'));
        try {
            ApplicationRating::firstOrCreate(
                auth()->check() ? ['user_id' => auth()->id()] : ['visitor_hash' => $visitor],
                ['visitor_hash' => $visitor, 'score' => (int) $this->score, 'feedback' => $this->feedback ?: null, 'ip_address' => $ipAddress]
            );
        } catch (QueryException $exception) {
            if (ApplicationRating::where('ip_address', $ipAddress)->exists()) {
                $this->addError('submission', 'This IP address has already submitted a rating.');

                return;
            }
            report($exception);
            $this->addError('submission', 'We could not save your rating. Please try again.');

            return;
        }
        RateLimiter::hit($key, 3600);
        session()->put('rating.submitted', true);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.pages.rating-prompt');
    }
}
