<div class="dh-page">
    <x-page-header eyebrow="Groups" title="Bulk add to group">
        <a href="{{ route('card_group.index') }}" class="dh-btn-ghost"><i class="fas fa-folder"></i> All groups</a>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="dh-panel p-5">
            <h2 class="dh-h2">Create new group</h2>
            <div class="mt-4 flex flex-col gap-3">
                <input type="text" wire:model.defer="newGroupName" placeholder="Group name…" class="dh-input">

                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="useInBuylistGeneration" class="dh-check">
                    Use in buylist generation
                </label>

                <div>
                    <button type="button" wire:click="createGroup" class="dh-btn-primary"><i class="fas fa-plus"></i> Create group</button>
                </div>
            </div>
        </div>

        <div class="dh-panel p-5">
            <h2 class="dh-h2">Target group</h2>
            <select wire:model.live="selectedGroupId" class="dh-select mt-4">
                <option value="">Select a group…</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->cards()->count() }})</option>
                @endforeach
            </select>

            @if($selectedGroup)
                <p class="mt-3 text-sm dh-muted">
                    Currently <span class="text-white">{{ $selectedGroup->cards()->count() }}</span>
                    {{ \Illuminate\Support\Str::plural('card', $selectedGroup->cards()->count()) }}
                    in <strong class="text-white">{{ $selectedGroup->name }}</strong>.
                </p>
            @endif

            <label class="mt-3 inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="hideAlreadyInGroup" class="dh-check">
                Hide cards already in selected group
            </label>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm">
        <div class="relative w-full sm:w-72">
            <i class="fas fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs text-violet-200/50"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search cards" class="dh-input pl-10">
        </div>

        <button type="button" wire:click="selectAllOnPage({{ json_encode($pageCardIds) }})" class="dh-chip">Select page</button>
        <button type="button" wire:click="clearSelection" class="dh-chip">Clear selection</button>

        <div class="ml-auto flex items-center gap-3">
            <span class="dh-muted">Selected <strong class="text-white tnum">{{ count($selectedCards) }}</strong></span>
            <button type="button" wire:click="addSelectedToGroup" @disabled(!$selectedGroupId || empty($selectedCards)) class="dh-btn-primary">
                <i class="fas fa-folder-plus"></i> Add {{ count($selectedCards) }} to group
            </button>
        </div>
    </div>

    <div class="dh-panel mt-6 overflow-x-auto">
        <table class="dh-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Card</th>
                    <th class="text-right">Raw price (JPY)</th>
                    <th>Groups</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cards as $card)
                    <tr wire:key="card-{{ $card->id }}" class="{{ in_array($card->id, $selectedCards) ? 'bg-yellow-400/[0.06]' : '' }}">
                        <td>
                            <input type="checkbox" value="{{ $card->id }}" wire:model.live="selectedCards" class="dh-check">
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($card->image_url)
                                    <img src="{{ $card->image_url }}" alt="" loading="lazy" class="h-12 w-9 shrink-0 rounded-md bg-black/20 object-contain">
                                @endif
                                <span class="font-medium text-white">{{ $card->search_term }}</span>
                            </div>
                        </td>
                        <td class="text-right tnum">{{ $card->cr_price }}</td>
                        <td class="text-xs dh-muted">{{ $card->cardGroups->pluck('name')->join(', ') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-10 text-center dh-muted">No cards match your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-8">
        {{ $cards->links() }}
    </div>
</div>
