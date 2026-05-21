@extends('layouts.app')
@push('styles')
    <style>
        /* Select2 UI Fixes */
        .select2-container .select2-selection--single {
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            background-color: transparent !important;
            border: 1px solid #d1d5db !important;
            /* gray-300 */
            border-radius: 0.5rem !important;
            padding: 0 !important;

            /* text-sm */
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
            padding-left: 12px !important;
            padding-right: 30px !important;
            color: #111827 !important;
            /* gray-900 */
            flex-grow: 1;
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            width: 30px !important;
            position: absolute !important;
            top: 1px !important;
            right: 1px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Dropdown Results Font Size */
        .select2-container .select2-dropdown .select2-results__option {
            font-size: 0.875rem !important;
            /* text-sm */
            padding: 6px 12px !important;
        }

        .select2-container .select2-dropdown .select2-search__field {
            font-size: 0.875rem !important;
            /* text-sm */
        }

        /* Dark Mode */
        .dark .select2-container .select2-selection--single {
            border-color: #4b5563 !important;
            /* gray-600 */
        }

        .dark .select2-container .select2-selection--single .select2-selection__rendered {
            color: #ffffff !important;
        }

        .dark .select2-container .select2-dropdown {
            background-color: #1f2937 !important;
            /* gray-800 */
            border-color: #374151 !important;
            /* gray-700 */
            color: white !important;
        }

        .dark .select2-container .select2-results__option[aria-selected=true] {
            background-color: #374151 !important;
        }
    </style>
@endpush
@section('content')
    @php
        $approvalConfig = config('idempiere.approval-po');
        $filterOptions = $approvalConfig['statuses']['filter_options'];
        $defaultStatus = request('status', $approvalConfig['defaults']['status_filter']);
    @endphp
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-results__option {
            font-size: 14px !important;
        }

        .select2-selection__rendered {
            font-size: 14px !important;
        }
    </style>
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-title-md2 font-bold text-black dark:text-white">
                Approval PO
            </h2>
            <nav>
                <ol class="flex items-center gap-2">
                    <li><a class="font-medium hover:text-brand-500" href="{{ route('dashboard') }}">Dashboard /</a></li>
                    <li class="font-medium text-primary">Approval PO</li>
                </ol>
            </nav>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:gap-6 mb-6">
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center shadow-sm">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">{{ number_format($countAll) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">ALL DOCUMENTS</p>
            </div>
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center shadow-sm">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
                    {{ number_format($countPending) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">PENDING</p>
            </div>
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center shadow-sm">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
                    {{ number_format($countApproved) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">APPROVED</p>
            </div>
            <div
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center shadow-sm">
                <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
                    {{ number_format($countRejected) }}
                </h4>
                <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">REJECTED</p>
            </div>
        </div>

        <!-- Filter & Search -->
        <div
            class="mb-5 flex flex-col gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <!-- Top Row: Status Filter & Search -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <!-- Status Dropdown -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select id="statusFilter" class="select2 w-full">
                        @foreach($filterOptions as $filterOption)
                            <option value="{{ $filterOption['value'] }}" {{ $defaultStatus == $filterOption['value'] ? 'selected' : '' }}>
                                {{ $filterOption['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Supplier</label>
                    <select id="supplierFilter" class="select2 w-full">
                        @if($selectedSupplier)
                            <option value="{{ $selectedSupplier->id }}" selected>{{ $selectedSupplier->text }}</option>
                        @endif
                    </select>
                </div>

                <!-- Search -->
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <div class="relative w-full">
                        <input type="text" id="searchInput" placeholder="Search..." value="{{ request('search') }}"
                            class="w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-500 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-600 dark:text-white dark:bg-gray-800"
                            style="height: 42px;">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Table Container -->
        <div id="table-container" class="relative min-h-[400px]">
            <!-- Loading Overlay -->
            <div id="table-loading"
                class="hidden absolute inset-0 bg-white/50 dark:bg-gray-900/50 z-10 items-center justify-center backdrop-blur-[1px] rounded-xl">
                <svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>

            <!-- Content -->
            <div id="table-content">
                @include('pages.approval-po.partials.table', ['orders' => $orders])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Include jQuery if not globally available, but assume it is from layout or add if needed.
                                                                                                             If $ is not defined, uncomment: -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let searchTimeout;

        document.addEventListener('DOMContentLoaded', () => {
            bindPaginationLinks();

            // Ensure jQuery is ready
            if (typeof $ !== 'undefined') {
                // Status Filter (Static Options)
                $('#statusFilter').select2({
                    minimumResultsForSearch: Infinity, // Disable search for fixed options
                    width: '100%'
                });

                $('#supplierFilter').select2({
                    ajax: {
                        url: '{{ route("approval-po.suppliers") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                term: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results,
                                pagination: data.pagination
                            };
                        },
                        cache: true
                    },
                    placeholder: "Select Supplier",
                    allowClear: true,
                    width: '100%'
                });

                $('#statusFilter, #supplierFilter').on('change', function () {
                    fetchData();
                });
            } else {
                console.error('jQuery is not loaded!');
            }
        });

        // Search
        document.getElementById('searchInput').addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => fetchData(), 500);
        });

        function fetchData(page = 1) {
            const loading = document.getElementById('table-loading');
            loading.classList.remove('hidden');
            loading.classList.add('flex');

            const params = new URLSearchParams();

            const status = $('#statusFilter').val();
            params.append('status', status);

            // Page
            if (page > 1) {
                params.append('page', page);
            }

            const supplier = document.getElementById('supplierFilter').value;
            if (supplier) params.append('c_bpartner_id', supplier);

            const search = document.getElementById('searchInput').value;
            if (search) params.append('search', search);

            // Update URL (optional, for browser history) without reload
            const newUrl = "{{ route('approval-po.index') }}?" + params.toString();
            window.history.replaceState(null, null, newUrl);

            axios.get(newUrl)
                .then(res => {
                    document.getElementById('table-content').innerHTML = res.data;
                    bindPaginationLinks();
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                })
                .finally(() => {
                    loading.classList.remove('flex');
                    loading.classList.add('hidden');
                });
        }

        function bindPaginationLinks() {
            // Find links in the new content
            const links = document.querySelectorAll('#table-content a'); // Pagination links usually inside nav inside table-content

            links.forEach(link => {
                // Check if it's a pagination link
                if (link.href && link.href.includes('page=')) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const url = new URL(this.href);
                        const page = url.searchParams.get('page');
                        fetchData(page);
                    });
                }
            });
        }
    </script>
@endpush