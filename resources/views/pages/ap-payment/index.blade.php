@extends('layouts.app')

@section('content')
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">

        {{-- Summary Cards --}}
        <x-ap-payment.summary-cards :countAll="$countAll" :countDraft="$countDraft" :countInProgress="$countInProgress"
            :countCompleted="$countCompleted" />

        {{-- Action / Filter Bar --}}
        <x-ap-payment.filter-bar />

        {{-- Table --}}
        <div id="payment-table-wrapper">
            <x-ap-payment.payment-table :payments="$payments" />
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const tableWrapper = document.getElementById('payment-table-wrapper');

                let debounceTimer;
                let currentFilters = {};

                const fetchData = (url = '{{ route("ap-payment.index") }}', extraParams = {}) => {
                    const query = searchInput ? searchInput.value : '';
                    tableWrapper.classList.add('opacity-50', 'pointer-events-none');

                    const params = { search: query, ...currentFilters, ...extraParams };

                    axios.get(url, { params })
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

                tableWrapper.addEventListener('click', function (e) {
                    const link = e.target.closest('a[href]');
                    if (link && link.href && link.href.includes('page=')) {
                        e.preventDefault();
                        const linkUrl = new URL(link.href);
                        const page = linkUrl.searchParams.get('page');
                        fetchData('{{ route("ap-payment.index") }}', { page });
                    }
                });
            });
        </script>
    @endpush

@endsection