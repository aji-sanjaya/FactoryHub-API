@extends('layouts.app')

@section('content')
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">

        <!-- Summary Cards -->
        <x-purchase-order.summary-cards :countAll="$countAll" :countDraft="$countDraft" :countInProgress="$countInProgress"
            :countCompleted="$countCompleted" />

        <!-- Action / Filter Bar -->
        <x-purchase-order.filter-bar />

        <!-- Table -->
        <div id="requisition-table-wrapper">
            <x-purchase-order.order-table :orders="$orders" />
        </div>

        <!-- Create Modal -->
        <x-purchase-order.create-modal />

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
                const tableWrapper = document.getElementById('requisition-table-wrapper');

                let debounceTimer;
                let currentFilters = {}; // Store active filters

                // Function to fetch data (URL optional)
                const fetchData = (url = '{{ route("purchase-order.index") }}') => {
                    const query = searchInput.value;
                    const wrapper = document.getElementById('requisition-table-wrapper');

                    // Add loading state
                    wrapper.classList.add('opacity-50', 'pointer-events-none');

                    console.log('Fetching data from:', url, { search: query, filters: currentFilters });

                    // Helper to construct URL with params if they aren't already there
                    // But since we are using withQueryString() on backend, the pagination links ALREADY have params.
                    // However, for the initial search/filter, we build params manually.
                    // If 'url' is passed (from pagination), we likely just want to fetch it directly.
                    // But wait: if I type search THEN click page 2, page 2 link might be stale if the table didn't refresh?
                    // YES, the table refetches on search input, so the pagination links ARE fresh.
                    // So we can blindly trust the URL from the link.

                    // Logic: If plain URL (route), append params. If paginated URL, use as is (or merge??)
                    // Safest: Use params object for Search/Filter, but for Pagination URL, mostly trust it OR merge.
                    // Let's rely on Axios params merging.

                    // Actually, if we pass a full URL to axios.get, and ALSO params, axios merges them.
                    // If URL already has ?search=foo, and we pass params: {search: foo}, it might duplicate or handle it.
                    // Simpler: If it's a pagination link, just use that URL. The controller handles it.
                    // If it's a Search/Filter event, use base route + params.

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
                            // Remove loading state
                            wrapper.classList.remove('opacity-50', 'pointer-events-none');
                        });
                };

                // Search Input Listener
                searchInput.addEventListener('input', function (e) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => fetchData(), 300); // Call without arg -> uses base route + params
                });

                // Listen for Filter Event
                document.addEventListener('filter-applied', function (e) {
                    console.log('Filter applied event caught:', e.detail);
                    currentFilters = e.detail;
                    fetchData(); // Call without arg -> uses base route + params
                });

                // Listen for Requisition Created Event
                document.addEventListener('requisition-created', function () {
                    console.log('Requisition created event caught, refreshing table...');
                    fetchData();
                });

                // Pagination Click Interceptor
                tableWrapper.addEventListener('click', function (e) {
                    const link = e.target.closest('a');
                    if (link && tableWrapper.contains(link)) {
                        // Check if it's a pagination link (roughly)
                        // Or just intercept ALL links inside the wrapper?
                        // Yes, actions usually are buttons or specific links. Pagination are <a>.
                        // We should be careful about "View" or "Edit" links if they are <a> tags.
                        // View link probably goes to a new page. Pagination stays here.
                        // Pagination links usually have 'page=' in href, or we can check parent class.
                        // Our custom pagination 'tailwind-buttons' produces plain <a> tags.
                        // Let's check if href contains 'page='.

                        let href = link.getAttribute('href');
                        if (href && href.includes('page=')) {
                            e.preventDefault();
                            @if(app()->environment('production'))
                            // Force HTTPS to prevent blocked:mixed-content error in production
                            href = href.replace(/^http:\/\//i, 'https://');
                            @endif
                            fetchData(href);
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection