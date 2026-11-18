<form wire:submit.prevent="submit" class="dh-panel mt-6 flex flex-wrap items-end gap-3 p-4">
    <div class="min-w-[240px] flex-1">
        <label for="name" class="dh-label">New group</label>
        <input wire:model.live="name" id="name" type="text" class="dh-input" placeholder="e.g. SARs, Bangers…">
    </div>
    <button type="submit" class="dh-btn-primary"><i class="fas fa-plus"></i> Add group</button>
</form>
