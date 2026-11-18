<div class="mt-6 flex flex-wrap items-center gap-3 text-sm">
    <label class="flex items-center gap-2">
        <span class="dh-muted">Card</span>
        <select wire:model.live="cardFilter" class="dh-select w-auto">
            <option value="both">Any</option>
            <option value="card_only">Matched to a card</option>
            <option value="only_no_card">No card match</option>
        </select>
    </label>

    <label class="flex items-center gap-2">
        <span class="dh-muted">Pending bid</span>
        <select wire:model.live="pendingBidFilter" class="dh-select w-auto">
            <option value="both">Any</option>
            <option value="with_pending_bid">With pending bid</option>
            <option value="without_pending_bid">Without pending bid</option>
        </select>
    </label>
</div>
