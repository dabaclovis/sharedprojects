<?php

namespace App\Livewire\Services;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free percentage calculator')]
class PercentageCalculator extends Component
{
    public string $mode = 'portion';
    public string $first = '';
    public string $second = '';
    public ?float $result = null;

    public function updated(): void
    {
        $this->result = null;
        $this->resetValidation();
    }

    public function calculate(): void
    {
        $this->result = null;
        $this->validate([
            'mode' => ['required', Rule::in(['portion', 'ratio', 'change'])],
            'first' => ['required', 'numeric', 'between:0,1000000000000'],
            'second' => ['required', 'numeric', 'between:0,1000000000000'],
        ]);
        $a = (float) $this->first;
        $b = (float) $this->second;
        if (($this->mode === 'ratio' && $b == 0) || ($this->mode === 'change' && $a == 0)) {
            $this->addError($this->mode === 'ratio' ? 'second' : 'first', 'The starting value or total must be greater than zero.');
            return;
        }
        $this->result = match ($this->mode) {
            'ratio' => $a / $b * 100,
            'change' => ($b - $a) / $a * 100,
            default => $a / 100 * $b,
        };
    }

    public function render()
    {
        return view('livewire.services.percentage-calculator');
    }
}
