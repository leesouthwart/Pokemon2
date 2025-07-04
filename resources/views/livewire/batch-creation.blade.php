<div class="flex">
    <div class="bg-gray-700 w-4/5">
        <div class="w-full bg-gray-500 flex items-center px-3 py-2 mb-3">
            @if(!$useList)
            <div class="form-container w-1/3">
                <input wire:model.live="start" id="searchTerm" type="text" class="w-full bg-gray-600 text-gray-300" placeholder="Starting Cert Number">
            </div>

            <div class="card_url w-1/3 mx-3">
                <input type="text" class="w-full bg-gray-600 text-gray-300" wire:model.live="end" placeholder="Ending Cert Number">
            </div>
            @else
            <div class="card_url w-2/3 mx-3">
                <input type="text" class="w-full bg-gray-600 text-gray-300" wire:model.live="list" placeholder="Comma Seperated list">
            </div>
            @endif

            <div class="button_container">
                <button type="button" wire:click="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Start</button>
            </div>

            <div class="button_container">
                <button type="button" wire:click="$toggle('useList')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded ml-5">List format</button>
            </div>

            <div class="button_container ml-5">
                <a href="{{ route('batch.index') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">View Previous Batches</a>
            </div>
        </div>

        @if($batch)
            <div>
                @if($listings)
                    @foreach($listings as $listing)
                        <livewire:batch-listing :listing="$listing" wire:key="{{$this->parentKey}}-{{$listing->id}}" />
                    @endforeach
                @endif

                @if($loading)
                    <div role="status" class="h-100 flex justify-center align-start mt-5">
                        <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                        </svg>
                        <span class="sr-only">Loading...</span>
                    </div>
                @endif

                @if($psa_api_expired)
                    <div class="bg-red-500 text-white p-4 rounded-md mx-4">
                        <p>PSA API limit has been reached. Please try again later.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>


    <div class="w-1/5 bg-gray-800">
        <div class="h-full">
            <div class="bg-gray-800 flex justify-center align-center py-3 border-b border-gray-700 text-gray-300">
                <p>Batch {{$batch->id ?? ''}}</p>
            </div>

            @if($loading)
                <div role="status" class="h-100 flex justify-center align-start mt-5">
                    <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                    </svg>
                    <span class="sr-only">Loading...</span>
                </div>
            @endif

            <div class="bg-gray-800 text-gray-300 flex flex-col items-center justify-center my-8">
                <button wire:click="export" class="btn bg-blue-600 text-white px-32 py-4 mx-auto">Export</button>
            </div>

            <div class="bg-gray-800 text-gray-300 flex flex-col items-center justify-center my-4">
                <a href="{{ route('batch.create') }}" class="btn bg-orange-600 hover:bg-orange-700 text-white px-32 py-4 mx-auto">Start Fresh</a>
            </div>

            @if($calculatedTotal)
                <div class="bg-gray-800 text-gray-300 flex flex-col items-center justify-center my-8">
                    <p>Total Value: £{{ number_format($calculatedTotal, 2) }}</p>
                </div>
            @endif
        </div>

    </div>
</div>
