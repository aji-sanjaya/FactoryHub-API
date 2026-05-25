@extends('layouts.app')

@section('content')

    @php 
        $customerShipmentConfig = config('idempiere.customer-shipment');
        $isNew = is_null($shipment);
        // Read active tab from URL param, default to 'header'
        $activeTab = in_array(request('tab'), ['header', 'lines', 'attachments']) ? request('tab') : 'header';
        // For new records always force header
        if ($isNew) $activeTab = 'header';

        // Context from Session

        // Calculate Default Org (Minimum ID)
        $defaultOrgId = null;
        if (isset($organizations) && count($organizations) > 0) {
            $sortedOrgs = collect($organizations)->sortBy('id');
            $defaultOrgId = $sortedOrgs->first()->id;
        }

        // Default Values for New Record Logic
        $docNo = $isNew ? '** New **' : $shipment->documentno;
        $status = $isNew ? 'Drafted' : $shipment->status_label;
        $desc = $isNew ? '' : $shipment->description;

        // Calculate Default Warehouse (Min ID)
        $defaultWarehouseId = null;
        if (isset($warehouses) && count($warehouses) > 0) {
            $sortedWh = collect($warehouses)->sortBy('id');
            $defaultWarehouseId = $sortedWh->first()->id;
        }
        $warehouseId = $isNew ? $defaultWarehouseId : $shipment->m_warehouse_id;

        // Current Org ID Selection
        $currentOrgId = $isNew ? $defaultOrgId : $shipment->ad_org_id;
        $movementDate = $isNew ? now()->format('m-d-Y') : \Carbon\Carbon::parse($shipment->movementdate)->format('m-d-Y');

        // Context from Session

        // Document ID Param for Links
        $docIdParam = request('document_id');

        // Read Only Logic
        $isReadOnly = false;
        if (!$isNew && isset($shipment->docstatus)) {
            $isReadOnly = in_array($shipment->docstatus, $customerShipmentConfig['statuses']['read_only']);
        }
    @endphp

    <!-- Header / Breadcrumb -->
    <div>
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('customer-shipment.index') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $isNew ? 'New Customer Shipment' : $docNo }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                        @if(!$isNew)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $status }}
                            </span>
                            <span class="text-gray-300">•</span>
                        @endif
                        <span>{{ $isNew ? 'Create a new customer shipment' : 'Manage shipment details and lines' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <!-- Always Visible Action (Print) -->
                @if(isset($shipment) && in_array($shipment->docstatus, $customerShipmentConfig['statuses']['printable']))
                    <button type="button"
                        onclick="openPrintModal('{{ route('customer-shipment.print', \Illuminate\Support\Facades\Crypt::encryptString($shipment->m_inout_id)) }}', '{{ \Illuminate\Support\Facades\Crypt::encryptString($shipment->m_inout_id) }}')"
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
                            {{ $isNew ? 'Create Customer Shipment' : 'Save Changes' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Card with Tabs -->
        <div
            class="bg-white rounded-2xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 overflow-hidden">

            <!-- Tabs Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 px-6 sm:px-8">
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
                        Shipment Lines
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
                    <a href="#" onclick="switchTab('journals'); return false;" id="nav-journals"
                        class="{{ $activeTab == 'journals' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $isNew ? 'cursor-not-allowed opacity-50 pointer-events-none' : '' }}">
                        <svg class="w-4 h-4 {{ $activeTab == 'journals' ? 'text-brand-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Journals
                    </a>
                </nav>
            </div>

            <!-- Tab Content -->
            <div id="tab-content-wrapper">
                <!-- Header Content -->
                <div id="tab-header" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @include('pages.customer-shipment.partials.tab-header')
                </div>

                <!-- Lines Content -->
                <div id="tab-lines" class="{{ $activeTab == 'lines' ? 'block' : 'hidden' }}">
                    @include('pages.customer-shipment.partials.tab-lines')
                </div>

                <!-- Attachments Content -->
                <div id="tab-attachments" class="{{ $activeTab == 'attachments' ? 'block' : 'hidden' }}">
                    <!-- Content gathered via AJAX -->
                </div>

                <!-- Journals Content -->
                <div id="tab-journals" class="{{ $activeTab == 'journals' ? 'block' : 'hidden' }}">
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
            font-size: 14px;

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
            const shipperDeliveryViaRule = @json($customerShipmentConfig['rules']['shipper_delivery_via']);

            // Initialization Function to be called on ready and after AJAX
            function initScripts() {
                // Init Select2 on All Header Dropdowns (removed pricelist_id and c_tax_id)
                $('#org_id, #warehouse_id, #c_bpartner_id, #c_bpartner_location_id, #bill_bpartner_id, #bill_location_id, #c_doctype_id, #deliveryviarule, #freightcostrule, #m_shipper_id, #a_asset_id, #salesrep_id').select2({
                    width: '100%',
                    placeholder: '- Select -'
                });

                // Delivery Via change → show/hide Shipper & toggle Asset/Driver
                $('#deliveryviarule').off('change.shipperToggle').on('change.shipperToggle', function() {
                    const isShipper = $(this).val() === shipperDeliveryViaRule;
                    const shipperRow = document.getElementById('shipper_row');
                    const assetRow = document.getElementById('asset_row');
                    const driverRow = document.getElementById('driver_row');
                    if (isShipper) {
                        shipperRow.style.display = '';
                        assetRow.style.display = 'none';
                        driverRow.style.display = 'none';
                        $('#a_asset_id').val('').trigger('change');
                        $('#salesrep_id').val('').trigger('change');
                    } else {
                        shipperRow.style.display = 'none';
                        $('#m_shipper_id').val('').trigger('change');
                        assetRow.style.display = '';
                        driverRow.style.display = '';
                    }
                });

                // Customer change → load Customer Locations, auto-fill Invoice To
                $('#c_bpartner_id').off('change.header').on('change.header', function () {
                    const bpartnerId = $(this).val();
                    const locationSelect = $('#c_bpartner_location_id');
                    const billSelect = $('#bill_bpartner_id');
                    const billLocationSelect = $('#bill_location_id');

                    locationSelect.empty().append(new Option('- Select -', '', true, true));
                    billLocationSelect.empty().append(new Option('- Select -', '', true, true));
                    locationSelect.trigger('change');

                    if (!bpartnerId) return;

                    // Auto-fill Invoice To with same customer
                    const selectedText = $(this).find(':selected').text();
                    const existingOpt = billSelect.find('option[value="' + bpartnerId + '"]');
                    if (existingOpt.length) {
                        billSelect.val(bpartnerId).trigger('change');
                    }

                    // Fetch locations
                    locationSelect.prop('disabled', true);
                    axios.get('/customer-shipment/api/bpartner-locations', { params: { bpartner_id: bpartnerId } })
                        .then(res => {
                            res.data.forEach(loc => {
                                locationSelect.append(new Option(loc.text, loc.id, false, false));
                                billLocationSelect.append(new Option(loc.text, loc.id, false, false));
                            });
                            if (res.data.length > 0) {
                                locationSelect.val(res.data[0].id).trigger('change');
                                billLocationSelect.val(res.data[0].id).trigger('change');
                            }
                        })
                        .catch(err => console.warn('Could not load BPartner locations', err))
                        .finally(() => locationSelect.prop('disabled', false));
                });

                // Bill BPartner change → load Bill Locations
                $('#bill_bpartner_id').off('change.bill').on('change.bill', function () {
                    const bpartnerId = $(this).val();
                    const billLocationSelect = $('#bill_location_id');
                    billLocationSelect.empty().append(new Option('- Select -', '', true, true)).trigger('change');

                    if (!bpartnerId) return;

                    axios.get('/customer-shipment/api/bpartner-locations', { params: { bpartner_id: bpartnerId } })
                        .then(res => {
                            res.data.forEach(loc => {
                                billLocationSelect.append(new Option(loc.text, loc.id, false, false));
                            });
                            if (res.data.length > 0) {
                                billLocationSelect.val(res.data[0].id).trigger('change');
                            }
                        })
                        .catch(err => console.warn('Could not load Bill locations', err));
                });

                // Init Product Select2 (Lines variables)
                if ($('#m_product_id').length > 0) {
                    $('#m_product_id').select2({
                        width: '100%',
                        placeholder: 'Search Product...',
                        allowClear: false,
                        ajax: {
                            url: '{{ route("customer-shipment.api.products") }}',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term, // search term
                                    page: params.page || 1
                                };
                            },
                            processResults: function (data, params) {
                                params.page = params.page || 1;

                                return {
                                    results: data.results,
                                    pagination: {
                                        more: data.pagination.more
                                    }
                                };
                            },
                            cache: true
                        }
                    });

                    // Product Change Listener -> Update UoM Display (no price fetch for shipments)
                    $('#m_product_id').off('change').on('change', function () {
                        if (window.isEditingLine) return;

                        const selectedData = $(this).select2('data');
                        if (selectedData && selectedData.length > 0) {
                            const product = selectedData[0];
                            const uomDisplay = product.uom_symbol || product.uom_name || 'Unit';
                            $('#product_uom').text(uomDisplay);

                            // Store UOM precision on qty input for decimal formatting
                            const precision = (product.uom_precision !== undefined && product.uom_precision !== null)
                                ? parseInt(product.uom_precision) : 2;
                            const qtyInput = document.getElementById('line_qty');
                            if (qtyInput) qtyInput.dataset.precision = precision;
                        } else {
                            $('#product_uom').text('Unit');
                            const qtyInput = document.getElementById('line_qty');
                            if (qtyInput) qtyInput.dataset.precision = 2;
                        }
                    });
                }

                // Org Change Listener -> Fetch Warehouses
                $('#org_id').off('change').on('change', function () {
                    const orgId = $(this).val();
                    const warehouseSelect = $('#warehouse_id');

                    // Clear current options
                    warehouseSelect.empty();
                    warehouseSelect.append(new Option('- Select -', '', true, true));

                    if (!orgId) {
                        warehouseSelect.trigger('change');
                        return;
                    }

                    // Disable while loading
                    warehouseSelect.prop('disabled', true);

                    axios.get('{{ route("customer-shipment.api.warehouses") }}', { params: { org_id: orgId } })
                        .then(res => {
                            const data = res.data;
                            let minId = null;

                            // Append options and find min ID
                            data.forEach(wh => {
                                warehouseSelect.append(new Option(wh.text, wh.id, false, false));
                                if (minId === null || Number(wh.id) < Number(minId)) {
                                    minId = wh.id;
                                }
                            });

                            // Select Min ID
                            if (minId) {
                                warehouseSelect.val(minId).trigger('change');
                            } else {
                                warehouseSelect.val('').trigger('change');
                            }
                        })
                        .catch(err => {
                            console.error('Failed to fetch warehouses', err);
                        })
                        .finally(() => {
                            warehouseSelect.prop('disabled', false);
                        });
                });
            }

            $(document).ready(function () {
                initScripts();

                // Auto-load the active tab from URL on page load
                const initialTab = '{{ $activeTab }}';
                const isNew = {{ $isNew ? 'true' : 'false' }};
                if (!isNew && initialTab !== 'header') {
                    // Trigger AJAX load for non-header tab without changing URL
                    loadTabContent(initialTab);
                }
            });

            function switchTab(tabName) {
                // Valid tabs
                const tabs = ['header', 'lines', 'attachments', 'journals'];
                if (!tabs.includes(tabName)) return;


                // Helper to toggle Nav Styles
                const toggleNav = (id, active) => {
                    const el = $('#' + id);
                    const icon = el.find('svg');
                    if (active) {
                        el.removeClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300')
                            .addClass('border-brand-500 text-brand-600');
                        icon.removeClass('text-gray-400 group-hover:text-gray-500').addClass('text-brand-500');
                    } else {
                        el.removeClass('border-brand-500 text-brand-600')
                            .addClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300');
                        icon.removeClass('text-brand-500').addClass('text-gray-400 group-hover:text-gray-500');
                    }
                };

                // Hide all tabs and navs
                tabs.forEach(t => {
                    $(`#tab-${t}`).removeClass('block').addClass('hidden');
                    toggleNav(`nav-${t}`, false);
                });

                // Show requested tab
                $(`#tab-${tabName}`).removeClass('hidden').addClass('block');
                toggleNav(`nav-${tabName}`, true);

                // Handle Header Actions visibility (Submit button etc)
                if (tabName === 'header') {
                    $('#header-actions').removeClass('hidden').addClass('block');
                } else {
                    $('#header-actions').removeClass('block').addClass('hidden');
                }

                // Load content for all tabs via AJAX (non-new records)
                const isNew = {{ $isNew ? 'true' : 'false' }};
                if (!isNew) {
                    loadTabContent(tabName);
                } else {
                    // For new records, update URL with tab param only
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabName);
                    window.history.replaceState(null, '', url.toString());
                }

                // Update global activeTab var for other scripts
                window.activeTab = tabName;

            }

            function loadTabContent(tabName, params = {}) {
                const container = $('#tab-' + tabName);

                // Show Loading Indicator
                container.html(`
                    <div class="flex flex-col justify-center items-center py-24">
                         <div class="rounded-full bg-brand-50 p-4 mb-4">
                            <svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                         </div>
                        <span class="text-gray-500 font-medium dark:text-gray-400">Loading details...</span>
                    </div>
                `);

                // Fetch Content
                const url = new URL(window.location.href);
                url.searchParams.set('ajax_tab', tabName);

                // Merge new params
                Object.keys(params).forEach(key => {
                    if (params[key] === null || params[key] === '') {
                        // Optional: delete empty params if desired, but setting them might be safer to clear prev state
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
                        const visibleUrl = new URL(url);
                        visibleUrl.searchParams.delete('ajax_tab');
                        visibleUrl.searchParams.set('tab', tabName);
                        // We should also update the visible q_lines/per_page in URL so refresh works
                        Object.keys(params).forEach(key => {
                            visibleUrl.searchParams.set(key, params[key]);
                        });
                        window.history.replaceState(null, '', visibleUrl.toString());
                    })
                    .catch(err => {
                        console.error(err);
                        container.html(`
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="rounded-full bg-red-100 p-3 mb-3">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h3 class="text-gray-900 font-medium">Failed to load content</h3>
                                <button onclick="loadTabContent('${tabName}')" class="mt-4 px-4 py-2 text-sm font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100">Try Again</button>
                            </div>
                        `);
                    });
            }

            function handleSearchLines(query) {
                loadTabContent('lines', { q_lines: query, page: 1 });
            }

            function handlePerPageLines(perPage) {
                const query = document.getElementById('q_lines') ? document.getElementById('q_lines').value : '';
                loadTabContent('lines', { per_page: perPage, q_lines: query, page: 1 });
            }

            function showCreateLineForm() {
                // Reset form for new line
                document.getElementById('line_id').value = '';
                document.getElementById('c_orderline_id').value = '';
                document.getElementById('createLineForm').reset();
                $('#m_product_id').val(null).trigger('change').prop('disabled', false);
                $('#product_uom').text('Unit');

                // Hide linked SO info
                $('#linkedSOInfo').addClass('hidden');

                // Update form title
                const formTitle = document.querySelector('#lineFormPanel h3');
                if (formTitle) {
                    formTitle.textContent = 'Create Line Item';
                }

                // Hide delete button (create mode)
                $('#lineDeleteBtnWrapper').addClass('hidden');

                $('#lines-list-container').addClass('hidden');
                $('#lines-create-form').removeClass('hidden');
            }

            function hideCreateLineForm() {
                $('#lines-create-form').addClass('hidden');
                $('#lineDeleteBtnWrapper').addClass('hidden');
                $('#lines-list-container').removeClass('hidden');
            }

            function deleteCurrentLine() {
                const lineId = document.getElementById('line_id').value;
                if (!lineId) return;
                hideCreateLineForm();
                deleteLine(lineId);
            }

            window.toggleSelectAllLines = function (checkbox) {
                const lineCheckboxes = document.querySelectorAll('.line-checkbox');
                lineCheckboxes.forEach(cb => {
                    cb.checked = checkbox.checked;
                });
                updateDeleteLinesButtonState();
            }

            window.updateDeleteLinesButtonState = function () {
                const checked = document.querySelectorAll('.line-checkbox:checked');
                const deleteBtn = document.getElementById('deleteSelectedBtn');
                const deleteText = document.getElementById('deleteSelectedText');

                if (displayDeleteButton(checked.length, deleteBtn, deleteText)) {
                    // Logic handled in helper or inline
                } else if (checked.length > 0) {
                    if (deleteBtn) deleteBtn.style.display = 'inline-flex';
                    if (deleteText) deleteText.textContent = `Delete Selected (${checked.length})`;
                } else {
                    if (deleteBtn) deleteBtn.style.display = 'none';
                }
            }

            function displayDeleteButton(count, btn, text) {
                if (count > 0) {
                    if (btn) btn.style.display = 'inline-flex';
                    if (text) text.textContent = `Delete Selected (${count})`;
                    return true;
                } else {
                    if (btn) btn.style.display = 'none';
                    return true;
                }
            }

            // Update delete button when individual checkboxes are clicked
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('line-checkbox')) {
                    updateDeleteLinesButtonState();

                    // Update select all checkbox
                    const allCheckboxes = document.querySelectorAll('.line-checkbox');
                    const checkedCheckboxes = document.querySelectorAll('.line-checkbox:checked');
                    const selectAllCheckbox = document.getElementById('selectAll');

                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
                    }
                }
            });

            function deleteLine(lineId) {
                Swal.fire({
                    title: 'Delete Line?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDelete([lineId]);
                    }
                });
            }

            function deleteSelectedLines() {
                const checked = document.querySelectorAll('.line-checkbox:checked');
                const lineIds = Array.from(checked).map(cb => cb.value);

                if (lineIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one line to delete',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                Swal.fire({
                    title: `Delete ${lineIds.length} Line(s)?`,
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete them!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDelete(lineIds);
                    }
                });
            }

            function performDelete(lineIds) {
                const data = {
                    line_ids: lineIds,
                    document_id: '{{ $docIdParam }}'
                };

                axios.delete('{{ route("customer-shipment.line.delete") }}', { data: data })
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.data.message || 'Line(s) deleted successfully',
                            confirmButtonColor: '#4f46e5',
                            timer: 2000
                        });

                        // Reload lines tab
                        loadTabContent('lines');
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error deleting line(s): ' + (err.response?.data?.message || err.message),
                            confirmButtonColor: '#4f46e5'
                        });
                    });
            }


            function formatNumber(input) {
                // Remove existing commas to get raw number
                let value = input.value.replace(/,/g, '');

                // Allow only numbers and one decimal point
                if (/[^0-9.]/.test(value)) {
                    value = value.replace(/[^0-9.]/g, '');
                }

                // Handle multiple decimal points
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }

                if (!value) {
                    input.value = '';
                    return;
                }

                // Split into integer and decimal parts
                const sections = value.split('.');
                const integerPart = sections[0];
                const decimalPart = sections.length > 1 ? '.' + sections[1] : '';

                // Add commas to integer part
                const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");

                input.value = formattedInteger + decimalPart;
            }

            function editLine(lineId, productText, productId, qty, description, lineNumber, uomName, uomPrecision, soDocumentNo, cOrderlineId) {
                // Show the form
                showCreateLineForm();

                // Set edit flag to prevent auto-fetch
                window.isEditingLine = true;

                // Populate line_id for update mode
                document.getElementById('line_id').value = lineId;

                // Populate product - create option and select it
                const productSelect = $('#m_product_id');
                if (productSelect.length) {
                    const option = new Option(productText, productId, true, true);
                    productSelect.append(option).trigger('change');
                }

                // Lock product field in edit mode
                productSelect.prop('disabled', true);

                // Reset flag
                window.isEditingLine = false;

                // Set c_orderline_id hidden field
                document.getElementById('c_orderline_id').value = cOrderlineId || '';

                // Show Linked SO info if line is linked
                if (soDocumentNo && cOrderlineId) {
                    $('#linkedSOText').text('Linked to SO: ' + soDocumentNo + ' \u2014 ' + productText);
                    $('#linkedSOInfo').removeClass('hidden');
                } else {
                    $('#linkedSOInfo').addClass('hidden');
                }

                // Set UOM manually
                if (uomName) {
                    $('#product_uom').text(uomName);
                }

                // Set UOM precision on qty input
                const decimals = (uomPrecision !== undefined && uomPrecision !== null) ? parseInt(uomPrecision) : 2;
                const qtyInput = document.getElementById('line_qty');
                if (qtyInput) qtyInput.dataset.precision = decimals;

                // Populate other fields
                document.getElementById('line_qty').value = qty.toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });

                document.getElementById('line_description').value = description || '';

                // Show delete button (edit mode)
                $('#lineDeleteBtnWrapper').removeClass('hidden');

                // Update form title
                const formTitle = document.querySelector('#lineFormPanel h3');
                if (formTitle) {
                    formTitle.textContent = 'Edit Line Item';
                }
            }

            function saveLine() {
                const form = document.getElementById('createLineForm');
                if (!form) return;

                const formData = new FormData(form);
                const rawData = Object.fromEntries(formData.entries());

                // Read product_id from Select2 (may be disabled, so FormData won't include it)
                const productId = $('#m_product_id').val();

                // Clean numeric values (remove commas)
                const data = {
                    ...rawData,
                    m_product_id: productId || rawData.m_product_id,
                    qty: rawData.qty ? rawData.qty.replace(/,/g, '') : '',
                    c_orderline_id: rawData.c_orderline_id || ''
                };

                if (!data.m_product_id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Product is required',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }
                if (!data.qty) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Quantity is required',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                // Show loading state on button
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    var originalText = btn.innerHTML;
                    btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';
                    btn.disabled = true;
                }

                // Determine if this is create or update
                const lineId = data.line_id;
                const url = lineId ? '{{ route("customer-shipment.line.update") }}' : '{{ route("customer-shipment.line.store") }}';
                const method = lineId ? 'put' : 'post';

                axios[method](url, data)
                    .then(res => {
                        // Reload Lines Tab
                        loadTabContent('lines');
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error saving line: ' + (err.response?.data?.message || err.message),
                            confirmButtonColor: '#4f46e5'
                        });
                        if (btn) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    });
            }

            window.isHeaderSubmitting = false;

            function submitHeader() {
                if (window.isHeaderSubmitting) return;

                const orgId = $('#org_id').val();
                const warehouseId = $('#warehouse_id').val();
                const bpartnerId = $('#c_bpartner_id').val();
                const bpartnerLocationId = $('#c_bpartner_location_id').val();
                const billBpartnerId = $('#bill_bpartner_id').val();
                const billLocationId = $('#bill_location_id').val();
                const description = document.getElementById('description') ? document.getElementById('description').value : '';
                const movementDate = document.getElementById('movement_date') ? document.getElementById('movement_date').value : '';
                const shipmentReference = document.getElementById('shipment_reference') ? document.getElementById('shipment_reference').value : '';

                const data = {
                    description: description,
                    movement_date: movementDate,
                    warehouse_id: warehouseId,
                    org_id: orgId,
                    c_bpartner_id: bpartnerId,
                    c_bpartner_location_id: bpartnerLocationId,
                    bill_bpartner_id: billBpartnerId,
                    bill_location_id: billLocationId,
                    shipment_reference: shipmentReference,
                    c_doctype_id: $('#c_doctype_id').val(),
                    deliveryviarule: $('#deliveryviarule').val(),
                    freightcostrule: $('#freightcostrule').val(),
                    m_shipper_id: $('#deliveryviarule').val() === shipperDeliveryViaRule ? ($('#m_shipper_id').val() || null) : null,
                    a_asset_id: $('#a_asset_id').val() || null,
                    salesrep_id: $('#salesrep_id').val() || null,
                };

                if (!data.movement_date) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Movement Date is mandatory',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }
                if (!data.c_bpartner_id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Customer is mandatory',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                // Lock submission
                window.isHeaderSubmitting = true;

                const isNew = {{ $isNew ? 'true' : 'false' }};
                const reqId = {{ isset($shipment) ? $shipment->m_inout_id : 'null' }};

                // Show loading
                Swal.fire({
                    title: isNew ? 'Creating...' : 'Saving...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                if (isNew) {
                    axios.post('{{ route("customer-shipment.store") }}', data)
                        .then(res => {
                            Swal.close();
                            if (res.data && res.data.data && res.data.data.encrypted_id) {
                                window.location.href = "{{ route('customer-shipment.index') }}?document_id=" + res.data.data.encrypted_id;
                            } else {
                                window.location.reload();
                            }
                            // No need to unlock main flag as we are redirecting/reloading
                        })
                        .catch(err => {
                            window.isHeaderSubmitting = false; // Unlock on error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error Creating',
                                text: err.response?.data?.message || err.message,
                                confirmButtonColor: '#4f46e5'
                            });
                        });
                } else {
                    let updateUrl = '{{ route("customer-shipment.update", "ID_PLACEHOLDER") }}'.replace('ID_PLACEHOLDER', reqId);
                    axios.put(updateUrl, data)
                        .then(res => {
                            window.isHeaderSubmitting = false; // Unlock on success
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Header updated successfully',
                                confirmButtonColor: '#4f46e5',
                                timer: 2000
                            });
                        })
                        .catch(err => {
                            window.isHeaderSubmitting = false; // Unlock on error
                            Swal.fire({
                                icon: 'error',
                                title: 'Error Updating',
                                text: err.response?.data?.message || err.message,
                                confirmButtonColor: '#4f46e5'
                            });
                        });
                }
            }

            // Document Action Modal Functions
            function openDocumentActionModal() {
                const modal = document.getElementById('documentActionModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            }

            function closeDocumentActionModal() {
                const modal = document.getElementById('documentActionModal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            }

            function executeDocumentAction(action) {
                closeDocumentActionModal();

                let actionText = '';
                let confirmText = '';

                switch (action) {
                    case 'CO':
                        actionText = 'Complete';
                        confirmText = 'Are you sure you want to complete this document?';
                        break;
                    case 'RE':
                        actionText = 'Re-Active';
                        confirmText = 'Are you sure you want to re-activate this document?';
                        break;
                }

                Swal.fire({
                    title: `${actionText} Document?`,
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: `Yes, ${actionText.toLowerCase()} it!`,
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

                const data = {
                    document_id: '{{ $docIdParam }}',
                    doc_action: action
                };

                axios.post('{{ route("customer-shipment.process") }}', data)
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: res.data.message || `Document ${actionText.toLowerCase()}d successfully`,
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            // Reload page to Header Tab
                            let redirectUrl = "{{ route('customer-shipment.index') }}?document_id={{ $docIdParam }}&tab=header";
                            if (res.data.new_document_id) {
                                redirectUrl = "{{ route('customer-shipment.index') }}?document_id=" + res.data.new_document_id + "&tab=header";
                            }
                            window.location.href = redirectUrl;
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

            function deleteShipment() {
                Swal.fire({
                    title: 'Delete Customer Shipment?',
                    text: "This action cannot be undone! All lines will also be deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDeleteShipment();
                    }
                });
            }

            function performDeleteShipment() {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const data = {
                    document_id: '{{ $docIdParam }}'
                };

                axios.delete('{{ route("customer-shipment.delete") }}', { data: data })
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.data.message || 'Customer Shipment deleted successfully',
                            confirmButtonColor: '#4f46e5',
                            timer: 2000
                        }).then(() => {
                            // Redirect to list
                            window.location.href = '{{ route("customer-shipment.index") }}';
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error deleting customer shipment: ' + (err.response?.data?.message || err.message),
                            confirmButtonColor: '#4f46e5'
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
                // ... (existing code, assumes matched by context if not replaced) ...
                const docIdElement = document.querySelector('input[name="document_id"]');
                const docId = docIdElement ? docIdElement.value : '{{ $docIdParam ?? "" }}';

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

                const formData = new FormData();
                formData.append('document_id', docId);
                formData.append('file', file);

                axios.post('{{ route("customer-shipment.attachment.upload") }}', formData, {
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

            // Bulk Delete Functions
            window.toggleSelectAll = function (source) {
                const checkboxes = document.querySelectorAll('.attachment-checkbox');
                checkboxes.forEach(cb => cb.checked = source.checked);
                updateDeleteButtonState();
            }

            window.updateDeleteButtonState = function () {
                const checkedCount = document.querySelectorAll('.attachment-checkbox:checked').length;
                const btn = document.getElementById('btnDeleteSelected');
                if (checkedCount > 0) {
                    btn.classList.remove('hidden');
                    btn.textContent = `Delete Selected (${checkedCount})`;
                } else {
                    btn.classList.add('hidden');
                }
            }

            window.deleteSelectedAttachments = function () {
                const checked = document.querySelectorAll('.attachment-checkbox:checked');
                const ids = Array.from(checked).map(cb => cb.value);

                if (ids.length === 0) return;

                Swal.fire({
                    title: 'Delete ' + ids.length + ' attachments?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const docIdElement = document.querySelector('input[name="document_id"]');
                        const docId = docIdElement ? docIdElement.value : '{{ $docIdParam ?? "" }}';

                        Swal.fire({
                            title: 'Deleting...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        let completed = 0;
                        ids.forEach(attId => {
                            axios.delete('{{ route("customer-shipment.attachment.delete") }}', {
                                data: { document_id: docId, attachment_id: attId }
                            }).then(() => {
                                completed++;
                                if (completed === ids.length) {
                                    loadTabContent('attachments');
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: completed + ' attachments deleted.',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                }
                            });
                        });
                    }
                })
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
                        const docIdElement = document.querySelector('input[name="document_id"]');
                        const docId = docIdElement ? docIdElement.value : '{{ $docIdParam ?? "" }}';

                        // Show Loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        axios.delete('{{ route("customer-shipment.attachment.delete") }}', {
                            data: {
                                document_id: docId,
                                attachment_id: attId
                            }
                        })
                            .then(res => {
                                loadTabContent('attachments');

                                // Show Success Modal (Not Toast)
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

            // View Attachment Functions
            window.openAttachmentPreview = function (url, filename) {
                const modal = document.getElementById('attachmentPreviewModal');
                const bodyContainer = document.getElementById('attachmentPreviewBody');
                const title = document.getElementById('attachmentPreviewTitle');

                title.textContent = filename || 'Preview';
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                // Content Type Logic
                let content = '';
                // Simple extension check
                const match = filename.match(/\.([0-9a-z]+)(?:[\?#]|$)/i);
                const ext = match ? match[1].toLowerCase() : '';

                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) {
                    content = `<img src="${url}" class="max-w-full max-h-full object-contain mx-auto" alt="Preview">`;
                } else if (ext === 'pdf') {
                    content = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
                } else {
                    // Generic iframe or download link
                    content = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
                }
                bodyContainer.innerHTML = content;
            }

            window.closeAttachmentPreview = function () {
                const modal = document.getElementById('attachmentPreviewModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.getElementById('attachmentPreviewBody').innerHTML = '';
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
                    $reqStatus = isset($shipment) ? $shipment->docstatus : 'DR';
                @endphp

                @if($reqStatus == 'CO')
                    <!-- Re-Active Action for Completed Documents -->
                    <button type="button" onclick="executeDocumentAction('RE')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg transition-colors dark:bg-orange-900/20 dark:hover:bg-orange-900/30 dark:border-orange-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="font-medium text-orange-700 dark:text-orange-300">Re-Active</span>
                        </div>
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                @elseif(in_array($reqStatus, ['DR', 'IP']))
                    <!-- Complete Action for Draft/In Progress Documents -->
                    <button type="button" onclick="executeDocumentAction('CO')"
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

    <!-- Print Modal -->
    <div id="printModal" class="hidden fixed inset-0 z-50 overflow-y-auto" ariaby="modal-title" role="dialog"
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
                            <label for="printStyleSelect" class="text-sm font-medium text-gray-700 dark:text-gray-200"></label>
                            <select id="printStyleSelect" onchange="changePrintStyle(this.value)" class="text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white py-1.5 px-3">
                                <option value="style1" selected>Style 1</option>
                                <option value="style2">Style 2</option>
                                <option value="style3">Style 3</option>
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

@endsection

@push('scripts')
    <script>
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
    </script>
@endpush
