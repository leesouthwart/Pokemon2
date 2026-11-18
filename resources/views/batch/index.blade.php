<x-app-layout>
    <div class="dh-page">
        <x-page-header eyebrow="Selling" title="Batches">
            <a href="{{ route('batch.create') }}" class="dh-btn-primary"><i class="fas fa-plus"></i> New batch</a>
        </x-page-header>

        @if($batches->count() > 0)
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($batches as $batch)
                    <a href="{{ route('batch.view', $batch) }}" class="dh-tile group block p-5">
                        <div class="flex items-center justify-between">
                            <span class="dh-badge dh-badge-violet">#{{ $batch->id }}</span>
                            <i class="fas fa-arrow-right text-xs text-violet-200/30 transition group-hover:translate-x-0.5 group-hover:text-yellow-400"></i>
                        </div>
                        <div class="mt-4 truncate text-lg font-semibold text-white">{{ $batch->name ?: 'Batch ' . $batch->id }}</div>
                        <div class="text-xs dh-muted">{{ $batch->created_at->format('j M Y, H:i') }}</div>
                        <div class="mt-4 dh-stat text-xs">
                            <div class="dh-stat-label">Listings</div>
                            <div class="dh-stat-value">{{ $batch->ebayListings->count() }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="dh-panel mt-8 py-16 text-center">
                <i class="fas fa-boxes-stacked text-3xl text-violet-200/30"></i>
                <p class="mt-3 dh-muted">No batches yet.</p>
                <a href="{{ route('batch.create') }}" class="dh-btn-primary mt-5">Create your first batch</a>
            </div>
        @endif
    </div>
</x-app-layout>
