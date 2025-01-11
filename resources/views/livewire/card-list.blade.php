<div class="bg-gray-900 py-10">

    <div class="flex w-full">    
        <div class="px-4 w-1/4">
                <input type="text" name="search_term" id="search" wire:model.live="search" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Search...">
        </div>

        
            <div class="flex ml-auto">
                
                <livewire:make-me-money :groups="$groups"/>
                

                @if(!empty($selectedCards))
                <select class="rounded text-sm py-1.5 border-0" wire:model="selectedCardGroupId">
                    <option disabled value="0">Select Group</option>
                @foreach($groups as $cardGroup)
                    <option value="{{ $cardGroup->id }}">{{ $cardGroup->name }}</option>
                @endforeach
                </select>

                <button class="bg-indigo-700 hover:bg-indigo-800 text-white font-bold px-4 rounded ml-2" wire:click="addToGroup">Add to group</button>
                <button class="bg-red-700 hover:bg-red-800 text-white font-bold px-4 rounded ml-16 mr-3" wire:click="delete">Delete</button>
                @endif
            </div>


            
    </div>

    <table class="mt-6 w-full whitespace-nowrap text-left">
        <colgroup>
            <col class="w-full sm:w-1/12">
            <col class="w-full sm:w-1/12">
            <col class="lg:w-4/12">
            <col class="lg:w-4/12">
            <col class="lg:w-1/12">
            <col class="lg:w-1/12">
            <col class="lg:w-1/12">
            <col class="lg:w-1/12">
            <col class="lg:w-1/12">
        </colgroup>
        <thead class="border-b border-white/10 text-sm leading-6 text-white">
        <tr>
            <th scope="col" class="py-2 px-2 font-semibold"></th>
            <th scope="col" class="py-2 px-2 font-semibold"></th>
            <th scope="col" class="py-2 pl-4 pr-8 font-semibold sm:pl-6 lg:pl-8 cursor-pointer" wire:click="sortBy('search_term')">Card Name</th>
            <th scope="col" class="hidden py-2 pl-0 pr-8 font-semibold sm:table-cell cursor-pointer" wire:click="sortBy('cr_price')">Raw Price</th>
            <th scope="col" class="py-2 pl-0 pr-4 text-right font-semibold sm:pr-8 sm:text-left lg:pr-20 cursor-pointer" wire:click="sortBy('region_cards.psa_10_price')">PSA 10 (lowest)</th>
            <th scope="col" class="hidden py-2 pl-0 pr-8 font-semibold md:table-cell lg:pr-20 cursor-pointer" wire:click="sortBy('region_cards.average_psa_10_price')">PSA 10 (average)</th>
            <th scope="col" class="hidden py-2 pl-0 pr-4 text-left font-semibold sm:table-cell sm:pr-6 lg:pr-8 cursor-pointer" wire:click="sortBy('cards.roi_average')">ROI</th>
            <th scope="col" class="hidden py-2 pl-0 pr-4 text-right font-semibold sm:table-cell sm:pr-6 lg:pr-8">Actions</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-white/5">

        @foreach($cardList as $card)
            <livewire:card :card="$card" :key="$card->id"  :wire:key="$card->id"/>
        @endforeach

        </tbody>
    </table>

    <div class="mt-6 px-8">
        {{ $cardList->links() }}
    </div>

</div>
