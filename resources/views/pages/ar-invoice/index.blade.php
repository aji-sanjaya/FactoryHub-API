@extends('layouts.app')

@section('content')
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">

        <!-- Summary Cards -->
        <x-ar-invoice.summary-cards :countAll="$countAll" :countDraft="$countDraft" :countInProgress="$countInProgress"
            :countCompleted="$countCompleted" />

        <!-- Action / Filter Bar -->
        <x-ar-invoice.filter-bar />

        <!-- Table -->
        <div id="invoice-table-wrapper">
            <x-ar-invoice.invoice-table :invoices="$invoices" />
        </div>

        <!-- Print Modal -->
        <div id="printModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div
                class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm">
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-2 sm:pb-2 h-[85vh] flex flex-col">
                        <div class="flex-shrink-0 flex justify-between items-center  pb-1 relative z-10"
                            style="background-color: transparent;">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title"></h3>
                            <button onclick="closePrintModal()"
                                class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Close
                            </button>
                        </div>
                        <div class="flex-1 w-full relative bg-gray-100 rounded-lg overflow-hidden z-0">
                            <iframe id="printFrame" src="" class="absolute inset-0 w-full h-full border-0"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            // Print Modal Functions
            window.openPrintModal = function (url) {
                const modal = document.getElementById('printModal');
                const iframe = document.getElementById('printFrame');
                iframe.src = url;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }

            window.closePrintModal = function () {
                const modal = document.getElementById('printModal');
                const iframe = document.getElementById('printFrame');
                modal.classList.add('hidden');
                iframe.src = 'about:blank'; // Clear source to stop loading
                document.body.style.overflow = ''; // Restore scrolling
            }

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const tableWrapper = document.getElementById('invoice-table-wrapper');

                let debounceTimer;
                let currentFilters = {};

                const fetchData = (url = '{{ route("ar-invoice.index") }}', extraParams = {}) => {
                    const query = searchInput ? searchInput.value : '';
                    tableWrapper.classList.add('opacity-50', 'pointer-events-none');

                    // Always merge search + currentFilters even for pagination links
                    const params = { search: query, ...currentFilters, ...extraParams };

                    axios.get(url, { params })
                        .then(response => {
                            tableWrapper.innerHTML = response.data.html;
                            // Rebind pagination after HTML swap
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

                // Delegate pagination click events — strip page param from link URL
                // and pass it as an axios param so filters are preserved
                tableWrapper.addEventListener('click', function (e) {
                    const link = e.target.closest('a[href]');
                    if (link && link.href && link.href.includes('page=')) {
                        e.preventDefault();
                        const linkUrl = new URL(link.href);
                        const page = linkUrl.searchParams.get('page');
                        fetchData('{{ route("ar-invoice.index") }}', { page });
                    }
                });
            });
        </script>
    @endpush
@endsection