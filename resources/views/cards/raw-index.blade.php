<x-app-layout class="flex">
    <div class="flex">
        <div class="bg-gray-900 w-full">
            <div class="border-b-2 border-gray-700 px-4 py-3 flex items-center justify-between">
                <h1 class="text-white text-lg font-semibold">Cardrush Raw (Ungraded)</h1>
                <a href="{{ route('cardrush') }}" class="text-sm text-indigo-400 hover:text-indigo-300">View PSA 10 list &rarr;</a>
            </div>

            <div>
                <livewire:card-list mode="raw" />
            </div>
        </div>

        <livewire:sidebar mode="raw" />

    </div>
</x-app-layout>
