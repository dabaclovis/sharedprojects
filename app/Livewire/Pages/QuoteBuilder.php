<?php

namespace App\Livewire\Pages;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free freelance quote builder')]
class QuoteBuilder extends Component
{
    public string $project = '';

    public string $currency = 'USD';

    public array $tasks = [['description' => '', 'hours' => '1', 'rate' => '50']];

    public string $expenses = '0';

    public string $buffer = '0';

    public string $deposit = '0';

    public string $scope = '';

    #[Locked]
    public ?array $result = null;

    public function updated(): void
    {
        $this->result = null;
        $this->resetValidation();
    }

    public function addTask(): void
    {
        if (count($this->tasks) < 20) {
            $this->tasks[] = ['description' => '', 'hours' => '1', 'rate' => '50'];
        }
        $this->updated();
    }

    public function removeTask(int $index): void
    {
        if (count($this->tasks) > 1 && array_key_exists($index, $this->tasks)) {
            unset($this->tasks[$index]);
            $this->tasks = array_values($this->tasks);
        }
        $this->updated();
    }

    public function calculate(): void
    {
        $this->result = null;
        $decimal = ['required', 'numeric', 'min:0', 'max:1000000', 'regex:/^\d+(\.\d{1,2})?$/'];
        $this->validate([
            'project' => ['required', 'string', 'max:150'],
            'currency' => ['required', Rule::in(['USD', 'EUR', 'GBP', 'CAD', 'AUD'])],
            'tasks' => ['required', 'array', 'min:1', 'max:20'],
            'tasks.*' => ['array:description,hours,rate'],
            'tasks.*.description' => ['required', 'string', 'max:150'],
            'tasks.*.hours' => ['required', 'numeric', 'gt:0', 'max:10000', 'regex:/^\d+(\.\d{1,2})?$/'],
            'tasks.*.rate' => $decimal,
            'expenses' => $decimal,
            'buffer' => ['required', 'integer', 'between:0,100'],
            'deposit' => ['required', 'integer', 'between:0,100'],
            'scope' => ['nullable', 'string', 'max:3000'],
        ]);
        $labor = 0;
        $lines = [];
        foreach ($this->tasks as $task) {
            $amount = (int) round(round((float) $task['hours'] * 100) * round((float) $task['rate'] * 100) / 100);
            $labor += $amount;
            $lines[] = $task['description'].' — '.$task['hours'].' hours × '.$this->money((int) round((float) $task['rate'] * 100)).' = '.$this->money($amount);
        }
        $expenses = (int) round((float) $this->expenses * 100);
        $buffer = (int) round($labor * (int) $this->buffer / 100);
        $total = $labor + $expenses + $buffer;
        $deposit = (int) round($total * (int) $this->deposit / 100);
        $summary = "PROJECT ESTIMATE\n{$this->project}\n\n".implode("\n", $lines)
            ."\n\nLabor: ".$this->money($labor)."\nExpenses: ".$this->money($expenses)
            ."\nContingency ({$this->buffer}% of labor): ".$this->money($buffer)
            ."\nEstimated total (before taxes): ".$this->money($total)
            ."\nDeposit ({$this->deposit}%): ".$this->money($deposit)
            ."\nRemaining balance: ".$this->money($total - $deposit)
            .($this->scope !== '' ? "\n\nScope and delivery notes:\n{$this->scope}" : '')
            ."\n\nEstimate based on the scope above. Confirm scope and payment terms with your client.\n";
        $this->result = compact('labor', 'expenses', 'buffer', 'total', 'deposit', 'summary');
        $this->result['balance'] = $total - $deposit;
    }

    public function money(int $cents): string
    {
        return $this->currency.' '.number_format($cents / 100, 2);
    }

    public function download()
    {
        $this->calculate();

        return response()->streamDownload(function () {
            echo $this->result['summary'];
        }, 'project-estimate.txt', ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.pages.quote-builder');
    }
}
