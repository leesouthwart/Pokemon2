<x-app-layout>
    <div class="dh-page">
        <x-page-header eyebrow="Account" title="Profile" />

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <div class="dh-panel p-6 sm:p-8">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="dh-panel p-6 sm:p-8">
                @include('profile.partials.ebay-settings-form')
            </div>

            @if(auth()->user()->email === 'leesouthwart@gmail.com')
                <div class="dh-panel p-6 sm:p-8">
                    @include('profile.partials.add-balance-form')
                </div>
            @endif

            <div class="dh-panel p-6 sm:p-8">
                @include('profile.partials.update-password-form')
            </div>

            <div class="rounded-2xl border border-rose-500/20 bg-rose-500/[0.04] p-6 sm:p-8">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
