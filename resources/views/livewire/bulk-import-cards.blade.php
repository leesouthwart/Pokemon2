<div class="dh-page max-w-5xl">
    <x-page-header eyebrow="Arbitrage" title="Bulk import cards">
        <a href="{{ route('cardrush') }}" class="dh-btn-ghost"><i class="fas fa-arrow-left"></i> Card list</a>
    </x-page-header>

    <p class="mt-3 max-w-3xl text-sm dh-muted">
        One card per line, formatted as <code class="rounded bg-white/10 px-1.5 py-0.5 text-violet-100">search term, card link</code>.
        Optionally add a third column with a comma-free group name to override the default group, e.g.
        <code class="rounded bg-white/10 px-1.5 py-0.5 text-violet-100">magikarp 080 073, https://example.com/card, My Group</code>.
    </p>

    <div class="dh-panel mt-6 space-y-5 p-5">
        <div class="max-w-sm">
            <label for="defaultGroup" class="dh-label">Default group (optional)</label>
            <select wire:model="defaultGroupId" id="defaultGroup" class="dh-select">
                <option value="">None</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="rows" class="dh-label">Cards</label>
            <textarea
                wire:model.defer="rows"
                id="rows"
                rows="14"
                class="dh-textarea font-mono"
                placeholder="magikarp 080 073, https://www.cardrush-pokemon.jp/product/12345&#10;mew 183 172, https://www.cardrush-pokemon.jp/product/67890"></textarea>
        </div>

        <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit" class="dh-btn-primary">
            <span wire:loading.remove wire:target="submit"><i class="fas fa-file-import mr-1"></i> Import</span>
            <span wire:loading wire:target="submit"><i class="fas fa-circle-notch fa-spin mr-1"></i> Queueing…</span>
        </button>
    </div>

    @if($submitted)
        <div class="mt-6 space-y-3">
            @if($dispatched > 0)
                <div class="dh-alert-success">
                    Queued <strong>{{ $dispatched }}</strong> card import {{ \Illuminate\Support\Str::plural('job', $dispatched) }}.
                    They will run in the background and appear in the card list once complete.
                </div>
            @endif

            @if(!empty($errors))
                <div class="dh-alert-error">
                    <p class="mb-2 font-semibold">Skipped {{ count($errors) }} {{ \Illuminate\Support\Str::plural('row', count($errors)) }}:</p>
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>
