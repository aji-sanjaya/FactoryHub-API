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
            background-color: #111827 !important;
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

        .dark .select2-search__field {
            background-color: #374151 !important;
            color: white !important;
            border-color: #4b5563 !important;
        }
    </style>
    <!-- Select2 CSS -->

@endpush

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <div class="space-y-6">
        <!-- Filter Card -->
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Purchase Order Report Filter
                </h3>
            </div>
            <div class="p-6">
                <!-- Filter Form -->
                <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-6 items-end">

                    <!-- Start Date -->
                    <div class="sm:col-span-6 lg:col-span-6">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Start Date
                            (Ordered)</label>
                        <div class="relative">
                            <x-form.date-picker id="start_date" name="start_date" placeholder="Select Date"
                                dateFormat="Y-m-d" />
                        </div>
                    </div>

                    <!-- End Date -->
                    <div class="sm:col-span-6 lg:col-span-6">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">End Date
                            (Ordered)</label>
                        <div class="relative">
                            <x-form.date-picker id="end_date" name="end_date" placeholder="Select Date"
                                dateFormat="Y-m-d" />
                        </div>
                    </div>

                    <!-- Supplier (Select2) -->
                    <div class="sm:col-span-6 lg:col-span-6">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Supplier</label>
                        <select id="c_bpartner_id" name="c_bpartner_id" class="select2 w-full" style="width: 100%">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->text }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="sm:col-span-6 lg:col-span-6">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="search_query" name="search_query" placeholder="PO No, Product, etc..."
                                class="w-full rounded-lg border-gray-300 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white placeholder-gray-400" />
                        </div>
                    </div>
                    <!-- Buttons -->
                    <div class="sm:col-span-2 lg:col-span-2 flex gap-2">
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-500/30 transition-all">
                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filter
                        </button>
                        <button type="button" onclick="exportExcel()"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-green-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-500/30 transition-all">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Export
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Table Card -->
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <!-- Table Header -->
            <div
                class="flex flex-col sm:flex-row justify-between items-center border-b border-gray-200 px-6 py-4 dark:border-gray-800 gap-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Purchase Order Report Results
                </h3>

                <!-- Rows per Page -->
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-500 dark:text-gray-400">Rows per page:</label>
                    <select id="per_page" onchange="fetchData(1)"
                        class="rounded border-gray-300 py-1.5 pl-2 pr-8 text-sm focus:border-brand-500 focus:ring-brand-500/20 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-left dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                            <th
                                class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                No
                            </th>
                            <th
                                class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                PO Num / Date</th>
                            <th
                                class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Supplier</th>
                            <th
                                class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Product</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Order Qty</th>
                            <th
                                class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Receipt Num / Date</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Receipt Qty</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody"
                        class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                    <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-sm font-medium">Please apply filters to generate the report</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div id="paginationControls"
                class="hidden flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 dark:border-gray-800 dark:bg-gray-900">
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-400">
                            Showing <span class="font-medium text-gray-900 dark:text-white" id="pageFrom">0</span> to <span
                                class="font-medium text-gray-900 dark:text-white" id="pageTo">0</span> of <span
                                class="font-medium text-gray-900 dark:text-white" id="pageTotal">0</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <button onclick="changePage('prev')" id="btnPrev"
                                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed dark:ring-gray-700 dark:hover:bg-gray-800">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>

                            <span
                                class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 focus:outline-offset-0 dark:text-white dark:ring-gray-700">
                                Page <span id="currentPageDisplay" class="mx-1">1</span> of <span id="lastPageDisplay"
                                    class="mx-1">1</span>
                            </span>

                            <button onclick="changePage('next')" id="btnNext"
                                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed dark:ring-gray-700 dark:hover:bg-gray-800">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd"
                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Mobile Pagination Simple -->
                <div class="flex flex-1 justify-between sm:hidden">
                    <button onclick="changePage('prev')" id="mobilePrevBtn"
                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">Previous</button>
                    <button onclick="changePage('next')" id="mobileNextBtn"
                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">Next</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <!-- jQuery and Select2 JS -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script>
            let currentPage = 1;
            let lastPage = 1;

            $(document).ready(function () {
                // Initialize Select2
                $('.select2').select2({
                    width: '100%',
                    placeholder: 'All Suppliers',
                    allowClear: true
                });
            });

            document.getElementById('filterForm').addEventListener('submit', function (e) {
                e.preventDefault();
                fetchData(1);
            });

            function changePage(direction) {
                if (direction === 'prev' && currentPage > 1) {
                    fetchData(currentPage - 1);
                } else if (direction === 'next' && currentPage < lastPage) {
                    fetchData(currentPage + 1);
                }
            }

            function fetchData(page = 1) {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                const searchQuery = document.getElementById('search_query').value;
                // For Select2, use jQuery to get value if needed
                const supplierId = $('#c_bpartner_id').val() || '';
                const perPage = document.getElementById('per_page').value;

                const tbody = document.getElementById('reportTableBody');

                tbody.innerHTML = `
                                                                                                            <tr>
                                                                                                                <td colspan="7" class="py-12  text-center">
                                                                                                                    <div class="inline-flex items-center gap-2 text-brand-600">
                                                                                                                        <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                                                                        </svg>
                                                                                                                        <span class="font-medium animate-pulse">Loading data...</span>
                                                                                                                    </div>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        `;

                const params = new URLSearchParams({
                    start_date: startDate,
                    end_date: endDate,
                    search_query: searchQuery,
                    c_bpartner_id: supplierId,
                    page: page,
                    per_page: perPage
                });

                axios.get(`{{ route('po-report.data') }}?${params.toString()}`)
                    .then(response => {
                        const res = response.data;
                        renderTable(res.data, res.from);
                        updatePagination(res);
                        currentPage = res.current_page;
                        lastPage = res.last_page;
                    })
                    .catch(error => {
                        console.error(error);
                        let msg = "Failed to load data.";
                        if (error.response && error.response.data && error.response.data.message) {
                            msg = error.response.data.message;
                        }
                        tbody.innerHTML = `
                                                                                                                    <tr>
                                                                                                                        <td colspan="7" class="py-12 text-center text-red-500">
                                                                                                                            <div class="flex flex-col items-center gap-2">
                                                                                                                                <svg class="w-8 h-8 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                                                                                                <span>${msg}</span>
                                                                                                                            </div>
                                                                                                                        </td>
                                                                                                                    </tr>`;
                    });
            }

            function renderTable(data, fromIndex) {
                const tbody = document.getElementById('reportTableBody');

                if (!data || data.length === 0) {
                    tbody.innerHTML = `
                                                                                                                <tr>
                                                                                                                    <td colspan="7" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                                                                                                        <div class="flex flex-col items-center justify-center">
                                                                                                                            <svg class="w-10 h-10 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                                                                                            </svg>
                                                                                                                            <span>No records found matching your filters.</span>
                                                                                                                        </div>
                                                                                                                    </td>
                                                                                                                </tr>`;
                    return;
                }

                let html = '';
                const startNo = fromIndex || 1;

                data.forEach((row, index) => {
                    const poDate = formatDate(row.po_date);
                    const receiptDate = formatDate(row.receipt_date);

                    html += `
                                                                                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                                                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-medium">${startNo + index}</td>

                                                                                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                                                                                        <div class="flex flex-col">
                                                                                                                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs font-medium dark:bg-blue-900/30 dark:text-blue-400 w-fit">${row.po_num || '-'}</span>
                                                                                                                            ${poDate ? `<span class="text-xs text-gray-400 font-mono mt-0.5">${poDate}</span>` : ''}
                                                                                                                        </div>
                                                                                                                    </td>

                                                                                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                                                                                        ${row.supplier_name || '-'}
                                                                                                                    </td>

                                                                                                                    <td class="px-6 py-4">
                                                                                                                        <div class="flex flex-col">
                                                                                                                            <span class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs" title="${row.product_name}">${row.product_name || '-'}</span>
                                                                                                                            <span class="text-xs text-gray-500 font-mono mt-0.5">${row.product_code || '-'}</span>
                                                                                                                        </div>
                                                                                                                    </td>

                                                                                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300 font-mono">
                                                                                                                        ${formatNumber(row.order_qty)}
                                                                                                                    </td>

                                                                                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                                                                                        <div class="flex flex-col">
                                                                                                                            ${row.receipt_num ? `<span class="bg-green-50 text-green-700 px-2 py-0.5 rounded text-xs font-medium dark:bg-green-900/30 dark:text-green-400 w-fit">${row.receipt_num}</span>` : '-'}
                                                                                                                            ${receiptDate ? `<span class="text-xs text-gray-400 font-mono mt-0.5">${receiptDate}</span>` : ''}
                                                                                                                        </div>
                                                                                                                    </td>

                                                                                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300 font-mono">
                                                                                                                        ${formatNumber(row.receipt_qty)}
                                                                                                                    </td>
                                                                                                                </tr>
                                                                                                            `;
                });

                tbody.innerHTML = html;
            }

            function formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }

            function exportExcel() {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                const searchQuery = document.getElementById('search_query').value;
                const supplierId = $('#c_bpartner_id').val() || '';

                const params = new URLSearchParams({
                    start_date: startDate,
                    end_date: endDate,
                    search_query: searchQuery,
                    c_bpartner_id: supplierId
                });

                window.location.href = `{{ route('po-report.export') }}?${params.toString()}`;
            }

            function updatePagination(meta) {
                const container = document.getElementById('paginationControls');

                if (meta.total === 0) {
                    container.classList.add('hidden');
                    return;
                }

                container.classList.remove('hidden');

                // Update text
                document.getElementById('pageFrom').textContent = meta.from || 0;
                document.getElementById('pageTo').textContent = meta.to || 0;
                document.getElementById('pageTotal').textContent = meta.total || 0;
                document.getElementById('currentPageDisplay').textContent = meta.current_page;
                document.getElementById('lastPageDisplay').textContent = meta.last_page;

                // Update buttons state
                const prevDisabled = meta.current_page <= 1;
                const nextDisabled = meta.current_page >= meta.last_page;

                document.getElementById('btnPrev').disabled = prevDisabled;
                document.getElementById('btnNext').disabled = nextDisabled;

                // Mobile buttons
                document.getElementById('mobilePrevBtn').disabled = prevDisabled;
                document.getElementById('mobileNextBtn').disabled = nextDisabled;
            }

            function formatNumber(value) {
                if (value === null || value === undefined) return '-';
                let num = parseFloat(value);
                if (num % 1 === 0) {
                    return num.toLocaleString('en-US');
                }
                return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
            }
        </script>
    @endpush
@endsection