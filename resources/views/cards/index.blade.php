<x-app-layout class="flex">
    <div class="flex">
        <div class="bg-gray-900 w-full">
            <div class="border-b-2 border-gray-700">
                <livewire:card-form />
            </div>

            <div class="border-b border-gray-700 px-4 py-2 flex justify-end">
                <a href="{{ route('cardrush-raw') }}" class="text-sm text-indigo-400 hover:text-indigo-300">View ungraded (raw) list &rarr;</a>
            </div>

            <div>
                <livewire:card-list mode="graded" />
            </div>
        </div>

        <livewire:sidebar mode="graded" />

    </div>
</x-app-layout>
