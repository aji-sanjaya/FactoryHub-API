@php
    $arReceiptConfig = config('idempiere.ar-receipt');
    $defaultStatuses = $arReceiptConfig['statuses']['default_list'] ?? ['DR', 'CO'];
    $filterStatusOptions = $arReceiptConfig['statuses']['filter_options'] ?? [];
@endphp

<div x-data="{
    openFilter: false,
    filters: { date_start: '', date_end: '', status: @js($defaultStatuses) },
    applyFilters() {
        this.openFilter = false;
        document.dispatchEvent(new CustomEvent('filter-applied', { detail: this.filters }));
    },
    resetFilters() {
        this.filters = { date_start: '', date_end: '', status: @js($defaultStatuses) };
        this.applyFilters();
    }
}"
    class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-t-2xl border-b border-gray-200 dark:bg-gray-900 dark:border-gray-800 relative z-20">
    <div class="flex items-center gap-3">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                <svg class="fill-current" width="16" height="16" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M3.04199 9.25C3.04199 5.82165 5.82165 3.04199 9.25 3.04199C12.6784 3.04199 15.458 5.82165 15.458 9.25C15.458 12.6784 12.6784 15.458 9.25 15.458C5.82165 15.458 3.04199 12.6784 3.04199 9.25ZM9.25 1.54199C4.99339 1.54199 1.54199 4.99339 1.54199 9.25C1.54199 13.5066 4.99339 16.958 9.25 16.958C11.1259 16.958 12.8443 16.2891 14.1953 15.1763L16.9427 17.9237C17.2356 18.2166 17.7105 18.2166 18.0034 17.9237C18.2963 17.6308 18.2963 17.1559 18.0034 16.863L15.256 14.1156C16.3688 12.7646 17.042 11.0462 17.042 9.25C17.042 4.99339 13.5066 1.54199 9.25 1.54199Z"
                        fill="" />
                </svg>
            </span>
            <input type="text" placeholder="Search Payment" id="searchInput"
                class="w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white dark:focus:border-brand-500 xl:w-80" />
        </div>
    </div>

    <div class="flex items-center gap-3 relative">
        <button @click="openFilter = !openFilter"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
            </svg>
            Filter
        </button>

        <div x-show="openFilter" @click.outside="openFilter = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 top-full mt-2 w-80 rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-800 dark:bg-gray-900 z-50">

            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Filter Options</h3>
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Date</label>
                    <div class="flex gap-2">
                        <input type="date" x-model="filters.date_start"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                        <span class="self-center text-gray-500">-</span>
                        <input type="date" x-model="filters.date_end"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <div class="flex flex-col gap-2">
                        @foreach($filterStatusOptions as $option)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" value="{{ $option['value'] }}" x-model="filters.status" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                {{ $option['label'] }} ({{ $option['value'] }})
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button @click="resetFilters()"
                    class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    Reset
                </button>
                <button @click="applyFilters()"
                    class="flex-1 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    Apply Filters
                </button>
            </div>
        </div>

        <a href="{{ route('ar-receipt.index', ['document_id' => 'new']) }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-600 focus:outline-none">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            New Payment
        </a>
    </div>
</div>