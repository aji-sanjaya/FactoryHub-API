@extends('layouts.app')

@section('content')
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">

        <!-- Summary Cards -->
        <x-material-receipt.summary-cards
            :countAll="$countAll"
            :countDraft="$countDraft"
            :countInProgress="$countInProgress"
            :countCompleted="$countCompleted" />

        <!-- Action / Filter Bar -->
        <x-material-receipt.filter-bar />

        <!-- Table -->
        <div id="receipt-table-wrapper">
            <x-material-receipt.receipt-table :receipts="$receipts" />
        </div>

    </div>

    <!-- Print Modal -->
    <div id="printModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm bg-gray-500/30">
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full dark:bg-gray-800">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 h-[85vh] flex flex-col">
                    <div class="flex-shrink-0 flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Print Preview</h3>
                        <button onclick="closePrintModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none bg-transparent border-0">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 w-full relative bg-gray-100 dark:bg-gray-900 mt-4 rounded-lg overflow-hidden">
                        <iframe id="printFrame" src="" class="absolute inset-0 w-full h-full border-0"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.openPrintModal = function (url) {
                const modal = document.getElementById('printModal');
                const iframe = document.getElementById('printFrame');
                if (modal && iframe) {
                    iframe.src = url;
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            };
            window.closePrintModal = function () {
                const modal = document.getElementById('printModal');
                const iframe = document.getElementById('printFrame');
                if (modal) {
                    modal.classList.add('hidden');
                    if (iframe) iframe.src = 'about:blank';
                    document.body.style.overflow = '';
                }
            };

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const tableWrapper = document.getElementById('receipt-table-wrapper');

                let debounceTimer;
                let currentFilters = {};

                const fetchData = (url = '{{ route("material-receipt.index") }}') => {
                    const query = searchInput ? searchInput.value : '';
                    tableWrapper.classList.add('opacity-50', 'pointer-events-none');

                    const isPagination = url.includes('page=');
                    const config = isPagination ? {} : {
                        params: { search: query, ...currentFilters }
                    };

                    axios.get(url, config)
                        .then(response => {
                            tableWrapper.innerHTML = response.data.html;
                        })
                        .catch(error => console.error('Error fetching data:', error))
                        .finally(() => {
                            tableWrapper.classList.remove('opacity-50', 'pointer-events-none');
                        });
                };

                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => fetchData(), 400);
                    });
                }

                document.addEventListener('filter-applied', function (e) {
                    currentFilters = e.detail;
                    fetchData();
                });

                // Handle pagination clicks dynamically
                tableWrapper.addEventListener('click', function (e) {
                    const link = e.target.closest('a[href]');
                    if (link && link.href && link.href.includes('page=')) {
                        e.preventDefault();
                        let href = link.getAttribute('href');
                        @if(app()->environment('production'))
                        // Force HTTPS to prevent blocked:mixed-content error in production
                        href = href.replace(/^http:\/\//i, 'https://');
                        @endif
                        fetchData(href);
                    }
                });
            });
        </script>
    @endpush
@endsection
