<div class="flex w-1/2 py-5">
    <div class="mt-2 flex rounded-md shadow-sm mx-3">
        <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 px-3 text-gray-500 sm:text-sm">Name</span>
        <input wire:model.live="name" id="name" type="text" class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Enter Name...">
    </div>

    <div class="button_container mt-2">
        <button type="button" wire:click="submit" class="bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded">Add Group</button>
    </div>

</div>