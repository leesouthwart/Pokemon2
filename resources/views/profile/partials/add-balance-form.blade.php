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
            class="dh-btn-primary"
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
    <div id="add-balance-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto w-full max-w-md rounded-3xl border border-white/10 bg-ink-800 p-6 shadow-2xl shadow-black/60">
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
                            class="dh-select mt-1"
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
                            class="dh-textarea mt-1"
                            placeholder="{{ __('Add any notes about this transaction...') }}"
                        >{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <x-primary-button>{{ __('Add Balance') }}</x-primary-button>
                        <button
                            type="button"
                            onclick="document.getElementById('add-balance-modal').classList.add('hidden')"
                            class="dh-btn-ghost"
                        >
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

