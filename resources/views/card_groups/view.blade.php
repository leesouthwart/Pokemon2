<x-app-layout class="flex">
    <div class="flex">
        <div class="bg-gray-900 w-full">
            <div>
                <livewire:card-list :cardGroup="$cardGroup" />
            </div>
        </div>

        <livewire:sidebar />

    </div>
</x-app-layout>
