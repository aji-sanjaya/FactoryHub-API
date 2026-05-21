@extends('layouts.app')

@section('content')

    @php 
        $isNew = is_null($request);
        // Active tab from controller or default
        $activeTab = $activeTab ?? 'header';

        // Calculate Default Org (Minimum ID)
        $defaultOrgId = null;
        if (isset($organizations) && count($organizations) > 0) {
            $sortedOrgs = collect($organizations)->sortBy('id');
            $defaultOrgId = $sortedOrgs->first()->id;
        }

        // Default Values for New Record Logic
        $docNo = $isNew ? '** New **' : $request->documentno;
        // $status is already passed from controller
        $desc = $isNew ? '' : $request->description;

        // Current Org ID Selection
        $currentOrgId = $isNew ? $defaultOrgId : $request->ad_org_id;
        $dateTrx = $isNew ? now()->format('Y-m-d') : \Carbon\Carbon::parse($request->date_trx)->format('Y-m-d');
        $dateAcct = $isNew ? now()->format('Y-m-d') : \Carbon\Carbon::parse($request->date_acct)->format('Y-m-d');
        
        // Document ID Param for Links
        $docIdParam = request('document_id');

        // Read Only Logic
        $isReadOnly = false;
        if (!$isNew && isset($request->docstatus)) {
            $isReadOnly = in_array($request->docstatus, ['CO', 'CL', 'VO', 'RE']);
        }
    @endphp


    <script>
        (function () {
            function setTabLoading(tabName) {
                var container = document.getElementById('tab-' + tabName);
                if (!container) return;
                container.innerHTML = '<div class="flex flex-col justify-center items-center py-24">' +
                    '<div class="rounded-full bg-brand-50 p-4 mb-4">' +
                    '<svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                    '</svg>' +
                    '</div>' +
                    '<span class="text-gray-500 font-medium dark:text-gray-400">Loading details...</span>' +
                    '</div>';
            }

            function toggleNav(id, active) {
                var el = document.getElementById(id);
                if (!el) return;
                var icon = el.querySelector('svg');
                var activeClasses = ['border-brand-500', 'text-brand-600'];
                var inactiveClasses = ['border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'];

                if (active) {
                    el.classList.remove.apply(el.classList, inactiveClasses);
                    el.classList.add.apply(el.classList, activeClasses);
                    if (icon) {
                        icon.classList.remove('text-gray-400', 'group-hover:text-gray-500');
                        icon.classList.add('text-brand-500');
                    }
                } else {
                    el.classList.remove.apply(el.classList, activeClasses);
                    el.classList.add.apply(el.classList, inactiveClasses);
                    if (icon) {
                        icon.classList.remove('text-brand-500');
                        icon.classList.add('text-gray-400', 'group-hover:text-gray-500');
                    }
                }
            }

            window.switchTab = function (tabName) {
                var tabs = ['header', 'lines', 'attachments'];
                if (tabs.indexOf(tabName) === -1) return false;

                for (var i = 0; i < tabs.length; i++) {
                    var t = tabs[i];
                    var tabEl = document.getElementById('tab-' + t);
                    if (tabEl) {
                        tabEl.classList.remove('block');
                        tabEl.classList.add('hidden');
                    }
                    toggleNav('nav-' + t, false);
                }

                var activeTab = document.getElementById('tab-' + tabName);
                if (activeTab) {
                    activeTab.classList.remove('hidden');
                    activeTab.classList.add('block');
                }
                toggleNav('nav-' + tabName, true);

                var headerActions = document.getElementById('header-actions');
                if (headerActions) {
                    if (tabName === 'header') {
                        headerActions.classList.remove('hidden');
                        headerActions.classList.add('block');
                    } else {
                        headerActions.classList.remove('block');
                        headerActions.classList.add('hidden');
                    }
                }

                var shouldAjax = false;
                if (tabName === 'attachments') {
                    shouldAjax = true;
                } else if (tabName === 'lines') {
                    var linesContainer = document.getElementById('tab-lines');
                    if (!linesContainer || linesContainer.innerHTML.trim() === '') {
                        shouldAjax = true;
                    }
                }

                if (shouldAjax) {
                    setTabLoading(tabName);
                }

                if (shouldAjax && typeof loadTabContent === 'function') {
                    loadTabContent(tabName);
                }

                window.activeTab = tabName;
                try {
                    var visibleUrl = new URL(window.location.href);
                    visibleUrl.searchParams.set('tab', tabName);
                    window.history.replaceState(null, '', visibleUrl.toString());
                } catch (e) {
                    // ignore URL update errors
                }

                return false;
            };
        })();
    </script>

    <!-- Header / Breadcrumb -->
    <div>
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('petty-cash-request.index') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $isNew ? 'New Petty Cash Request' : $docNo }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                        @if(!$isNew)
                            @php
                                $headerStatusColor = match($request ? $request->docstatus : 'DR') {
                                    'NA', 'VO', 'RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'CO', 'CL', 'AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'IP' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'DR' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                };
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $headerStatusColor }}">
                                {{ $status }}
                            </span>
                            <span class="text-gray-300">•</span>
                        @endif
                        <span>{{ $isNew ? 'Create a new petty cash request' : 'Manage petty cash request details' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <!-- Print Document Button -->
                @if(isset($request) && in_array($request->docstatus, ['IP', 'CO', 'DR']))
                    <button type="button"
                        onclick="window.print()"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 shadow-sm transition-all gap-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                            </path>
                        </svg>
                        Print Document
                    </button>
                @endif

                <!-- Header Buttons Only Visible on Header Tab -->
                <div id="header-actions" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @if(!$isReadOnly)
                        <button onclick="submitHeader()"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm hover:shadow transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            {{ $isNew ? 'Create Request' : 'Save Changes' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Card with Tabs -->
        <div
            class="bg-white rounded-2xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 overflow-visible relative">

            <!-- Tabs Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 px-6 sm:px-8 rounded-t-2xl">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="#" onclick="switchTab('header'); return false;" id="nav-header"
                        class="{{ $activeTab == 'header' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4 {{ $activeTab == 'header' ? 'text-brand-500' : 'text-gray-400 group-hover:text-gray-500' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Header Details
                    </a>
                    <a href="#" onclick="switchTab('lines'); return false;" id="nav-lines"
                        class="{{ $activeTab == 'lines' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $isNew ? 'cursor-not-allowed opacity-50 pointer-events-none' : '' }}">
                        <svg class="w-4 h-4 {{ $activeTab == 'lines' ? 'text-brand-500' : 'text-gray-400 group-hover:text-gray-500' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        Lines
                    </a>
                    <a href="#" onclick="switchTab('attachments'); return false;" id="nav-attachments"
                        class="{{ $activeTab == 'attachments' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $isNew ? 'cursor-not-allowed opacity-50 pointer-events-none' : '' }}">
                        <svg class="w-4 h-4 {{ $activeTab == 'attachments' ? 'text-brand-500' : 'text-gray-400 group-hover:text-gray-500' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                            </path>
                        </svg>
                        Attachments
                    </a>
                </nav>
            </div>

            <!-- Tab Content -->
            <div id="tab-content-wrapper">
                <!-- Header Content -->
                <div id="tab-header" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @include('pages.petty-cash-request.partials.tab-header')
                </div>

                <!-- Lines Content -->
                <div id="tab-lines" class="{{ $activeTab == 'lines' ? 'block' : 'hidden' }}">
                    @include('pages.petty-cash-request.partials.tab-lines')
                </div>

                <!-- Attachments Content -->
                <div id="tab-attachments" class="{{ $activeTab == 'attachments' ? 'block' : 'hidden' }}">
                    <!-- Content gathered via AJAX -->
                </div>
            </div>

        </div>
    </div> <!-- End Main Content -->

    @push('scripts')
        
    <!-- Select2 Resources -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


        <style>
            /* Flatpickr Calendar z-index */
            .flatpickr-calendar {
                z-index: 9999 !important;
            }

            /* Select2 Custom Styling to match Tailwind */
            .select2-container--default .select2-selection--single {
                background-color: transparent;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                height: 46px;
                display: flex;
                align-items: center;
                font-size: 14px;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #1f2937;
                padding-left: 1rem;
                line-height: normal;
                width: 100%;
                font-size: 14px;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 44px;
                right: 0.75rem;
                font-size: 14px;
            }

            .dark .select2-container--default .select2-selection--single {
                background-color: #111827;
                border-color: #374151;
                font-size: 14px;
            }

            .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #e5e7eb;
                font-size: 14px;
            }

            .dark .select2-dropdown {
                background-color: #1f2937;
                border-color: #374151;
                color: #e5e7eb;
                font-size: 14px;
            }

            .dark .select2-container--default .select2-results__option--selectable {
                background-color: #1f2937;
                color: #e5e7eb;
                font-size: 14px;
            }

            .dark .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
                background-color: #4f46e5;
                color: white;
                font-size: 14px;
            }

            .dark .select2-search__field {
                background-color: #374151;
                color: white;
                border-color: #4b5563;
                border-radius: 0.25rem;
                font-size: 14px;
            }

            /* Hide hidden elements */
            .hidden {
                display: none !important;
            }

            /* SweetAlert Small Font */
            .swal2-popup {
                font-size: 0.875rem !important;
            }

            .swal2-title {
                font-size: 1.125rem !important;
            }

            .swal2-html-container {
                font-size: 0.875rem !important;
            }
        </style> 

        <!-- SweetAlert2 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            // Initialization Function to be called on ready and after AJAX
            function initScripts() {
                console.log('=== initScripts called ===');
                console.log('jQuery loaded:', typeof $ !== 'undefined');
                console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'N/A');
                console.log('Select2 plugin exists:', typeof $.fn.select2 !== 'undefined');
                
                // Check if elements exist
                var elements = ['#org_id', '#ad_user_id', '#c_currency_id', '#c_doctype_id', '#c_costcenter_id'];
                elements.forEach(function(selector) {
                    var el = $(selector);
                    console.log('Element ' + selector + ': exists=' + (el.length > 0) + ', is visible=' + el.is(':visible'));
                });
                
                // Initialize Select2 for all dropdowns
                $('#org_id, #ad_user_id, #c_currency_id, #c_doctype_id, #c_costcenter_id').select2({
                    width: '100%',
                    placeholder: '- Select -'
                });
                
                console.log('=== Select2 initialization complete ===');
            }

            $(document).ready(function () {
                console.log('=== Document ready fired ===');
                
                // Add small delay to ensure DOM is fully ready
                setTimeout(function() {
                    initScripts();
                }, 100);

                // Initial Load for Active Tab (if not header)
                var initialTab = '{{ $activeTab }}';
                var isNew = {{ $isNew ? 'true' : 'false' }};

                if (!isNew && initialTab !== 'header') {
                    // Force load content for the active tab (especially for attachments which is AJAX only)
                    loadTabContent(initialTab);
                    window.activeTab = initialTab;
                }
            }); 

            function loadTabContent(tabName, params) {
                params = params || {};
                var container = $('#tab-' + tabName);

                // Show Loading Indicator
                container.html('<div class="flex flex-col justify-center items-center py-24">' +
                    '<div class="rounded-full bg-brand-50 p-4 mb-4">' +
                    '<svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                    '</svg>' +
                    '</div>' +
                    '<span class="text-gray-500 font-medium dark:text-gray-400">Loading details...</span>' +
                    '</div>');

                // Fetch Content
                var url = new URL(window.location.href);
                url.searchParams.set('ajax_tab', tabName);
                url.searchParams.set('_t', new Date().getTime()); // Prevent Cache

                // Merge new params
                Object.keys(params).forEach(function(key) {
                    if (params[key] === null || params[key] === '') {
                        url.searchParams.set(key, '');
                    } else {
                        url.searchParams.set(key, params[key]);
                    }
                });

                axios.get(url.toString())
                    .then(res => {
                        container.html(res.data);
                        initScripts(); // Re-bind JS events/Select2

                        // Update Browser URL silently
                        var visibleUrl = new URL(url);
                        visibleUrl.searchParams.delete('ajax_tab');
                        visibleUrl.searchParams.set('tab', tabName);
                        Object.keys(params).forEach(function(key) {
                            visibleUrl.searchParams.set(key, params[key]);
                        });
                        window.history.replaceState(null, '', visibleUrl.toString());
                    })
                    .catch(err => {
                        console.error(err);
                        container.html('<div class="flex flex-col items-center justify-center py-12">' +
                            '<div class="rounded-full bg-red-100 p-3 mb-3">' +
                            '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>' +
                            '</div>' +
                            '<h3 class="text-gray-900 font-medium">Failed to load content</h3>' +
                            '<button onclick="loadTabContent(\'' + tabName + '\')" class="mt-4 px-4 py-2 text-sm font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100">Try Again</button>' +
                            '</div>');
                    });
            }

            window.isHeaderSubmitting = false;

            function submitHeader() {
                if (window.isHeaderSubmitting) return;

                var orgId = $('#org_id').val();
                var bpartnerId = $('#c_bpartner_id').val();
                var userId = $('#ad_user_id').val();
                var currencyId = $('#c_currency_id').val();
                var docTypeId = $('#c_doctype_id').val();
                var costCenterId = $('#c_costcenter_id').val();

                var description = document.getElementById('description') ? document.getElementById('description').value : '';
                var name = document.getElementById('name') ? document.getElementById('name').value : '';
                // var value = document.getElementById('value') ? document.getElementById('value').value : '';
                var dateTrx = document.getElementById('date_trx') ? document.getElementById('date_trx').value : '';
                var dateAcct = document.getElementById('date_acct') ? document.getElementById('date_acct').value : '';

                var data = {
                    description: description,
                    name: name,
                    // value: value,
                    date_trx: dateTrx,
                    date_acct: dateAcct,
                    org_id: orgId,
                    bpartner_id: bpartnerId,
                    user_id: userId,
                    currency_id: currencyId,
                    doc_type_id: docTypeId,
                    cost_center_id: costCenterId
                };

                // Validation
                if (!data.date_trx) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Transaction Date is required',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                if (!data.org_id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Organization is required',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                // Lock submission
                window.isHeaderSubmitting = true;

                var isNew = {{ $isNew ? 'true' : 'false' }};
                var requestId = {{ isset($request) ? $request->adw_pettycash_request_id : 'null' }};

                // Show loading
                Swal.fire({
                    title: isNew ? 'Creating...' : 'Saving...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function () { Swal.showLoading(); }
                });

                if (isNew) {
                    axios.post('{{ route("petty-cash-request.store") }}', data)
                        .then(res => {
                            Swal.close();
                            if (res.data && res.data.data && res.data.data.encrypted_id) {
                                window.location.href = "{{ route('petty-cash-request.index') }}?document_id=" + res.data.data.encrypted_id;
                            } else {
                                window.location.reload();
                            }
                        })
                        .catch(err => {
                            window.isHeaderSubmitting = false;
                            var msg = err && err.response && err.response.data && err.response.data.message
                                ? err.response.data.message
                                : err.message;
                            Swal.fire({
                                icon: 'error',
                                title: 'Error Creating',
                                text: msg,
                                confirmButtonColor: '#4f46e5'
                            });
                        });
                } else {
                    var updateUrl = '{{ route("petty-cash-request.update", "ID_PLACEHOLDER") }}'.replace('ID_PLACEHOLDER', requestId);
                    axios.put(updateUrl, data)
                        .then(res => {
                            window.isHeaderSubmitting = false;
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Header updated successfully',
                                confirmButtonColor: '#4f46e5',
                                timer: 2000
                            });
                        })
                        .catch(err => {
                            window.isHeaderSubmitting = false;
                            var msg = err && err.response && err.response.data && err.response.data.message
                                ? err.response.data.message
                                : err.message;
                            Swal.fire({
                                icon: 'error',
                                title: 'Error Updating',
                                text: msg,
                                confirmButtonColor: '#4f46e5'
                            });
                        });
                }
            }

            // Document Action Modal Functions
            function openDocumentActionModal() {
                var modal = document.getElementById('documentActionModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            }

            function closeDocumentActionModal() {
                var modal = document.getElementById('documentActionModal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            }

            function processDocument(action) {
                closeDocumentActionModal();

                var actionText = '';
                var confirmText = '';

                switch (action) {
                    case 'CO':
                        actionText = 'Complete';
                        confirmText = 'Are you sure you want to complete this document?';
                        break;
                    case 'VO':
                        actionText = 'Void';
                        confirmText = 'Are you sure you want to void this document? This action cannot be undone!';
                        break;
                    case 'RE':
                        actionText = 'Re-Activate';
                        confirmText = 'Are you sure you want to re-activate this document?';
                        break;
                    case 'CL':
                        actionText = 'Close';
                        confirmText = 'Are you sure you want to close this document?';
                        break;
                }

                Swal.fire({
                    title: actionText + ' Document?',
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: action === 'VO' ? '#ef4444' : '#4f46e5',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, ' + actionText.toLowerCase() + ' it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDocumentAction(action, actionText);
                    }
                });
            }

            function performDocumentAction(action, actionText) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                var data = {
                    document_id: '{{ $docIdParam }}',
                    doc_action: action
                };

                axios.post('{{ route("petty-cash-request.process") }}', data)
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: res.data.message || 'Document ' + actionText.toLowerCase() + 'd successfully',
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            // Reload page to Header Tab
                            window.location.href = "{{ route('petty-cash-request.index') }}?document_id={{ $docIdParam }}&tab=header";
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error processing document: ' + (err.response?.data?.message || err.message),
                            confirmButtonColor: '#4f46e5'
                        });
                    });
            }

            function deleteRequest() {
                Swal.fire({
                    title: 'Delete Request?',
                    text: 'This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    Swal.fire({
                        title: 'Deleting...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: function () { Swal.showLoading(); }
                    });

                    axios.delete('{{ route("petty-cash-request.delete") }}', {
                        data: { document_id: '{{ $docIdParam }}' }
                    })
                        .then(function (res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: res.data && res.data.message ? res.data.message : 'Request deleted successfully',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(function () {
                                window.location.href = '{{ route("petty-cash-request.index") }}';
                            });
                        })
                        .catch(function (err) {
                            var msg = 'Delete failed';
                            if (err && err.response && err.response.data && err.response.data.message) {
                                msg = err.response.data.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg,
                                confirmButtonColor: '#4f46e5'
                            });
                        });
                });
            }

            // Attachments Functions
            window.handleFileUpload = function (files) {
                if (!files.length) return;
                Array.from(files).forEach(file => {
                    uploadSingleFile(file);
                });
            }

            window.uploadSingleFile = function (file) {
                var docIdElement = document.querySelector('input[name="document_id"]');
                var docId = docIdElement ? docIdElement.value : '{{ $docIdParam ?? "" }}';

                if (!docId) {
                    Swal.fire('Error', 'Document ID not found. Please save the document first.', 'error');
                    return;
                }

                // Show Loading
                Swal.fire({
                    title: 'Uploading...',
                    text: 'Please wait while your file is being uploaded.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                var formData = new FormData();
                formData.append('document_id', docId);
                formData.append('file', file);

                axios.post('{{ route("petty-cash-request.attachment.upload") }}', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
                    .then(res => {
                        // Reload attachments
                        loadTabContent('attachments');

                        // Show Success
                        Swal.fire({
                            icon: 'success',
                            title: 'Uploaded!',
                            text: 'File uploaded successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Upload Failed', err.response?.data?.message || 'Unknown error', 'error');
                    });
            }

            window.deleteAttachment = function (attId) {
                Swal.fire({
                    title: 'Delete Attachment?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var docIdElement = document.querySelector('input[name="document_id"]');
                        var docId = docIdElement ? docIdElement.value : '{{ $docIdParam ?? "" }}';

                        // Show Loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        axios.delete('{{ route("petty-cash-request.attachment.delete") }}', {
                            data: {
                                document_id: docId,
                                attachment_id: attId
                            }
                        })
                            .then(res => {
                                loadTabContent('attachments');

                                // Show Success Modal
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'Attachment has been deleted.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            })
                            .catch(err => {
                                Swal.fire('Delete Failed', err.response?.data?.message || 'Unknown error', 'error');
                            });
                    }
                })
            }

            // Bulk Attachment Functions
            window.toggleSelectAll = function(cb) {
                document.querySelectorAll('.attachment-checkbox').forEach(c => c.checked = cb.checked);
                window.updateDeleteButtonState();
            };

            window.updateDeleteButtonState = function() {
                const checked = document.querySelectorAll('.attachment-checkbox:checked').length;
                const btn = document.getElementById('btnDeleteSelected');
                if (btn) {
                    if (checked > 0) {
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                }
            };

            window.deleteSelectedAttachments = function() {
                const checkedBoxes = document.querySelectorAll('.attachment-checkbox:checked');
                const ids = Array.from(checkedBoxes).map(c => c.value);
                
                if (ids.length === 0) return;
                
                Swal.fire({
                    title: 'Delete ' + ids.length + ' attachment(s)?', 
                    text: 'You will not be able to recover these files!',
                    icon: 'warning',
                    showCancelButton: true, 
                    confirmButtonColor: '#dc2626', 
                    confirmButtonText: 'Yes, Delete All'
                }).then(result => {
                    if (result.isConfirmed) {
                        var docIdElement = document.querySelector('input[name="document_id"]');
                        var docId = docIdElement ? docIdElement.value : '{{ $docIdParam ?? "" }}';

                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        Promise.all(ids.map(id => 
                            axios.delete('{{ route("petty-cash-request.attachment.delete") }}', {
                                data: { document_id: docId, attachment_id: id }
                            })
                        )).then(() => {
                            loadTabContent('attachments');
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Attachments have been deleted.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }).catch(err => {
                            Swal.fire('Delete Failed', err.response?.data?.message || 'Unknown error', 'error');
                        });
                     }
                });
            };

            // View Attachment Functions
            window.openAttachmentPreview = function (url, filename) {
                var modal = document.getElementById('attachmentPreviewModal');
                var bodyContainer = document.getElementById('attachmentPreviewBody');
                var title = document.getElementById('attachmentPreviewTitle');

                title.textContent = filename || 'Preview';
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                // Content Type Logic
                var content = '';
                var match = filename.match(/\.([0-9a-z]+)(?:[\?#]|$)/i);
                var ext = match ? match[1].toLowerCase() : '';

                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) {
                    content = '<img src="' + url + '" class="max-w-full max-h-full object-contain mx-auto" alt="Preview">';
                } else if (ext === 'pdf') {
                    content = '<iframe src="' + url + '" class="w-full h-full border-0"></iframe>';
                } else {
                    content = '<iframe src="' + url + '" class="w-full h-full border-0"></iframe>';
                }
                if (bodyContainer) {
                    bodyContainer.innerHTML = content;
                }
            }

            window.closeAttachmentPreview = function () {
                var modal = document.getElementById('attachmentPreviewModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                var bodyContainer = document.getElementById('attachmentPreviewBody');
                if (bodyContainer) {
                    bodyContainer.innerHTML = '';
                }
            }
        </script>
    @endpush

    <!-- Document Action Modal -->
    <div id="documentActionModal"
        class="hidden fixed inset-0 bg-gray-900/30 backdrop-blur-sm overflow-y-auto h-full w-full z-50 items-center justify-center">
        <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Document Action
                </h3>
                <button type="button" onclick="closeDocumentActionModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="mt-4 space-y-3">
                @php
                    $reqStatus = isset($request) ? $request->docstatus : 'DR';
                @endphp

                @if($reqStatus == 'CO')
                    <!-- Complete: Close option only -->
                    <button type="button" onclick="processDocument('CL')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:border-blue-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Close</span>
                        </div>
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                @elseif(in_array($reqStatus, ['DR', 'IP', 'NA']))
                    <!-- Draft/In Progress: Complete and Void options -->
                    <button type="button" onclick="processDocument('CO')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:border-green-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium text-green-700 dark:text-green-300">Complete</span>
                        </div>
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <button type="button" onclick="processDocument('VO')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:border-red-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                </path>
                            </svg>
                            <span class="font-medium text-red-700 dark:text-red-300">Void</span>
                        </div>
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                @else
                    <div class="text-center py-6">
                        <p class="text-gray-500 dark:text-gray-400">No actions available for this document status.</p>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="closeDocumentActionModal()"
                    class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Attachment Preview Modal -->
    <div id="attachmentPreviewModal"
        class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-5xl h-[90vh] flex flex-col relative">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate pr-4" id="attachmentPreviewTitle">
                    Attachment Preview</h3>
                <button onclick="closeAttachmentPreview()"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <span class="sr-only">Close</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <!-- Body -->
            <div class="flex-1 overflow-hidden relative bg-gray-100 dark:bg-gray-900 flex items-center justify-center"
                id="attachmentPreviewBody">
                <!-- Content injected here (iframe/img) -->
            </div>
        </div>
    </div>

@endsection
