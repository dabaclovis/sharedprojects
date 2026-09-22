<div class="container py-5">
    <p class="posts-eyebrow">Free tool &middot; No account needed</p>
    <h1 class="h2"><i class="fa-solid fa-file-invoice-dollar w3-text-teal mr-2" aria-hidden="true"></i>Freelance quote builder</h1>
    <p class="text-muted">Turn your next project into a clear estimate. Break down the work, include expenses, and plan a deposit.</p>
    <section class="dashboard-panel p-4 w3-round-xlarge">
        <form wire:submit="calculate">
            <div class="row">
                <div class="col-md-8 mb-3"><label for="quote-project">Project name</label><input id="quote-project" class="form-control" wire:model.live.debounce.400ms="project" maxlength="150" required placeholder="e.g. Website design for a local bakery"></div>
                <div class="col-md-4 mb-3"><label for="quote-currency">Currency</label><select id="quote-currency" class="custom-select" wire:model.live="currency">@foreach (['USD', 'EUR', 'GBP', 'CAD', 'AUD'] as $code)<option>{{ $code }}</option>@endforeach</select></div>
            </div>
            <h2 class="h5 mt-3">Project tasks</h2>
            @foreach ($tasks as $index => $task)
                <div class="row align-items-end p-2 mb-3 w3-light-grey w3-round-large" wire:key="quote-task-{{ $index }}">
                    <div class="col-md-5 mb-2"><label for="task-{{ $index }}">Task {{ $index + 1 }}</label><input id="task-{{ $index }}" class="form-control" wire:model.live.debounce.400ms="tasks.{{ $index }}.description" maxlength="150" required placeholder="e.g. Design and revisions"></div>
                    <div class="col-6 col-md-2 mb-2"><label for="hours-{{ $index }}">Hours</label><input id="hours-{{ $index }}" type="number" min="0.01" max="10000" step="0.01" class="form-control" wire:model.live="tasks.{{ $index }}.hours" required></div>
                    <div class="col-6 col-md-3 mb-2"><label for="rate-{{ $index }}">Hourly rate</label><input id="rate-{{ $index }}" type="number" min="0" max="1000000" step="0.01" class="form-control" wire:model.live="tasks.{{ $index }}.rate" required></div>
                    <div class="col-md-2 mb-2"><button type="button" class="btn btn-outline-danger" wire:click="removeTask({{ $index }})" wire:loading.attr="disabled" @disabled(count($tasks) === 1) aria-label="Remove task {{ $index + 1 }}">Remove</button></div>
                </div>
            @endforeach
            <button type="button" class="btn btn-outline-primary mb-4" wire:click="addTask" wire:loading.attr="disabled" @disabled(count($tasks) >= 20)><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>Add task</button>
            <div class="row">
                @foreach (['expenses' => 'Project expenses', 'buffer' => 'Contingency (% of labor)', 'deposit' => 'Deposit (% of total)'] as $field => $label)
                    <div class="col-md-4 mb-3"><label for="quote-{{ $field }}">{{ $label }}</label><input id="quote-{{ $field }}" type="number" min="0" max="{{ $field === 'expenses' ? 1000000 : 100 }}" step="{{ $field === 'expenses' ? '0.01' : '1' }}" class="form-control" wire:model.live="{{ $field }}" required></div>
                @endforeach
            </div>
            <label for="quote-scope">Scope and delivery notes (optional)</label>
            <textarea id="quote-scope" class="form-control mb-3" rows="3" maxlength="3000" wire:model.live.debounce.400ms="scope" placeholder="Deliverables, included revisions, timeline, and exclusions"></textarea>
            <p class="small text-muted">Use your own rates. Contingency is added to labor only; taxes are not included. Currency selection labels your amounts and does not convert them. Entries are not saved to the database.</p>
            @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">Build estimate</button>
        </form>
    </section>
    <div aria-live="polite">
        @if ($result !== null)
            <section class="mt-4" aria-label="Project estimate">
                <div class="row age-results">
                    @foreach (['total' => 'Estimated total · before taxes', 'deposit' => 'Requested deposit', 'balance' => 'Remaining balance'] as $key => $label)
                        <div class="col-md-4 mb-3"><div class="age-result-card age-result-card--{{ ['teal', 'blue', 'purple'][$loop->index] }} w3-card w3-round-xlarge w3-padding-large"><p class="h3 age-result-value">{{ $this->money($result[$key]) }}</p><p class="age-result-label mb-0">{{ $label }}</p></div></div>
                    @endforeach
                </div>
                <div class="dashboard-panel p-4" x-data="{ status: '' }">
                    <h2 class="h5">Your estimate is ready</h2>
                    <label for="quote-summary">Copy or download this draft for your client</label>
                    <textarea id="quote-summary" x-ref="summary" class="form-control mb-3" rows="14" readonly>{{ $result['summary'] }}</textarea>
                    <button type="button" class="btn btn-primary mr-2 mb-2" @click="try { await navigator.clipboard.writeText($refs.summary.value); status = 'Estimate copied.'; } catch (error) { status = 'Select the estimate and copy it manually.'; }"><i class="fa-solid fa-copy mr-1" aria-hidden="true"></i>Copy estimate</button>
                    <button type="button" class="btn btn-outline-primary mb-2" wire:click="download" wire:loading.attr="disabled"><i class="fa-solid fa-download mr-1" aria-hidden="true"></i>Download .txt</button>
                    <p class="small mb-0" role="status" x-text="status"></p>
                </div>
            </section>
        @endif
    </div>
</div>
