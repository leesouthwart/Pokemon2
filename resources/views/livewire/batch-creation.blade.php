<div class="dh-page">
    <x-page-header eyebrow="Selling" :title="$batch ? 'Batch #' . $batch->id : 'PSA listing upload'">
        <a href="{{ route('batch.index') }}" class="dh-btn-ghost"><i class="fas fa-boxes-stacked"></i> Previous batches</a>
        @if($batch)
            <a href="{{ route('batch.create') }}" class="dh-btn-ghost"><i class="fas fa-rotate-left"></i> Start fresh</a>
        @endif
    </x-page-header>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="min-w-0">
            <div class="dh-panel flex flex-wrap items-end gap-3 p-4">
                <div class="dh-segment">
                    <button type="button" wire:click="$set('useList', false)" class="{{ !$useList ? 'is-active' : '' }}">Cert range</button>
                    <button type="button" wire:click="$set('useList', true)" class="{{ $useList ? 'is-active' : '' }}">List</button>
                </div>

                @if(!$useList)
                    <div class="min-w-[180px] flex-1">
                        <label class="dh-label" for="certStart">Starting cert</label>
                        <input wire:model.live="start" id="certStart" type="text" class="dh-input tnum" placeholder="e.g. 81234500">
                    </div>
                    <div class="min-w-[180px] flex-1">
                        <label class="dh-label" for="certEnd">Ending cert</label>
                        <input type="text" id="certEnd" class="dh-input tnum" wire:model.live="end" placeholder="e.g. 81234520">
                    </div>
                @else
                    <div class="min-w-[260px] flex-[2]">
                        <label class="dh-label" for="certList">Cert numbers</label>
                        <input type="text" id="certList" class="dh-input tnum" wire:model.live="list" placeholder="Comma separated list">
                    </div>
                @endif

                <button type="button" wire:click="submit" class="dh-btn-primary"><i class="fas fa-play"></i> Start</button>
            </div>

            @if($batch)
                <div class="dh-panel mt-6 overflow-hidden">
                    <div class="grid grid-cols-[1fr_90px_140px] gap-3 border-b border-white/10 px-4 py-3 text-xs font-medium uppercase tracking-wider dh-muted">
                        <span>Title</span>
                        <span>Qty</span>
                        <span>Price</span>
                    </div>

                    @if($listings)
                        <div class="divide-y divide-white/5">
                            @foreach($listings as $listing)
                                <livewire:batch-listing :listing="$listing" wire:key="{{$this->parentKey}}-{{$listing->id}}" />
                            @endforeach
                        </div>
                    @endif

                    @if($loading)
                        <div class="py-8 text-center">
                            <i class="fas fa-circle-notch fa-spin text-2xl text-yellow-400"></i>
                            <p class="mt-2 text-sm dh-muted">Fetching PSA cert data…</p>
                        </div>
                    @elseif(!$listings || $listings->isEmpty())
                        <p class="py-8 text-center text-sm dh-muted">No listings in this batch yet.</p>
                    @endif
                </div>

                @if($psa_api_expired)
                    <div class="dh-alert-error mt-4">PSA API limit has been reached. Please try again later.</div>
                @endif
            @endif
        </div>

        <aside class="dh-panel h-fit p-5 lg:sticky lg:top-24">
            <div class="text-sm dh-muted">Batch</div>
            <div class="text-2xl font-semibold text-white">{{ $batch ? '#' . $batch->id : '—' }}</div>

            <div class="mt-5 grid grid-cols-2 gap-2 text-xs">
                <div class="dh-stat">
                    <div class="dh-stat-label">Listings</div>
                    <div class="dh-stat-value">{{ $listings ? $listings->count() : 0 }}</div>
                </div>
                <div class="dh-stat">
                    <div class="dh-stat-label">Total value</div>
                    <div class="dh-stat-value">{{ $calculatedTotal ? '£' . number_format($calculatedTotal, 2) : '—' }}</div>
                </div>
            </div>

            <div class="mt-5 space-y-2">
                <button wire:click="export" class="dh-btn-primary w-full" @disabled(!$batch)><i class="fas fa-file-excel"></i> Export</button>
                <a href="{{ route('batch.create') }}" class="dh-btn-ghost w-full"><i class="fas fa-rotate-left"></i> Start fresh</a>
            </div>
        </aside>
    </div>
</div>
