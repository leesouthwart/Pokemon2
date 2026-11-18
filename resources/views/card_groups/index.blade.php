<x-app-layout>
    <div class="dh-page">
        <x-page-header eyebrow="Groups" title="Card groups">
            <a href="{{ route('card_group.bulk-add') }}" class="dh-btn-ghost"><i class="fas fa-folder-plus"></i> Bulk add cards</a>
        </x-page-header>

        <livewire:card-group-form />
        <livewire:card-group-list />
    </div>
</x-app-layout>
