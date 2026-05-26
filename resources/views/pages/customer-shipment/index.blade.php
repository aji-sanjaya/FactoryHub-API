@extends('layouts.app')

@section('content')
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">

        <!-- Summary Cards -->
        <x-customer-shipment.summary-cards :countAll="$countAll" :countDraft="$countDraft"
            :countInProgress="$countInProgress" :countCompleted="$countCompleted" />

        <!-- Action / Filter Bar -->
        <x-customer-shipment.filter-bar />

        <!-- Table -->
        <div id="customer-shipment-table-wrapper">
            <x-customer-shipment.customer-shipmentTable :shipments="$shipments" />
        </div>

        <!-- Create Modal -->
        <x-customer-shipment.create-modal />

        <!-- Print Modal -->
        <div id="printModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div
                class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm bg-gray-500/30">
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full dark:bg-gray-800">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 h-[85vh] flex flex-col">
                        <div
                            class="flex-shrink-0 flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <label for="printStyleSelect"
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200"></label>
                                <select id="printStyleSelect" onchange="changePrintStyle(this.value)"
                                    class="text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white py-1.5 px-3">
                                    <option value="style1" selected>Style 1</option>
                                    <option value="style2">Style 2</option>
                                </select>
                            </div>
                            <button onclick="closePrintModal()"
                                class="text-gray-400 hover:text-gray-500 focus:outline-none bg-transparent border-0">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
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

    </div>

    @push('scripts')
        <script>
            // Print Modal Functions
            window.openPrintModal = function (url, encryptedId) {
                const modal = document.getElementById('printModal');
                const iframe = document.getElementById('printFrame');
                const styleSelect = document.getElementById('printStyleSelect');
                if (modal && iframe) {
                    window.currentPrintId = encryptedId;
                    if (styleSelect) styleSelect.value = 'style1'; // reset to style 1
                    iframe.src = url;
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }

            window.changePrintStyle = function (style) {
                const iframe = document.getElementById('printFrame');
                if (!iframe || !window.currentPrintId) return;

                let url = '';
                if (style === 'style1') {
                    url = `/customer-shipment/print/${window.currentPrintId}`;
                } else if (style === 'style2') {
                    url = `/customer-shipment/print-style2/${window.currentPrintId}`;
                } else if (style === 'style3') {
                    url = `/customer-shipment/print-style3/${window.currentPrintId}`;
                }
                iframe.src = url;
            }

            window.closePrintModal = function () {
                const modal = document.getElementById('printModal');
                const iframe = document.getElementById('printFrame');
                if (modal) {
                    modal.classList.add('hidden');
                    if (iframe) iframe.src = 'about:blank';
                    document.body.style.overflow = '';
                }
            }

            window.toggleTracking = function (el, docId, field) {
                const isTargetChecked = el.checked;

                // Keep the checkbox looking like it didn't change while loading
                el.checked = !isTargetChecked;

                const value = isTargetChecked ? 'Y' : 'N';

                // Show spinner by hiding the checkbox
                el.style.display = 'none';

                // Create spinner element and insert it
                let spinner = document.createElement('div');
                spinner.className = 'tracking-spinner inline-block w-4 h-4 border-2 border-brand-500 border-t-transparent rounded-full animate-spin mx-auto';
                el.parentNode.insertBefore(spinner, el.nextSibling);

                axios.put(`{{ url('/customer-shipment/toggle-tracking') }}/${docId}`, {
                    field: field,
                    value: value,
                    _token: '{{ csrf_token() }}'
                }).then(res => {
                    // Update state to the intended one on success
                    el.checked = isTargetChecked;

                    // Remove spinner from DOM, show checkbox explicitly
                    spinner.remove();
                    el.style.display = '';

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: res.data.message || 'Tracking status updated.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }).catch(err => {
                    // Status remains untouched since we didn't update it yet
                    // Remove spinner, restore checkbox
                    spinner.remove();
                    el.style.display = '';

                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: err.response?.data?.message || 'Gagal update status tracking.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const tableWrapper = document.getElementById('customer-shipment-table-wrapper');

                let debounceTimer;
                let currentFilters = {}; // Store active filters

                // Function to fetch data (URL optional)
                const fetchData = (url = '{{ route("customer-shipment.index") }}') => {
                    const query = searchInput.value;
                    const wrapper = document.getElementById('customer-shipment-table-wrapper');

                    // Add loading state
                    wrapper.classList.add('opacity-50', 'pointer-events-none');

                    console.log('Fetching data from:', url, { search: query, filters: currentFilters });


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

                // Listen for Customer Shipment Created Event
                document.addEventListener('customer-shipment-created', function () {
                    console.log('Customer Shipment created event caught, refreshing table...');
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