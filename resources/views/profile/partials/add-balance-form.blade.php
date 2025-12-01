<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Add Balance') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Add funds to your account balance.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.add-balance') }}" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="amount" :value="__('Amount to Add')" />
            <x-text-input 
                id="amount" 
                name="amount" 
                type="number" 
                class="mt-1 block w-full" 
                :value="old('amount', 0)" 
                min="0" 
                step="1"
                required 
                autofocus 
            />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Current balance: $') }}{{ number_format($user->balance ?? 0, 2) }}
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Add Balance') }}</x-primary-button>

            @if (session('status') === 'balance-added')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm text-green-600 dark:text-green-400 font-medium"
                >
                    {{ __('Added $') }}{{ number_format(session('balance_added'), 2) }}. {{ __('New balance: $') }}{{ number_format(session('new_balance'), 2) }}
                </p>
            @endif
        </div>
    </form>
</section>

