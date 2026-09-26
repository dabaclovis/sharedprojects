<?php

namespace App\Livewire\Admins;

use App\Models\ServiceOrder;
use App\Services\SiteAudit\Analyzer;
use App\Services\SiteAudit\SafeFetcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class RevenueServices extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    #[Locked]
    public string $service = 'website-audit';

    #[Locked]
    public ?int $orderId = null;

    #[Locked]
    public ?string $revision = null;

    public string $search = '';

    public string $status = '';

    public string $amount = '';

    public string $currency = 'USD';

    public string $paymentReference = '';

    public string $internalNotes = '';

    public string $deliverable = '';

    public string $sponsorName = '';

    public string $sponsorTitle = '';

    public string $sponsorDescription = '';

    public string $sponsorUrl = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'admin' && auth()->user()->status === 'active', 403);
    }

    public function mount(string $service = 'website-audit'): void
    {
        abort_unless(array_key_exists($service, ServiceOrder::SERVICES), 404);
        $this->service = $service;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $order = ServiceOrder::where('service', $this->service)->findOrFail($id);
        $this->resetValidation();
        $this->orderId = $order->id;
        $this->revision = $order->revision();
        $this->amount = $order->amount_cents === null ? '' : number_format($order->amount_cents / 100, 2, '.', '');
        $this->currency = $order->currency;
        $this->paymentReference = $order->payment_reference ?? '';
        foreach (['internalNotes' => 'internal_notes', 'deliverable' => 'deliverable', 'sponsorName' => 'sponsor_name', 'sponsorTitle' => 'sponsor_title', 'sponsorDescription' => 'sponsor_description', 'sponsorUrl' => 'sponsor_url'] as $property => $field) {
            $this->{$property} = $order->{$field} ?? '';
        }
        $this->startsAt = $order->starts_at?->utc()->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $order->ends_at?->utc()->format('Y-m-d\TH:i') ?? '';
    }

    public function close(): void
    {
        $this->reset('orderId', 'revision');
        $this->resetValidation();
    }

    private function change(string $action, callable $callback): void
    {
        DB::transaction(function () use ($action, $callback) {
            $order = ServiceOrder::where('service', $this->service)->lockForUpdate()->findOrFail($this->orderId);
            if ($this->revision !== $order->revision()) {
                throw ValidationException::withMessages(['workflow' => 'Another admin changed this request. Copy any unsaved text, close it, and reopen before continuing.']);
            }
            $callback($order);
            $history = $order->history ?? [];
            $history[] = ['action' => $action, 'admin_id' => auth()->id(), 'at' => now()->toIso8601String(), 'status' => $order->status, 'payment_status' => $order->payment_status, 'amount_cents' => $order->amount_cents, 'currency' => $order->currency, 'payment_reference' => $order->payment_reference];
            $order->history = $history;
            $order->save();
            $this->revision = $order->fresh()->revision();
        });
        $this->resetValidation();
        session()->flash('serviceStatus', $action.'.');
    }

    private function requireState(bool $allowed, string $message): void
    {
        if (! $allowed) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }

    public function quote(): void
    {
        $this->amount = trim($this->amount);
        $this->validate(['amount' => ['required', 'regex:/^\d{1,6}(\.\d{1,2})?$/', 'numeric', 'min:1'], 'currency' => ['required', Rule::in(['USD', 'EUR', 'GBP', 'INR', 'NGN', 'KES', 'ZAR'])]]);
        [$whole, $fraction] = array_pad(explode('.', $this->amount), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        $this->change('Quote saved', function ($order) use ($cents) {
            $this->requireState(in_array($order->status, ['new', 'quoted']) && $order->payment_status === 'unpaid', 'Only an unpaid, open request can be quoted.');
            $order->amount_cents = $cents;
            $order->currency = $this->currency;
            $order->status = 'quoted';
        });
    }

    public function recordPayment(): void
    {
        $this->paymentReference = trim($this->paymentReference);
        $this->validate(['paymentReference' => ['required', 'string', 'min:3', 'max:255']]);
        $this->change('Payment recorded', function ($order) {
            $this->requireState($order->status === 'quoted' && $order->payment_status === 'unpaid' && $order->amount_cents > 0, 'Save a quote before recording its verified payment.');
            $order->payment_status = 'paid';
            $order->payment_reference = $this->paymentReference;
            $order->paid_at = now();
        });
    }

    public function recordRefund(): void
    {
        $this->change('Full refund recorded', function ($order) {
            $this->requireState($order->payment_status === 'paid', 'Only a paid request can be marked refunded.');
            $order->payment_status = 'refunded';
            $order->status = 'cancelled';
        });
    }

    public function cancelOrder(): void
    {
        $this->change('Request cancelled', function ($order) {
            $this->requireState($order->payment_status === 'unpaid' && in_array($order->status, ['new', 'quoted']), 'Paid requests must be refunded before cancellation.');
            $order->status = 'cancelled';
        });
    }

    public function saveNotes(): void
    {
        $this->validate(['internalNotes' => ['nullable', 'string', 'max:10000']]);
        $this->change('Internal notes saved', fn ($order) => $order->internal_notes = trim($this->internalNotes));
    }

    public function startAudit(): void
    {
        $this->change('Audit started', function ($order) {
            $this->requireState($order->service === 'website-audit' && $order->status === 'quoted' && $order->payment_status === 'paid', 'A paid audit quote is required before starting work.');
            $order->status = 'in_progress';
        });
    }

    public function runAudit(): void
    {
        $order = ServiceOrder::where('service', $this->service)->findOrFail($this->orderId);
        $this->requireState($order->service === 'website-audit' && $order->status === 'in_progress' && $order->payment_status === 'paid', 'Start a paid audit before generating findings.');
        $key = 'paid-audit:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('workflow', 'Please wait a few minutes before running another audit.');

            return;
        }
        RateLimiter::hit($key, 600);
        try {
            $fetcher = app(SafeFetcher::class);
            $url = $fetcher->normalize($order->website);
            $analyzer = app(Analyzer::class);
            $robots = $fetcher->fetch(parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).'/robots.txt');
            if (! in_array($robots['status'], [200, 404, 410]) || ($robots['status'] === 200 && ! $analyzer->allowed($url, $robots['body']))) {
                throw new \RuntimeException('The website cannot be checked because robots.txt blocks this page or could not be read.');
            }
            $response = $fetcher->fetch($url);
            if ($response['status'] !== 200 || ! preg_match('~(?:text/html|application/xhtml\+xml)~i', $response['headers']['content-type'] ?? '')) {
                throw new \RuntimeException('The page did not return a successful HTML response. Ask the client for the final, accessible page address.');
            }
            $result = $analyzer->analyze($response);
            $result['checked_at'] = now()->toIso8601String();
        } catch (\RuntimeException $exception) {
            $this->addError('workflow', $exception->getMessage());

            return;
        }
        $this->change('Audit findings generated', function ($order) use ($result) {
            $order->audit_result = $result;
        });
    }

    public function saveReport(): void
    {
        $this->validate(['deliverable' => ['required', 'string', 'max:30000']]);
        $this->change('Report draft saved', function ($order) {
            $this->requireState($order->service === 'website-audit' && $order->status === 'in_progress' && $order->payment_status === 'paid', 'Only an audit in progress can be edited.');
            $order->deliverable = trim($this->deliverable);
        });
    }

    public function completeAudit(): void
    {
        $this->deliverable = trim($this->deliverable);
        $this->validate(['deliverable' => ['required', 'string', 'min:50', 'max:30000']]);
        $this->change('Audit completed', function ($order) {
            $this->requireState($order->service === 'website-audit' && $order->status === 'in_progress' && $order->payment_status === 'paid' && ! empty($order->audit_result), 'Generate findings for a paid audit before completing the report.');
            $order->deliverable = $this->deliverable;
            $order->status = 'completed';
        });
    }

    public function activateSponsor(): void
    {
        foreach (['sponsorName', 'sponsorTitle', 'sponsorDescription', 'sponsorUrl'] as $field) {
            $this->{$field} = trim($this->{$field});
        }
        $this->validate([
            'sponsorName' => ['required', 'string', 'max:100'],
            'sponsorTitle' => ['required', 'string', 'max:120'],
            'sponsorDescription' => ['required', 'string', 'max:400'],
            'sponsorUrl' => ['required', 'url:https', 'max:2048'],
            'startsAt' => ['required', 'date_format:Y-m-d\TH:i'],
            'endsAt' => ['required', 'date_format:Y-m-d\TH:i', 'after:startsAt'],
        ]);
        $starts = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->startsAt, 'UTC')->startOfMinute()->setTimezone(config('app.timezone'));
        $ends = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->endsAt, 'UTC')->startOfMinute()->setTimezone(config('app.timezone'));
        $this->requireState($ends->isFuture(), 'The placement must end in the future.');
        $this->change('Sponsorship scheduled', function ($order) use ($starts, $ends) {
            $this->requireState($order->service === 'sponsorship' && $order->payment_status === 'paid' && in_array($order->status, ['quoted', 'in_progress']), 'A paid sponsorship quote is required before scheduling.');
            $order->fill(['sponsor_name' => $this->sponsorName, 'sponsor_title' => $this->sponsorTitle, 'sponsor_description' => $this->sponsorDescription, 'sponsor_url' => $this->sponsorUrl, 'starts_at' => $starts, 'ends_at' => $ends, 'status' => 'in_progress']);
        });
    }

    public function render()
    {
        $base = ServiceOrder::where('service', $this->service);
        $order = $this->orderId ? (clone $base)->findOrFail($this->orderId) : null;

        return view('livewire.admins.revenue-services', [
            'serviceTitle' => ServiceOrder::SERVICES[$this->service],
            'orders' => (clone $base)->select(['id', 'reference', 'name', 'email', 'status', 'payment_status', 'amount_cents', 'currency', 'created_at'])
                ->when(trim($this->search) !== '', function ($query) {
                    $search = '%'.mb_substr(trim($this->search), 0, 200).'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('reference', 'like', $search));
                })
                ->when(in_array($this->status, ServiceOrder::STATUSES), fn ($query) => $query->where('status', $this->status))
                ->latest('id')->paginate(10),
            'order' => $order,
            'newCount' => (clone $base)->where('status', 'new')->count(),
            'paidTotals' => (clone $base)->where('payment_status', 'paid')->selectRaw('currency, SUM(amount_cents) as total')->groupBy('currency')->pluck('total', 'currency'),
            'reportUrl' => $order?->service === 'website-audit' && $order->status === 'completed' && $order->payment_status === 'paid'
                ? URL::temporarySignedRoute('pages.business-report', now()->addDays(30), ['order' => $order->reference]) : null,
        ])->title(ServiceOrder::SERVICES[$this->service]);
    }
}
