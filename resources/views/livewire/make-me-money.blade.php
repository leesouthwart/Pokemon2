<div>
    <button class="dh-btn-primary px-5 py-2.5" wire:click="$set('showModal', true)">
        <i class="fas fa-bolt"></i> Make me money
    </button>

    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
        <div class="relative w-full max-w-lg overflow-hidden rounded-3xl border border-white/10 bg-ink-800 shadow-2xl shadow-black/60">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(250,204,21,0.12),transparent_55%)]"></div>

            <div class="relative flex items-start justify-between gap-4 border-b border-white/10 px-6 py-5">
                <div>
                    <div class="dh-eyebrow">{{ $mode === 'raw' ? 'Raw buylist' : 'PSA 10 buylist' }}</div>
                    <h3 class="mt-1 text-2xl font-semibold text-white">Make me money</h3>
                </div>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 transition hover:bg-white/5 hover:text-white" wire:click="$set('showModal', false)">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="relative space-y-5 px-6 py-5">
                @if($generating === true)
                    <div class="py-4 text-center">
                        <p class="text-white">{{ $currentText ?: 'Starting…' }}</p>

                        <div class="mx-auto mt-5 h-3 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="h-3 rounded-full bg-gradient-to-r from-yellow-400 to-lime-400 transition-all" style="width: {{ $progressPercentage }}%"></div>
                        </div>

                        <p class="mt-3 text-sm dh-muted tnum">{{ $progress }}/{{ $total }} cards added</p>

                        @if($completed)
                            <p class="mt-5 font-semibold text-emerald-300"><i class="fas fa-circle-check mr-1"></i> Buylist complete!</p>
                            @if($buylist)
                                <a href="{{ route('buylist.view', ['buylist' => $buylist->id]) }}" class="dh-btn-primary mt-4">View buylist <i class="fas fa-arrow-right"></i></a>
                            @endif
                        @endif
                    </div>
                @else
                    <div>
                        <label for="total" class="dh-label">Total cards</label>
                        <input wire:model.live="total" type="text" name="total" id="total" class="dh-input">
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="dh-label mb-0">Groups</span>
                            <button wire:click="addGroup" type="button" class="dh-btn-ghost dh-btn-sm"><i class="fas fa-plus"></i> Add group</button>
                        </div>

                        @if(count($selectedGroups) > 0)
                            <div class="space-y-2">
                                @foreach($selectedGroups as $key => $selected)
                                    <div class="flex gap-2">
                                        <select wire:change="changeGroup({{ $key }}, $event.target.value)" class="dh-select flex-[2]">
                                            @foreach($groups as $group)
                                                <option {{ $selected['id'] == $group->id ? 'selected' : '' }} value="{{ $group->id }}">{{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                        <input wire:model="selectedGroups.{{ $key }}.amount" type="text" placeholder="Cards" class="dh-input flex-1">
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="rounded-2xl border border-dashed border-white/10 px-4 py-6 text-center text-sm dh-muted">Add at least one group to pull cards from.</p>
                        @endif
                    </div>
                @endif
            </div>

            @unless($generating)
                <div class="relative flex justify-end gap-2 border-t border-white/10 px-6 py-4">
                    <button type="button" class="dh-btn-ghost" wire:click="$set('showModal', false)">Cancel</button>
                    <button wire:click="generate" type="button" class="dh-btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Generate</button>
                </div>
            @endunless
        </div>
    </div>
    @endif
</div>
