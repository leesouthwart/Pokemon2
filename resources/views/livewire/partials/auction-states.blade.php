@if($error)
    <div class="dh-alert-error mt-6">{{ $error }}</div>
@endif

@if(!$hasListings)
    <div class="dh-panel mt-6 py-16 text-center">
        @if($loading)
            <i class="fas fa-circle-notch fa-spin text-2xl text-yellow-400"></i>
            <p class="mt-3 dh-muted">Loading listings…</p>
        @else
            <i class="fas fa-binoculars text-3xl text-violet-200/30"></i>
            <p class="mt-3 dh-muted">No listings found.</p>
        @endif
    </div>
@endif
