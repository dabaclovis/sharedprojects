<?php

namespace App\Livewire\Pages;

use Carbon\CarbonImmutable;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free date difference calculator')]
class DateDifference extends Component
{
    public string $startDate = '';

    public string $endDate = '';

    public bool $includeEnd = false;

    public ?array $result = null;

    public function updated(): void
    {
        $this->result = null;
        $this->resetValidation();
    }

    public function calculate(): void
    {
        $this->result = null;
        $this->validate([
            'startDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'],
        ]);
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $this->startDate, 'UTC');
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $this->endDate, 'UTC');
        $days = intdiv($end->timestamp - $start->timestamp, 86400) + (int) $this->includeEnd;
        $this->result = ['days' => $days, 'weeks' => intdiv($days, 7), 'remainingDays' => $days % 7];
    }

    public function render()
    {
        return view('livewire.pages.date-difference');
    }
}
