<div class="dh-panel flex flex-wrap items-end gap-3 p-4">
    <div class="min-w-[220px] flex-1">
        <label for="searchTerm" class="dh-label">PSA 10 search term</label>
        <input wire:model.live="searchTerm" id="searchTerm" type="text" class="dh-input" placeholder="magikarp 080 073">
    </div>

    <div class="min-w-[260px] flex-[2]">
        <label for="url" class="dh-label">Cardrush URL</label>
        <input type="email" id="url" name="url" wire:model.live="url" placeholder="https://www.cardrush-pokemon.jp/product/…" class="dh-input">
    </div>

    <button type="button" wire:click="addCard" class="dh-btn-primary"><i class="fas fa-plus"></i> Add card</button>
</div>
