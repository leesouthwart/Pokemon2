<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold mb-2">Edit Card PSA Title</h2>
                        <p class="text-gray-600 dark:text-gray-400">Card ID: {{ $card->id }} | Search Term: {{ $card->search_term }}</p>
                    </div>

                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('cards.psa-title.update', $card) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="psa_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                PSA Title
                            </label>
                            <input
                                type="text"
                                id="psa_title"
                                name="psa_title"
                                value="{{ old('psa_title', $card->psa_title) }}"
                                placeholder="Enter PSA title to match eBay listings..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                This title will be used to match eBay listing titles to this card.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="excluded_from_sniping"
                                    value="1"
                                    {{ old('excluded_from_sniping', $card->excluded_from_sniping) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Exclude from sniping
                                </span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Excluded cards will be filtered out from sniping.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Update Card
                            </button>
                            <a
                                href="{{ route('cards.psa-title.index') }}"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

