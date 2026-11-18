<x-app-layout>
    <div class="dh-page max-w-3xl">
        <x-page-header eyebrow="Edit listing title" :title="$card->search_term">
            <a href="{{ route('cards.psa-title.index') }}" class="dh-btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
        </x-page-header>
        <p class="mt-2 text-sm dh-muted">Card #{{ $card->id }}</p>

        @if($errors->any())
            <div class="dh-alert-error mt-6">
                <ul class="list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('cards.psa-title.update', $card) }}" class="dh-panel mt-6 space-y-5 p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="psa_title" class="dh-label">PSA title</label>
                <input type="text" id="psa_title" name="psa_title" value="{{ old('psa_title', $card->psa_title) }}" placeholder="Enter PSA title to match eBay listings…" class="dh-input">
                <p class="dh-help">Used to match eBay listing titles to this card.</p>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="excluded_from_sniping" value="1" {{ old('excluded_from_sniping', $card->excluded_from_sniping) ? 'checked' : '' }} class="dh-check">
                    Exclude from sniping
                </label>
                <p class="dh-help">Excluded cards are filtered out from sniping.</p>
            </div>

            <div class="flex gap-2 border-t border-white/10 pt-5">
                <button type="submit" class="dh-btn-primary">Update card</button>
                <a href="{{ route('cards.psa-title.index') }}" class="dh-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
