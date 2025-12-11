<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Add Balance') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Add funds to your account balance and track profit/loss.') }}
        </p>
    </header>

    <div class="mt-6">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('Current balance: $') }}{{ number_format($user->balance ?? 0, 2) }}
        </p>
        
        <button
            type="button"
            onclick="document.getElementById('add-balance-modal').classList.remove('hidden')"
            class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
        >
            {{ __('Add Balance') }}
        </button>

        @if (session('status') === 'balance-added')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 3000)"
                class="mt-4 text-sm text-green-600 dark:text-green-400 font-medium"
            >
                {{ __('Added $') }}{{ number_format(session('balance_added'), 2) }} 
                ({{ session('fund_type') === 'payout' ? 'Payout' : 'Addition' }}). 
                {{ __('New balance: $') }}{{ number_format(session('new_balance'), 2) }}
            </p>
        @endif
    </div>

    <!-- Add Balance Modal -->
    <div id="add-balance-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Add Balance') }}
                    </h3>
                    <button
                        type="button"
                        onclick="document.getElementById('add-balance-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form method="post" action="{{ route('profile.add-balance') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="modal_amount" :value="__('Amount to Add')" />
                        <x-text-input 
                            id="modal_amount" 
                            name="amount" 
                            type="number" 
                            class="mt-1 block w-full" 
                            :value="old('amount', 0)" 
                            min="0" 
                            step="0.01"
                            required 
                            autofocus 
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <select
                            id="type"
                            name="type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            required
                        >
                            <option value="">{{ __('Select type...') }}</option>
                            <option value="payout" {{ old('type') === 'payout' ? 'selected' : '' }}>
                                {{ __('Payout') }} - Money from sold cards
                            </option>
                            <option value="addition" {{ old('type') === 'addition' ? 'selected' : '' }}>
                                {{ __('Addition') }} - Extra funds from other sources
                            </option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Payouts are used in profit calculation, additions are not.') }}
                        </p>
                    </div>

                    <div>
                        <x-input-label for="notes" :value="__('Notes (Optional)')" />
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            placeholder="{{ __('Add any notes about this transaction...') }}"
                        >{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <x-primary-button>{{ __('Add Balance') }}</x-primary-button>
                        <button
                            type="button"
                            onclick="document.getElementById('add-balance-modal').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-600"
                        >
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

