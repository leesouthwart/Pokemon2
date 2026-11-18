<div class="mt-8">
    @if($card_groups->isEmpty())
        <div class="dh-panel py-16 text-center">
            <i class="fas fa-folder-open text-3xl text-violet-200/30"></i>
            <p class="mt-3 dh-muted">No groups yet. Create one above.</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($card_groups as $card_group)
                <a href="{{ route('card_group.single', $card_group->id) }}" class="dh-tile group block">
                    <div class="relative grid aspect-[4/3] place-items-center overflow-hidden rounded-xl bg-black/20">
                        @forelse($card_group->preview_images as $index => $image)
                            <img src="{{ $image }}" alt="" loading="lazy"
                                 class="absolute h-[85%] rounded-md object-contain shadow-lg shadow-black/50"
                                 style="transform: translateX({{ ($index - (count($card_group->preview_images) - 1) / 2) * 34 }}%) rotate({{ ($index - (count($card_group->preview_images) - 1) / 2) * 8 }}deg); z-index: {{ 10 - abs($index - 1) }};">
                        @empty
                            <i class="fas fa-folder text-3xl text-violet-200/20"></i>
                        @endforelse
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-2 px-1">
                        <span class="truncate font-medium text-white">{{ $card_group->name }}</span>
                        <span class="dh-badge dh-badge-yellow">{{ $card_group->cards_count }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
