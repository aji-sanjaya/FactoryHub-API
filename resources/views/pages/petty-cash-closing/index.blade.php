@extends('layouts.app')

@section('content')
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">

        <!-- Page Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Petty Cash Closing</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage petty cash closings</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:gap-6">
            <!-- Card: All Document -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
                    {{ number_format($countAll ?? 0) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                    ALL DOCUMENT
                </p>
            </div>

            <!-- Card: Draft -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
                    {{ number_format($countDraft ?? 0) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                    DRAFT
                </p>
            </div>

            <!-- Card: In Progress -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
                    {{ number_format($countInProgress ?? 0) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                    IN PROGRESS
                </p>
            </div>

            <!-- Card: Completed -->
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
                    {{ number_format($countCompleted ?? 0) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                    COMPLETED
                </p>
            </div>
        </div>

        <!-- Action / Filter Bar -->
        <div x-data="{ 
                openFilter: false,
                filters: {
                    description: '',
                    date_start: '',
                    date_end: '',
                    status: ''
                },
                applyFilters() {
                    this.openFilter = false;
                    document.dispatchEvent(new CustomEvent('filter-applied', { detail: this.filters }));
                },
                resetFilters() {
                    this.filters = {
                        description: '',
                        date_start: '',
                        date_end: '',
                        status: ''
                    };
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
                    <input type="text" placeholder="Search Document" id="searchInput"
                        class="w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-4 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white dark:focus:border-brand-500 xl:w-80" />
                </div>
            </div>

            <div class="flex items-center gap-3 relative">
                <!-- Filter Button -->
                <button @click="openFilter = !openFilter"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    Filter
                </button>

                <!-- Create New Button -->
                <a href="{{ route('petty-cash-closing.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Create New
                </a>

                <!-- Filter Dropdown -->
                <div x-show="openFilter" @click.outside="openFilter = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 top-full mt-2 w-80 rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-800 dark:bg-gray-900 z-50"
                    style="display: none;">

                    <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Filter Options</h3>

                    <div class="space-y-4">
                        <!-- Description -->
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <input type="text" x-model="filters.description"
                                class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white"
                                placeholder="Contains text..." />
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date
                                Range</label>
                            <div class="flex gap-2">
                                <input type="date" x-model="filters.date_start"
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                                <span class="self-center text-gray-500">-</span>
                                <input type="date" x-model="filters.date_end"
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select x-model="filters.status"
                                class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
                                <option value="">All Statuses</option>
                                <option value="DR">Draft</option>
                                <option value="IP">In Progress</option>
                                <option value="CO">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button @click="resetFilters()"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Reset
                        </button>
                        <button @click="applyFilters()"
                            class="flex-1 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 focus:outline-none">
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div id="closing-table-wrapper">
            <div
                class="overflow-hidden rounded-b-2xl border border-t-0 border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="max-w-full overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                            <tr class="bg-gray-50 text-left dark:bg-white/[0.03]">
                                <th
                                    class="min-w-[50px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                    NO
                                </th>
                                <th
                                    class="min-w-[80px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                    VIEW
                                </th>
                                <th
                                    class="min-w-[150px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                    DOC NUMBER
                                </th>
                                <th
                                    class="min-w-[120px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                    DATE
                                </th>
                                <th
                                    class="min-w-[150px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                    USER
                                </th>
                                <th
                                    class="min-w-[200px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                    DESCRIPTION
                                </th>
                                <th
                                    class="min-w-[120px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">
                                    TOTAL AMOUNT
                                </th>
                                <th
                                    class="min-w-[120px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">
                                    STATUS
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($closings ?? [] as $index => $item)
                                                    <tr class="border-t border-gray-200 dark:border-gray-800">
                                                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                                                            {{ $index + 1 + (($closings->currentPage() - 1) * $closings->perPage()) }}
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center space-x-2">
                                                                <a href="{{ route('petty-cash-closing.index', ['document_id' => \Illuminate\Support\Facades\Crypt::encryptString($item->adw_pettycash_closing_id)]) }}"
                                                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500">
                                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                    </svg>
                                                                </a>
                                                                @if($item->docstatus !== 'VO')
                                                                    <button type="button"
                                                                        onclick="Swal.fire({icon: 'info', title: 'Info', text: 'Print functionality coming soon', confirmButtonColor: '#4f46e5'}); return false;"
                                                                        class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500"
                                                                        title="Print">
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                                                            </path>
                                                                        </svg>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-800 text-theme-sm dark:text-white/90 font-medium">
                                                            {{ $item->documentno }}
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                                                            {{ date('d M Y', strtotime($item->datetrx)) }}
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                                                            {{ $item->user_name ?? '-' }}
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                                                            <div class="truncate max-w-xs whitespace-nowrap" title="{{ $item->description }}">
                                                                {{ $item->description ?: '-' }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-800 text-theme-sm dark:text-white/90 text-right font-medium">
                                                            {{ number_format($item->totallines ?? 0, 2) }}
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            @php
                                                                $statusLabel = $item->status_label ?? 'Draft';
                                                                $statusClass = match ($statusLabel) {
                                                                    'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                                                    'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                                                                    default => 'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-400'
                                                                };
                                                            @endphp
                                 <span
                                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                                                {{ $statusLabel }}
                                                            </span>
                                                        </td>
                                                    </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <!-- Empty State -->
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <p class="text-base font-medium">No petty cash closings found</p>
                                            <p class="text-sm text-gray-400 mt-1">Create your first request to get started</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination & Info -->
                @if(isset($closings) && $closings->total() > 0)
                    <div
                        class="px-4 py-4 border-t border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing <span
                                class="font-medium text-gray-900 dark:text-white">{{ $closings->firstItem() ?? 0 }}</span>
                            to <span class="font-medium text-gray-900 dark:text-white">{{ $closings->lastItem() ?? 0 }}</span>
                            of <span class="font-medium text-gray-900 dark:text-white">{{ $closings->total() }}</span> results
                        </div>
                        <div class="w-full sm:w-auto">
                            {{ $closings->onEachSide(1)->links('pagination::tailwind') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const tableWrapper = document.getElementById('closing-table-wrapper');

                let debounceTimer;
                let currentFilters = {};

                // Function to fetch data
                const fetchData = (url = '{{ route("petty-cash-closing.index") }}') => {
                    const query = searchInput.value;
                    const wrapper = document.getElementById('closing-table-wrapper');

                    // Add loading state
                    wrapper.classList.add('opacity-50', 'pointer-events-none');

                    const isPagination = url.includes('page=');
                    const config = isPagination ? {} : {
                        params: {
                            search: query,
                            ...currentFilters
                        }
                    };

                    axios.get(url, config)
                        .then(response => {
                            wrapper.innerHTML = response.data.html;
                        })
                        .catch(error => {
                            console.error('Error fetching data:', error);
                        })
                        .finally(() => {
                            wrapper.classList.remove('opacity-50', 'pointer-events-none');
                        });
                };

                // Search Input Listener
                searchInput.addEventListener('input', function (e) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => fetchData(), 300);
                });

                // Listen for Filter Event
                document.addEventListener('filter-applied', function (e) {
                    console.log('Filter applied:', e.detail);
                    currentFilters = e.detail;
                    fetchData();
                });

                // Pagination Click Interceptor
                tableWrapper.addEventListener('click', function (e) {
                    const link = e.target.closest('a');
                    if (link && tableWrapper.contains(link)) {
                        const href = link.getAttribute('href');
                        if (href && href.includes('page=')) {
                            e.preventDefault();
                            fetchData(href);
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection