<?php

namespace App\Livewire\Services;

use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free text toolkit')]
class TextToolkit extends Component
{
    public string $text = '';

    #[Locked]
    public ?string $previousText = null;

    public string $status = '';

    private function validateText(): void
    {
        $this->validate(['text' => ['string', 'max:100000']]);
    }

    public function transform(string $action): void
    {
        $this->validateText();
        $lines = preg_split('/\R/u', $this->text);
        $result = match ($action) {
            'upper' => mb_strtoupper($this->text),
            'lower' => mb_strtolower($this->text),
            'title' => mb_convert_case($this->text, MB_CASE_TITLE),
            'spaces' => implode("\n", array_map(fn ($line) => trim(preg_replace('/[\p{Z}\t]+/u', ' ', $line)), $lines)),
            'blank' => implode("\n", array_filter($lines, fn ($line) => preg_match('/\S/u', $line))),
            'duplicates' => implode("\n", array_unique($lines)),
            'clear' => '',
            default => null,
        };
        if ($result === null) {
            $this->addError('text', 'Choose one of the available text actions.');
            return;
        }
        if (mb_strlen($result) > 100000) {
            $this->addError('text', 'The edited text exceeds the 100,000-character limit.');
            return;
        }
        $this->previousText = $this->text;
        $this->text = $result;
        $this->status = 'Text updated. Undo is available.';
    }

    public function undo(): void
    {
        if ($this->previousText !== null) {
            $this->text = $this->previousText;
            $this->previousText = null;
            $this->resetValidation();
            $this->status = 'Previous text restored.';
        }
    }

    public function updatedText(): void
    {
        $this->status = '';
        $this->resetValidation();
        $this->validateText();
    }

    public function download()
    {
        $this->validateText();
        return response()->streamDownload(function () {
            echo $this->text;
        }, 'edited-text.txt', ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function render()
    {
        $text = mb_substr($this->text, 0, 100000);
        preg_match_all("/[\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*/u", $text, $matches);
        $words = count($matches[0]);
        return view('livewire.services.text-toolkit', ['counts' => [
            'Words' => $words,
            'Characters' => mb_strlen($text),
            'Lines' => $text === '' ? 0 : count(preg_split('/\R/u', $text)),
            'Reading minutes' => (int) ceil($words / 200),
        ]]);
    }
}
