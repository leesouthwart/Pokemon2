<div class="bg-gray-900 min-h-screen text-gray-200 py-8">
    <div class="max-w-5xl mx-auto px-4">
        <h1 class="text-2xl font-semibold mb-2">Bulk Import Cards</h1>
        <p class="text-sm text-gray-400 mb-6">
            One card per line, formatted as <code class="bg-gray-800 px-1 rounded">search term, card link</code>.
            Optionally append a third column for a comma-free group name to override the default group, e.g.
            <code class="bg-gray-800 px-1 rounded">magikarp 080 073, https://example.com/card, My Group</code>.
        </p>

        <div class="mb-4">
            <label for="defaultGroup" class="block text-sm font-medium text-gray-300 mb-1">Default group (optional)</label>
            <select wire:model="defaultGroupId" id="defaultGroup" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                <option value="">-- None --</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="rows" class="block text-sm font-medium text-gray-300 mb-1">Cards</label>
            <textarea
                wire:model.defer="rows"
                id="rows"
                rows="14"
                class="block w-full font-mono text-sm rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                placeholder="magikarp 080 073, https://www.cardrush-pokemon.jp/product/12345&#10;mew 183 172, https://www.cardrush-pokemon.jp/product/67890"></textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                    wire:click="submit"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-2 px-4 rounded">
                <span wire:loading.remove wire:target="submit">Import</span>
                <span wire:loading wire:target="submit">Queueing...</span>
            </button>

            <a href="{{ route('cardrush') }}" class="text-sm text-gray-400 hover:text-gray-200 underline">Back to card list</a>
        </div>

        @if($submitted)
            <div class="mt-6 space-y-3">
                @if($dispatched > 0)
                    <div class="bg-green-700/30 border border-green-600/40 text-green-200 p-4 rounded">
                        Queued <strong>{{ $dispatched }}</strong> card import {{ \Illuminate\Support\Str::plural('job', $dispatched) }}.
                        They will run in the background and appear in the card list once complete.
                    </div>
                @endif

                @if(!empty($errors))
                    <div class="bg-red-700/30 border border-red-600/40 text-red-200 p-4 rounded">
                        <p class="font-semibold mb-2">Skipped {{ count($errors) }} {{ \Illuminate\Support\Str::plural('row', count($errors)) }}:</p>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            @foreach($errors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
