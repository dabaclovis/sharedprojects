<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Free word counter')]
class WordCounter extends Component
{
    public string $text = '';

    public ?array $counts = null;

    public function countWords(): void
    {
        $this->counts = null;
        $this->validate(['text' => ['nullable', 'string', 'max:100000']]);
        preg_match_all("/[\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*/u", $this->text, $matches);
        $words = count($matches[0]);
        $this->counts = [
            'Words' => $words,
            'Characters' => mb_strlen($this->text),
            'Characters without spaces' => mb_strlen(preg_replace('/\s/u', '', $this->text)),
            'Reading time (minutes)' => $words === 0 ? 0 : (int) ceil($words / 200),
        ];
    }

    public function clear(): void
    {
        $this->reset('text', 'counts');
        $this->resetValidation();
    }

    public function updatedText(): void
    {
        $this->counts = null;
    }

    public function render()
    {
        return view('livewire.pages.word-counter');
    }
}
