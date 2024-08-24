<x-app-layout>

    <div class="py-12">
        @if(Session::has('message'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{Session::get('message')}}
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="container mx-auto">

        <div class="overflow-hidden rounded-lg bg-gray-700 shadow sm:grid sm:grid-cols-2 sm:gap-px sm:divide-y-0">
            <div class="group relative rounded-tl-lg rounded-tr-lg bg-gray-700 p-6 sm:rounded-tr-none">
                <div>
                  <span class="inline-flex rounded-lg bg-teal-50 p-3 text-teal-700 ring-4 ring-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </span>
                </div>
                <div class="mt-8">
                    <h3 class="text-base font-semibold leading-6 text-white">
                        <a href="{{ route('cardrush') }}" class="focus:outline-none">
                            <!-- Extend touch target to entire panel -->
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            Cardrush Data
                        </a>
                    </h3>
                    <p class="mt-2 text-sm text-gray-400">View the card ROI database.</p>
                </div>
                <span class="pointer-events-none absolute right-6 top-6 text-gray-400 group-hover:text-gray-300" aria-hidden="true">
                  <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z" />
                  </svg>
                </span>
            </div>
            <div class="group relative bg-gray-700 p-6 sm:rounded-tr-none border-l-gray-600 border-l-2">
                <div>
          <span class="inline-flex rounded-lg bg-purple-50 p-3 text-purple-700 ring-4 ring-white">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
            </svg>
          </span>
                </div>
                <div class="mt-8">
                    <h3 class="text-base font-semibold leading-6 text-white">
                        <a href="{{ route('batch.create') }}" class="focus:outline-none">
                            <!-- Extend touch target to entire panel -->
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            PSA Listing Creation
                        </a>
                    </h3>
                    <p class="mt-2 text-sm text-gray-400">Create PSA listings from a list of PSA cert numbers.</p>
                </div>
                <span class="pointer-events-none absolute right-6 top-6 text-gray-400 group-hover:text-gray-300" aria-hidden="true">
          <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z" />
          </svg>
        </span>
            </div>
    </div>
</x-app-layout>
