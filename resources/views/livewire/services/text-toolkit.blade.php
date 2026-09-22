<div class="container py-5">
    <p class="posts-eyebrow">Free tool &middot; No account needed</p>
    <h1 class="h2"><i class="fa-solid fa-pen-to-square w3-text-indigo mr-2" aria-hidden="true"></i>Text toolkit</h1>
    <p class="text-muted">Polish a draft, clean up a list, and check your writing in one place.</p>
    <div class="row age-results mt-4">
        @foreach ($counts as $label => $count)
            <div class="col-6 col-lg-3 mb-3">
                <div class="age-result-card age-result-card--{{ ['indigo', 'teal', 'blue', 'purple'][$loop->index] }} w3-card w3-round-xlarge w3-padding-large">
                    <p class="h3 age-result-value">{{ number_format($count) }}</p>
                    <p class="mb-0 age-result-label">{{ $label }}</p>
                </div>
            </div>
        @endforeach
    </div>
    <section class="dashboard-panel p-4 w3-round-xlarge" aria-label="Text editor"
        x-data="{ copyStatus: '' }">
        <label for="toolkit-text" class="font-weight-bold">Your text</label>
        <textarea id="toolkit-text" x-ref="editor" class="form-control mb-2" rows="12" maxlength="100000"
            wire:model.live.debounce.400ms="text" aria-describedby="toolkit-help" placeholder="Paste or type your text here..."></textarea>
        <p id="toolkit-help" class="small text-muted">Up to 100,000 characters. Reading time estimates 200 words per minute. Text is processed on the server and is not saved to the database.</p>
        @error('text')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
        <h2 class="h6 mt-4">Convert case</h2>
        @foreach (['upper' => 'UPPERCASE', 'lower' => 'lowercase', 'title' => 'Title Case'] as $action => $label)
            <button type="button" class="btn btn-outline-primary mr-2 mb-2 w3-round-large" wire:click="transform('{{ $action }}')" wire:loading.attr="disabled">{{ $label }}</button>
        @endforeach
        <h2 class="h6 mt-3">Clean up</h2>
        @foreach (['spaces' => 'Remove extra spaces', 'blank' => 'Remove blank lines', 'duplicates' => 'Remove duplicate lines'] as $action => $label)
            <button type="button" class="btn btn-outline-secondary mr-2 mb-2 w3-round-large" wire:click="transform('{{ $action }}')" wire:loading.attr="disabled">{{ $label }}</button>
        @endforeach
        <p class="small text-muted">Duplicate removal keeps the first occurrence and compares exact lines, including case and spaces. Space cleanup preserves line breaks. Title Case capitalizes each word, including small words.</p>
        <hr>
        <div class="d-flex flex-wrap">
            <button type="button" class="btn btn-primary mr-2 mb-2" @click="copyStatus = ''; try { await navigator.clipboard.writeText($refs.editor.value); copyStatus = 'Copied to clipboard.'; } catch (error) { copyStatus = 'Copy unavailable. Select the text and copy it manually.'; }"><i class="fa-solid fa-copy mr-1" aria-hidden="true"></i>Copy text</button>
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" wire:click="download" wire:loading.attr="disabled"><i class="fa-solid fa-download mr-1" aria-hidden="true"></i>Download .txt</button>
            <button type="button" class="btn btn-outline-secondary mr-2 mb-2" wire:click="undo" wire:loading.attr="disabled" @disabled($previousText === null)><i class="fa-solid fa-rotate-left mr-1" aria-hidden="true"></i>Undo last action</button>
            <button type="button" class="btn btn-outline-danger mb-2" wire:click="transform('clear')" wire:loading.attr="disabled">Clear text</button>
        </div>
        <p class="small mb-0" role="status">{{ $status }}</p>
        <p class="small mb-0" role="status" x-text="copyStatus"></p>
    </section>
</div>
