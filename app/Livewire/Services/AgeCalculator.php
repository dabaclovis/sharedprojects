<?php

namespace App\Livewire\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free age calculator')]
class AgeCalculator extends Component
{
    public string $dateOfBirth = '';

    public ?array $totals = null;

    public ?string $calculatedAt = null;

    public function updatedDateOfBirth(): void
    {
        $this->reset('totals', 'calculatedAt');
        $this->resetValidation();
    }

    public function calculate(): void
    {
        $this->reset('totals', 'calculatedAt');
        $now = CarbonImmutable::now('UTC');
        $this->validate(['dateOfBirth' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01', 'before_or_equal:'.$now->toDateString()]]);
        $birth = CarbonImmutable::createFromFormat('!Y-m-d', $this->dateOfBirth, 'UTC');
        $seconds = $now->timestamp - $birth->timestamp;
        $interval = $birth->diff($now);
        DB::table('age_calculation_logs')->insert([
            'age_years' => $interval->y,
            'age_months' => $interval->m,
            'age_days' => $interval->d,
            'ip_address' => request()->ip(),
            'calculated_at' => $now,
        ]);
        $this->totals = [
            'Seconds' => $seconds,
            'Minutes' => intdiv($seconds, 60),
            'Hours' => intdiv($seconds, 3600),
            'Days' => intdiv($seconds, 86400),
            'Weeks' => intdiv($seconds, 604800),
            'Months' => $interval->y * 12 + $interval->m,
            'Years' => $interval->y,
        ];
        $this->calculatedAt = $now->format('M j, Y H:i:s').' UTC';
    }

    public function render()
    {
        return view('livewire.services.age-calculator', ['today' => CarbonImmutable::now('UTC')->toDateString()]);
    }
}
