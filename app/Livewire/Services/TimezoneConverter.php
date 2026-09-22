<?php

namespace App\Livewire\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free time zone converter')]
class TimezoneConverter extends Component
{
    public string $dateTime = '';

    public string $fromZone = 'UTC';

    public string $toZone = 'America/New_York';

    public ?string $result = null;

    public function mount(): void
    {
        $this->dateTime = CarbonImmutable::now('UTC')->format('Y-m-d\TH:i');
    }

    public function updated(): void
    {
        $this->result = null;
        $this->resetValidation();
    }

    public function convert(): void
    {
        $this->result = null;
        $this->validate([
            'dateTime' => ['required', 'date_format:Y-m-d\TH:i'],
            'fromZone' => ['required', Rule::in(timezone_identifiers_list())],
            'toZone' => ['required', Rule::in(timezone_identifiers_list())],
        ]);
        $local = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $this->dateTime, $this->fromZone);
        if ($local->format('Y-m-d\TH:i') !== $this->dateTime) {
            $this->addError('dateTime', 'This time does not exist because the clocks move forward. Choose another time.');

            return;
        }
        // A repeated local hour identifies two different instants; do not silently pick one.
        $wall = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $this->dateTime, 'UTC')->timestamp;
        $transitions = (new \DateTimeZone($this->fromZone))->getTransitions($wall - 172800, $wall + 172800);
        $matches = [];
        foreach ($transitions as $transition) {
            $candidate = $wall - $transition['offset'];
            if (CarbonImmutable::createFromTimestampUTC($candidate)->setTimezone($this->fromZone)->format('Y-m-d\TH:i') === $this->dateTime) {
                $matches[$candidate] = true;
            }
        }
        if (count($matches) > 1) {
            $this->addError('dateTime', 'This time occurs twice because the clocks move back. Choose a time outside the repeated hour.');

            return;
        }
        $this->result = $local->setTimezone($this->toZone)->format('D, M j, Y — H:i (P)').' '.$this->toZone;
    }

    public function render()
    {
        return view('livewire.services.timezone-converter', ['timezones' => timezone_identifiers_list()]);
    }
}
