<?php

namespace App\Livewire\Pages;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free unit converter')]
class UnitConverter extends Component
{
    public const UNITS = [
        'length' => ['mm' => ['Millimeters', 0.001], 'cm' => ['Centimeters', 0.01], 'm' => ['Meters', 1], 'km' => ['Kilometers', 1000], 'in' => ['Inches', 0.0254], 'ft' => ['Feet', 0.3048], 'yd' => ['Yards', 0.9144], 'mi' => ['Miles', 1609.344]],
        'weight' => ['g' => ['Grams', 0.001], 'kg' => ['Kilograms', 1], 'oz' => ['Ounces', 0.028349523125], 'lb' => ['Pounds', 0.45359237]],
    ];

    public string $category = 'length';

    public string $amount = '';

    public string $fromUnit = 'm';

    public string $toUnit = 'ft';

    public ?float $result = null;

    public function updatedCategory(): void
    {
        $this->fromUnit = $this->category === 'weight' ? 'kg' : 'm';
        $this->toUnit = $this->category === 'weight' ? 'lb' : 'ft';
    }

    public function updated(): void
    {
        $this->result = null;
        $this->resetValidation();
    }

    public function convert(): void
    {
        $this->result = null;
        $units = self::UNITS[$this->category] ?? [];
        $this->validate([
            'category' => ['required', Rule::in(array_keys(self::UNITS))],
            'amount' => ['required', 'numeric', 'between:0,1000000000000'],
            'fromUnit' => ['required', Rule::in(array_keys($units))],
            'toUnit' => ['required', Rule::in(array_keys($units))],
        ]);
        $this->result = (float) $this->amount * $units[$this->fromUnit][1] / $units[$this->toUnit][1];
    }

    public function render()
    {
        return view('livewire.pages.unit-converter', ['units' => self::UNITS[$this->category] ?? []]);
    }
}
