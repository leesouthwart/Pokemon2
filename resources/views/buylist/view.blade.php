<x-app-layout class="flex">
    <div class="flex">
        <div class="bg-gray-900 w-full">
            
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
                    <th scope="col" class="py-2 pl-4 pr-8 font-semibold sm:pl-6 lg:pl-8 cursor-pointer">Card Name</th>
                    <th scope="col" class="hidden py-2 pl-0 pr-8 font-semibold sm:table-cell cursor-pointer">Raw Price</th>
                    <th scope="col" class="py-2 pl-0 pr-4 text-right font-semibold sm:pr-8 sm:text-left lg:pr-20 cursor-pointer">PSA 10 (lowest)</th>
                    <th scope="col" class="hidden py-2 pl-0 pr-8 font-semibold md:table-cell lg:pr-20 cursor-pointer">PSA 10 (average)</th>
                    <th scope="col" class="hidden py-2 pl-0 pr-4 text-left font-semibold sm:table-cell sm:pr-6 lg:pr-8">ROI</th>
                    <th scope="col" class="hidden py-2 pl-0 pr-4 text-right font-semibold sm:table-cell sm:pr-6 lg:pr-8">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
        
                @foreach($cards as $card)
                    <livewire:card :card="$card" :key="$card->id"  :wire:key="$card->id"/>
                @endforeach
        
                </tbody>
            </table>
        </div>

        <livewire:sidebar />

    </div>
</x-app-layout>
