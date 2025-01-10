<div class="flex ml-auto mr-3">
    <button class="bg-green-700 hover:bg-green-800 text-white font-bold px-4 rounded ml-2" wire:click="$set('showModal', true)">Make Me Money</button>

    @if($showModal)
    <div class="modal absolute top-0 left-0 w-full h-full flex items-center justify-center bg-black">
        <div>
            <button class="bg-red-700 hover:bg-red-800 text-white font-bold px-4 rounded ml-2 absolute top-[50px] right-[50px]" wire:click="$set('showModal', false)">close</button>
        </div>
        <div class="card-wrapper rounded-lg bg-white w-1/3 min-h-[50%]">
            <div class="border-b border-gray-200 bg-white px-4 py-5 sm:px-6 w-full">
                <div class="-ml-4 -mt-2 flex flex-wrap items-center justify-between sm:flex-nowrap">
                    <div class="ml-4 mt-2">
                        <h3 class="text-base font-semibold text-gray-900">Create List</h3>
                    </div>
                    <div class="ml-4 mt-2 shrink-0">
                        <button wire:click="generate" type="button" class="relative inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Generate</button>
                    </div>
                </div>
            </div>

            <div class="p-4 border-b border-gray-200">
                <div>
                    <label for="total" class="block text-sm/6 font-medium text-gray-900">Total cards</label>
                    <div class="mt-2">
                        <input wire:model.live="total" type="text" name="total" id="total" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                    </div>
                </div>
            </div>
    
            @if(count($selectedGroups) > 0 && !$generating)
            <div class="p-4 border-b border-gray-200">
                @foreach($selectedGroups as $key => $selected)
                <div class="mt-3 flex w-full">
                    <select wire:change="changeGroup({{ $key }}, $event.target.value)" class="w-1/2 mr-3">
                        @foreach($groups as $group)
                            <option 
                                {{ $selected['id'] == $group->id ? 'selected' : ''}}
                                value="{{ $group->id }}">{{ $group->name }}
                            </option>
                        @endforeach
                    </select>

                    <input wire:model="selectedGroups.{{ $key }}.amount" type="text" placeholder="Card amount" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                </div>
                @endforeach
            </div>
            @endif
            
            @if($generating == false)
            <div class="p-4 text-right" wire:transition>
                <button wire:click="addGroup" type="button" class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                </button>
            </div>
            @endif
            

            @if($generating === true)
            <div class="text-center mt-5">
                <p>{{ $currentText }}</p>

                <div class="w-3/4 mx-auto mt-4 mb-2 bg-gray-200 rounded-full h-3.5 dark:bg-gray-700">
                    <div class="bg-indigo-600 h-3.5 rounded-full" style="width: {{ $progressPercentage }}%"></div>
                </div>

                <p class="text-sm text-gray-500">{{ $progress }}/{{$total}} Cards Added</p>

                @if($completed)
                    <p><b>Buylist complete!</b></p>
                    <a class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">View</a>
                @endif
            </div>

                
            @endif
        </div>  
    </div>
    @endif
</div>

