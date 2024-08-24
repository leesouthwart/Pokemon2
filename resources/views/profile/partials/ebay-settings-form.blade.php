<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Ebay Settings') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your shipping cost and ebay fees.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.ebay-settings.update') }}" class="mt-6 space-y-6">
        @csrf
        <div>
            <x-input-label for="shipping_cost" :value="__('Shipping Cost')" />
            <x-text-input id="shipping_cost" name="shipping_cost" type="text" class="mt-1 block w-full" :value="old('shipping_cost', $user->shipping_cost)" required autofocus  />
            <x-input-error class="mt-2" :messages="$errors->get('shipping_cost')" />
        </div>

        <div>
            <x-input-label for="ebay_fee" :value="__('Ebay Fee')" />
            <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('This is the percentage of the ebay price that is taken off the final price. 0.155 = 15.5%') }}
            </span>
            <x-text-input id="ebay_fee" name="ebay_fee" type="text" class="mt-1 block w-full" :value="old('ebay_fee', $user->ebay_fee)" required autofocus  />
            <x-input-error class="mt-2" :messages="$errors->get('ebay_fee')" />
        </div>

        <div>
            <x-input-label for="grading_cost" :value="__('Grading Cost')" />
            <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('PSA Grading cost (in GBP)') }}
            </span>
            <x-text-input id="grading_cost" name="grading_cost" type="text" class="mt-1 block w-full" :value="old('grading_cost', $user->grading_cost)" required autofocus  />
            <x-input-error class="mt-2" :messages="$errors->get('grading_cost')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
