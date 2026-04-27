<div class="bg-gray-900 min-h-screen text-gray-200 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-2xl font-semibold mb-6">Bulk Add Cards to Group</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gray-800 rounded-lg p-5">
                <h2 class="text-lg font-semibold mb-3">Create new group</h2>
                <div class="flex flex-col gap-3">
                    <input
                        type="text"
                        wire:model.defer="newGroupName"
                        placeholder="Group name..."
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">

                    <label class="inline-flex items-center text-sm text-gray-300">
                        <input type="checkbox" wire:model="useInBuylistGeneration" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-2">
                        Use in buylist generation
                    </label>

                    <div>
                        <button type="button"
                                wire:click="createGroup"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded">
                            Create group
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-5">
                <h2 class="text-lg font-semibold mb-3">Target group</h2>
                <select wire:model.live="selectedGroupId" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                    <option value="">-- Select a group --</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->cards()->count() }})</option>
                    @endforeach
                </select>

                @if($selectedGroup)
                    <p class="text-sm text-gray-400 mt-2">
                        Currently {{ $selectedGroup->cards()->count() }}
                        {{ \Illuminate\Support\Str::plural('card', $selectedGroup->cards()->count()) }}
                        in <strong>{{ $selectedGroup->name }}</strong>.
                    </p>
                @endif

                <label class="inline-flex items-center text-sm text-gray-300 mt-3">
                    <input type="checkbox" wire:model.live="hideAlreadyInGroup" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-2">
                    Hide cards already in selected group
                </label>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-5">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search cards..."
                    class="flex-1 min-w-[200px] rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">

                <span class="text-sm text-gray-400">
                    Selected: <strong class="text-white">{{ count($selectedCards) }}</strong>
                </span>

                <button type="button"
                        wire:click="selectAllOnPage({{ json_encode($pageCardIds) }})"
                        class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold py-1.5 px-3 rounded">
                    Select page
                </button>

                <button type="button"
                        wire:click="clearSelection"
                        class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold py-1.5 px-3 rounded">
                    Clear selection
                </button>

                <button type="button"
                        wire:click="addSelectedToGroup"
                        @disabled(!$selectedGroupId || empty($selectedCards))
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-2 px-4 rounded">
                    Add {{ count($selectedCards) }} to group
                </button>
            </div>

            <table class="w-full text-left">
                <thead class="border-b border-white/10 text-sm text-white">
                    <tr>
                        <th class="py-2 px-2 w-10"></th>
                        <th class="py-2 px-2 w-16"></th>
                        <th class="py-2 px-2">Card name</th>
                        <th class="py-2 px-2">Raw price (JPY)</th>
                        <th class="py-2 px-2">Groups</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($cards as $card)
                        <tr wire:key="card-{{ $card->id }}" class="text-sm">
                            <td class="py-2 px-2">
                                <input type="checkbox"
                                       value="{{ $card->id }}"
                                       wire:model.live="selectedCards"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="py-2 px-2">
                                @if($card->image_url)
                                    <img src="{{ $card->image_url }}" alt="" class="h-12 w-auto rounded">
                                @endif
                            </td>
                            <td class="py-2 px-2">{{ $card->search_term }}</td>
                            <td class="py-2 px-2">{{ $card->cr_price }}</td>
                            <td class="py-2 px-2 text-xs text-gray-400">
                                {{ $card->cardGroups->pluck('name')->join(', ') ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-400">No cards match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">
                {{ $cards->links() }}
            </div>
        </div>
    </div>
</div>
