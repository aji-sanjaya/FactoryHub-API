@extends('layouts.app')

@section('content')

    @php 
        $purchaseOrderConfig = $purchaseOrderConfig ?? config('idempiere.create-po');
        $isNew = is_null($order);
        // Active tab from controller or default
        $activeTab = $activeTab ?? 'header';

        // Context from Session

        // Calculate Default Org (Minimum ID)
        $defaultOrgId = null;
        if (isset($organizations) && count($organizations) > 0) {
            $sortedOrgs = collect($organizations)->sortBy('id');
            $defaultOrgId = $sortedOrgs->first()->id;
        }

        // Default Values for New Record Logic
        $docNo = $isNew ? '** New **' : $order->documentno;
        // $status is already passed from controller
        $desc = $isNew ? '' : $order->description;

        // Calculate Default Warehouse (Min ID)
        $defaultWarehouseId = null;
        if (isset($warehouses) && count($warehouses) > 0) {
            $sortedWh = collect($warehouses)->sortBy('id');
            $defaultWarehouseId = $sortedWh->first()->id;
        }
        $warehouseId = $isNew ? $defaultWarehouseId : $order->m_warehouse_id;

        // Use docTypeId from controller (already set to 1000048 for new docs)
        // No need to recalculate here

        // Default Payment Term ID
        $paymentTermId = $isNew ? $purchaseOrderConfig['defaults']['payment_term_id'] : ($order->c_paymentterm_id ?? null);

        // Current Org ID Selection
        $currentOrgId = $isNew ? $defaultOrgId : $order->ad_org_id;
        $dateOrdered = $isNew ? now()->format('Y-m-d') : \Carbon\Carbon::parse($order->dateordered)->format('Y-m-d');

        // Context from Session

        // Document ID Param for Links
        $docIdParam = request('document_id');

        // Read Only Logic
        $isReadOnly = false;
        if (!$isNew && isset($order->docstatus)) {
            $isReadOnly = in_array($order->docstatus, $purchaseOrderConfig['statuses']['read_only'], true);
        }

        $headerBadgeClasses = $purchaseOrderConfig['statuses']['header_badge_classes'] ?? [];
        $headerStatusColor = $headerBadgeClasses[$order->docstatus ?? 'DR'] ?? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        $printableStatuses = $purchaseOrderConfig['statuses']['printable'] ?? [];
        $workflowConfig = $purchaseOrderConfig['workflow'] ?? [];
        $reactivateStatuses = $workflowConfig['reactivate_from'] ?? [];
        $completeVoidStatuses = $workflowConfig['complete_void_from'] ?? [];
        $standardBlockedStatuses = $workflowConfig['standard_blocked'] ?? [];
        $completeAction = $workflowConfig['complete_action'] ?? 'CO';
        $prepareAction = $workflowConfig['prepare_action'] ?? 'PR';
        $voidAction = $workflowConfig['void_action'] ?? 'VO';
        $reactivateAction = $workflowConfig['reactivate_action'] ?? 'RE';
        $workflowActionLabels = $workflowConfig['action_labels'] ?? [];
        $workflowConfirmationMessages = $workflowConfig['confirmation_messages'] ?? [];
    @endphp

    <!-- Header / Breadcrumb -->
    <div>
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('purchase-order.index') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $isNew ? 'New Purchase Order' : $docNo }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                        @if(!$isNew)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $headerStatusColor }}">
                                {{ $status }}
                            </span>
                            <span class="text-gray-300">•</span>
                        @endif
                        <span>{{ $isNew ? 'Create a new purchase order' : 'Manage purchase order details and lines' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <!-- Always Visible Action (Print) -->
                @if(isset($order) && in_array($order->docstatus, $printableStatuses, true))
                    <button type="button"
                        onclick="openPrintModal('{{ route('purchase-order.print', \Illuminate\Support\Facades\Crypt::encryptString($order->c_order_id)) }}')"
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
                            {{ $isNew ? 'Create Order' : 'Save Changes' }}
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
                    @include('pages.purchase-order.partials.tab-header')
                </div>

                <!-- Lines Content -->
                <div id="tab-lines" class="{{ $activeTab == 'lines' ? 'block' : 'hidden' }}">
                    @include('pages.purchase-order.partials.tab-lines')
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
            font-size: 14px;

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
            // Initialization Function to be called on ready a    nd after AJAX
            function initScripts() {
                $('#c_bpartner_id, #org_id, #warehouse_id, #pricelist_id, #priority_rule, #c_paymentterm_id, #adw_ad_user_checked_id, #adw_ad_user_approved_id, #doc_type_id, #c_tax_id').select2({
                    width: '100%',
                    placeholder: '- Select -'
                });

                $('#c_project_id').select2({
                    width: '100%',
                    placeholder: '- Select -',
                    allowClear: true
                }); 

                // Force select default Doc Type if set (with delay to ensure Select2 is ready)
                const defaultDocType = "{{ $docTypeId ?? '' }}";
                const isNewDoc = {{ $isNew ? 'true' : 'false' }};
                
                console.log('Doc Type Debug:', {
                    defaultDocType: defaultDocType,
                    isNewDoc: isNewDoc
                });
                
                if ($('#doc_type_id').length && defaultDocType) {
                    setTimeout(function() {
                        $('#doc_type_id').val(defaultDocType).trigger('change');
                        console.log('Set doc_type_id to:', defaultDocType);
                        console.log('Current value:', $('#doc_type_id').val());
                    }, 150);
                } 

                // Init Product Select2 (Lines variables)
                if ($('#m_product_id').length > 0) {
                    $('#m_product_id').select2({
                        width: '100%',
                        placeholder: 'Search Product...',
                        allowClear: false,
                        ajax: {
                            url: '{{ route("purchase-order.api.products") }}',
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

                    // Product Change Listener -> Update UoM Display and Fetch Price
                    $('#m_product_id').off('change').on('change', function () {
                        if (window.isEditingLine) return;


                        const selectedData = $(this).select2('data');
                        if (selectedData && selectedData.length > 0) {
                            const product = selectedData[0];
                            const uomDisplay = product.uom_symbol || product.uom_name || 'Unit';
                            $('#product_uom').text(uomDisplay);

                            // Fetch price from price list
                            const productId = product.id;
                            const priceListId = $('#pricelist_id').val() || $('#hidden_pricelist_id').val();

                            if (productId && priceListId) {
                                axios.get('{{ route("purchase-order.api.product-price") }}', {
                                    params: {
                                        product_id: productId,
                                        pricelist_id: priceListId
                                    }
                                })
                                    .then(res => {
                                        if (res.data && res.data.price !== undefined) {
                                            const priceInput = document.getElementById('line_price');
                                            if (priceInput) {
                                                const price = parseFloat(res.data.price) || 0;
                                                priceInput.value = price.toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                });
                                                // Update total
                                                if (typeof calculateLineTotal === 'function') {
                                                    calculateLineTotal();
                                                }
                                            }
                                        }
                                    })
                                    .catch(err => {
                                        console.error('Failed to fetch price', err);
                                        const priceInput = document.getElementById('line_price');
                                        if (priceInput) {
                                            priceInput.value = '0.00';
                                        }
                                    });
                            }
                        } else {
                            $('#product_uom').text('Unit');
                            const priceInput = document.getElementById('line_price');
                            if (priceInput) {
                                priceInput.value = '';
                            }
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

                    axios.get('{{ route("purchase-order.api.warehouses") }}', { params: { org_id: orgId } })
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

            // Function to lock c_tax_id field if order has lines
            function lockTaxIfHasLines() {
                const isNew = {{ $isNew ? 'true' : 'false' }};
                if (isNew) return; // For new orders, tax is always editable

                // Make AJAX call to check current order lines count
                const currentUrl = new URL(window.location.href);
                const orderId = currentUrl.pathname.split('/').pop();
                
                // Simple check: count lines via AJAX request
                axios.get(currentUrl.toString(), {
                    params: {
                        ajax_tab: 'lines',
                        check_lines_only: 'true',
                        _t: new Date().getTime()
                    }
                })
                .then(res => {
                    // Count tbody tr elements (excluding empty state row)
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = res.data;
                    const lineRows = tempDiv.querySelectorAll('tbody tr.border-t');
                    const orderLinesCount = lineRows.length;
                    
                    const taxSelect = $('#c_tax_id');
                    if (!taxSelect.length) return; // Field not found

                    if (orderLinesCount > 0) {
                        // Lock the tax field
                        taxSelect.prop('disabled', true);
                        taxSelect.addClass('bg-gray-50 cursor-not-allowed dark:bg-gray-700/50');
                        taxSelect.removeClass('focus:border-brand-500 focus:ring-brand-500');
                        
                        // Add a visual indicator if not already present
                        if (!$('#tax-locked-indicator').length) {
                            // Find Select2 container and add warning after it
                            const select2Container = taxSelect.next('.select2-container');
                            if (select2Container.length) {
                                select2Container.after(`
                                    <p id="tax-locked-indicator" class="text-xs text-amber-600 dark:text-amber-400 mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Tax cannot be changed when order lines exist
                                    </p>
                                `);
                            } else {
                                // Fallback: if Select2 not initialized, add after select element
                                taxSelect.after(`
                                    <p id="tax-locked-indicator" class="text-xs text-amber-600 dark:text-amber-400 mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Tax cannot be changed when order lines exist
                                    </p>
                                `);
                            }
                        }
                    } else {
                        // Unlock the tax field
                        taxSelect.prop('disabled', false);
                        taxSelect.removeClass('bg-gray-50 cursor-not-allowed dark:bg-gray-700/50');
                        taxSelect.addClass('focus:border-brand-500 focus:ring-brand-500');
                        
                        // Remove the indicator
                        $('#tax-locked-indicator').remove();
                    }
                })
                .catch(err => {
                    console.error('Failed to check order lines count:', err);
                });
            }

            $(document).ready(function () {
                initScripts();

                // Lock tax field if necessary
                lockTaxIfHasLines();

                // Initial Load for Active Tab (if not header)
                const initialTab = '{{ $activeTab }}';
                const isNew = {{ $isNew ? 'true' : 'false' }};

                if (!isNew && initialTab !== 'header') {
                    // Force load content for the active tab (especially for attachments which is AJAX only)
                    loadTabContent(initialTab);
                    window.activeTab = initialTab;
                }
            });

            function switchTab(tabName) {
                // Valid tabs
                const tabs = ['header', 'lines', 'attachments'];
                if (!tabs.includes(tabName)) return;

                // Check if already active
                if (!$(`#tab-${tabName}`).hasClass('hidden')) return;

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
                    // Lock tax field if necessary when switching to header tab
                    lockTaxIfHasLines();
                } else {
                    $('#header-actions').removeClass('block').addClass('hidden');
                }

                // Load content for non-header tabs if not new
                const isNew = {{ $isNew ? 'true' : 'false' }};
                if (!isNew && tabName !== 'header') {
                    // Determine if we should load (simple check or always load)
                    // The previous logic always loaded 'lines' on switch.
                    // We'll keep it for 'lines' and 'attachments'.
                    loadTabContent(tabName);
                }

                // Update global activeTab var for other scripts
                window.activeTab = tabName;

                // Update Browser URL silently for all tabs (header included)
                const visibleUrl = new URL(window.location.href);
                visibleUrl.searchParams.set('tab', tabName);
                window.history.replaceState(null, '', visibleUrl.toString());
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
                url.searchParams.set('_t', new Date().getTime()); // Prevent Cache

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
                document.getElementById('m_requisitionline_id').value = ''; // Reset Requisition ID
                document.getElementById('createLineForm').reset();
                $('#m_product_id').val(null).trigger('change');
                $('#product_uom').text('Unit');

                // Reset submit button state (in case previous save left it in loading state)
                const submitBtn = document.querySelector('#createLineForm button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Save Line Item`;
                }

                // Hide Requisition Info
                const reqInfoDiv = document.getElementById('requisition_info_container');
                if (reqInfoDiv) reqInfoDiv.classList.add('hidden');

                // Show Remove Link button (visible in create mode)
                const removeLinkBtn = document.getElementById('req_remove_link_btn');
                if (removeLinkBtn) removeLinkBtn.classList.remove('hidden');

                // Hide Delete button (only visible in edit mode)
                const deleteBtn = document.getElementById('form_delete_line_btn');
                if (deleteBtn) deleteBtn.classList.add('hidden');

                // Update form title
                const formTitle = document.querySelector('#lineFormPanel h3');
                if (formTitle) {
                    formTitle.textContent = 'Create Line Item';
                }

                // Populate Tax from Header
                // Handle both scenarios: select (no lines) or hidden input (lines exist)
                const headerTaxElement = document.getElementById('c_tax_id');
                const headerTaxDisplay = document.getElementById('c_tax_id_display');
                const lineTaxDisplay = document.getElementById('c_tax_line_id');
                const lineTaxValue = document.getElementById('c_tax_line_value');
                
                if (lineTaxDisplay && lineTaxValue) {
                    // Check if header tax is a select or hidden input
                    if (headerTaxElement && headerTaxElement.tagName === 'SELECT') {
                        // Tax is still editable (no lines exist)
                        const selectedOption = headerTaxElement.options[headerTaxElement.selectedIndex];
                        lineTaxValue.value = headerTaxElement.value;
                        lineTaxDisplay.value = selectedOption ? selectedOption.text : '';
                    } else if (headerTaxElement && headerTaxElement.type === 'hidden' && headerTaxDisplay) {
                        // Tax is readonly (lines exist)
                        lineTaxValue.value = headerTaxElement.value;
                        lineTaxDisplay.value = headerTaxDisplay.value;
                    }
                }

                $('#lines-list-container').addClass('hidden');
                $('#lines-create-form').removeClass('hidden');

                // Reset withholding fields
                const whCheckbox = document.getElementById('line_is_withholding');
                const whRate = document.getElementById('line_withholding_rate');
                const whAmt = document.getElementById('line_withholding_amount');
                if (whCheckbox) whCheckbox.checked = false;
                if (whRate) { whRate.disabled = true; whRate.value = '2'; }
                if (whAmt) whAmt.value = '0.00';
            }

            function hideCreateLineForm() {
                $('#lines-create-form').addClass('hidden');
                $('#lines-list-container').removeClass('hidden');
                // Also clear the queue so cancel properly aborts sequential processing
                reqLineQueue = [];
                loadTabContent('lines');
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

                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                axios.delete('{{ route("purchase-order.line.delete") }}', { data: data })
                    .then(res => {
                        // Update Totals if returned
                        if (res.data.grandtotal) {
                             const taxEl = document.getElementById('txt_tax_amount');
                             const grandEl = document.getElementById('txt_grand_total');
                             if (taxEl) taxEl.value = res.data.tax_amount;
                             if (grandEl) grandEl.value = res.data.grandtotal;
                        }

                        // If form panel is open (edit mode), close it first
                        const createForm = document.getElementById('lines-create-form');
                        if (createForm && !createForm.classList.contains('hidden')) {
                            hideCreateLineForm();
                        }

                        // Reload lines tab then show success
                        loadTabContent('lines');
                        
                        // Update tax lock status after deletion
                        setTimeout(() => lockTaxIfHasLines(), 500);

                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.data.message || 'Line(s) deleted successfully',
                            confirmButtonColor: '#4f46e5',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: err.response?.data?.message || err.message,
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

            function editLine(lineId, productText, productId, qty, price, description, lineNumber, uomName, requisitionLineId, requisitionDocNo, isWithholding, withholdingRate, withholdingAmount) {
                // Show the form (also resets requisition state)
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

                // Reset flag
                window.isEditingLine = false;

                // Set UOM manually
                if (uomName) {
                    $('#product_uom').text(uomName);
                }

                // Populate other fields
                document.getElementById('line_qty').value = parseFloat(qty).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                document.getElementById('line_price').value = parseFloat(price).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                document.getElementById('line_description').value = description || '';

                // Populate withholding fields
                const whCheckbox = document.getElementById('line_is_withholding');
                const whRate = document.getElementById('line_withholding_rate');
                const whAmt = document.getElementById('line_withholding_amount');
                if (whCheckbox) {
                    whCheckbox.checked = !!isWithholding;
                    if (whRate) {
                        whRate.disabled = !isWithholding;
                        whRate.value = withholdingRate || '2';
                    }
                    if (whAmt) {
                        whAmt.value = withholdingAmount || '0.00';
                    }
                }

                // Tax from Header (already populated by showCreateLineForm)
                // No additional action needed as it's read-only

                // Update form title
                const formTitle = document.querySelector('#lineFormPanel h3');
                if (formTitle) {
                    formTitle.textContent = 'Edit Line Item';
                }

                // Handle Requisition Link
                if (requisitionLineId) {
                    // Set hidden requisition line id
                    const reqLineIdField = document.getElementById('m_requisitionline_id');
                    if (reqLineIdField) reqLineIdField.value = requisitionLineId;

                    // Show info banner
                    const reqInfoDiv = document.getElementById('requisition_info_container');
                    const reqInfoText = document.getElementById('requisition_info_text');
                    if (reqInfoDiv && reqInfoText) {
                        reqInfoDiv.classList.remove('hidden');
                        reqInfoText.textContent = `Linked to Requisition: ${requisitionDocNo || '#' + requisitionLineId} — ${productText}`;
                    }

                    // Hide Remove Link button — in edit mode, user can only delete the line
                    const removeLinkBtn = document.getElementById('req_remove_link_btn');
                    if (removeLinkBtn) removeLinkBtn.classList.add('hidden');

                    // Lock product field (same mechanism as confirmRequisitionAdd)
                    if (productSelect.length) {
                        productSelect.next('.select2-container').css({
                            'pointer-events': 'none',
                            'opacity': '0.6'
                        });
                        // Remove name from real select, inject hidden backup
                        productSelect.removeAttr('name');
                        $('#m_product_id_req_backup').remove();
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'm_product_id_req_backup',
                            name: 'm_product_id',
                            value: productId
                        }).appendTo('#createLineForm');

                        // Add lock badge
                        const productLabel = productSelect.closest('div').prev('label');
                        if (productLabel.length && !productLabel.find('.req-lock-badge').length) {
                            productLabel.append('<span class="req-lock-badge ml-2 text-xs font-medium text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded">Locked by Requisition</span>');
                        }
                    }
                }

                // Show Delete button (edit mode)
                const deleteBtn = document.getElementById('form_delete_line_btn');
                if (deleteBtn) deleteBtn.classList.remove('hidden');

                // Calculate total
                calculateLineTotal();
            }

            function deleteCurrentLine() {
                const lineId = document.getElementById('line_id').value;
                if (!lineId) return;
                // Delegate to the existing deleteLine() function used in the table
                deleteLine(parseInt(lineId));
            }

            function saveLine() {
                const form = document.getElementById('createLineForm');
                if (!form) return;

                const formData = new FormData(form);
                const rawData = Object.fromEntries(formData.entries());

                // Clean numeric values (remove commas)
                const data = {
                    ...rawData,
                    qty: rawData.qty ? rawData.qty.replace(/,/g, '') : '',
                    price: rawData.price ? rawData.price.replace(/,/g, '') : ''
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
                const url = lineId ? '{{ route("purchase-order.line.update") }}' : '{{ route("purchase-order.line.store") }}';
                const method = lineId ? 'put' : 'post';

                // Show SweetAlert Loading
                Swal.fire({
                    title: 'Saving...',
                    text: 'Please wait while we save your line item.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                axios[method](url, data)
                    .then(res => {
                        // Update Totals if returned
                        if (res.data.grandtotal) {
                             const totalLinesEl = document.getElementById('txt_total_lines');
                             const taxEl = document.getElementById('txt_tax_amount');
                             const grandEl = document.getElementById('txt_grand_total');
                             const whtEl = document.getElementById('txt_withholding_total');
                             if (totalLinesEl && res.data.total_lines !== undefined) totalLinesEl.value = res.data.total_lines;
                             if (taxEl) taxEl.value = res.data.tax_amount;
                             if (grandEl) grandEl.value = res.data.grand_total_net ?? res.data.grandtotal;
                             if (whtEl && res.data.withholding_total !== undefined) whtEl.value = res.data.withholding_total;
                        }

                        // If there are more queued requisition lines, process the next one
                        if (reqLineQueue.length > 0) {
                            // Do NOT reload tab here — it would replace the DOM and break the form.
                            // Just show a brief toast, then open the next form directly.
                            setTimeout(() => lockTaxIfHasLines(), 300);
                            Swal.fire({
                                icon: 'success',
                                title: 'Saved!',
                                html: `Line saved. Loading next item (<b>${reqLineQueue.length}</b> remaining)...`,
                                timer: 1200,
                                showConfirmButton: false,
                                timerProgressBar: true
                            }).then(() => {
                                processNextQueuedRequisitionLine();
                            });
                        } else {
                            // Last item — show normal success and reload tab
                            Swal.fire({
                                icon: 'success',
                                title: 'Saved!',
                                text: 'Line item saved successfully.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                loadTabContent('lines');
                                setTimeout(() => lockTaxIfHasLines(), 500);
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        const status = err.response?.status;
                        const message = err.response?.data?.message || err.message;
                        Swal.fire({
                            icon: 'warning',
                            title: status === 422 ? 'Validation Error' : 'Error',
                            text: message,
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
                const pricelistId = $('#pricelist_id').val();
                const cBPartnerId = $('#c_bpartner_id').val();
                const docTypeId = $('#doc_type_id').val();

                const description = document.getElementById('description') ? document.getElementById('description').value : '';
                const dateOrdered = document.getElementById('date_ordered') ? document.getElementById('date_ordered').value : '';
                const datePromised = document.getElementById('date_promised') ? document.getElementById('date_promised').value : '';
                const priorityRule = $('#priority_rule').val();
                const paymentTermId = $('#c_paymentterm_id').val();
                const userCheckedId = $('#adw_ad_user_checked_id').val();
                const userApprovedId = $('#adw_ad_user_approved_id').val();
                const taxId = $('#c_tax_id').val();

                // Assuming this is part of an initScripts-like block or a document ready function
                if ($('#priority_rule').length) $('#priority_rule').select2({ minimumResultsForSearch: 10 });
                if ($('#c_paymentterm_id').length) $('#c_paymentterm_id').select2(); 
                if ($('#adw_ad_user_checked_id').length) $('#adw_ad_user_checked_id').select2();
                if ($('#adw_ad_user_approved_id').length) $('#adw_ad_user_approved_id').select2(); 
                 
                if ($('#c_tax_id').length) $('#c_tax_id').select2({ width: '100%' });

                const projectSelect = $('#c_project_id');
                const projectId = projectSelect.length ? projectSelect.val() : '';

                const data = {
                    description: description,
                    date_ordered: dateOrdered,
                    date_promised: datePromised,
                    warehouse_id: warehouseId,
                    org_id: orgId,
                    pricelist_id: pricelistId,
                    c_bpartner_id: cBPartnerId,
                    doc_type_id: docTypeId,
                    priority_rule: priorityRule,
                    c_paymentterm_id: paymentTermId,
                    adw_ad_user_checked_id: userCheckedId,
                    adw_ad_user_approved_id: userApprovedId,
                    c_tax_id: taxId,
                    c_project_id: projectId
                };

                if (!data.date_ordered) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Date Ordered is mandatory',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }
                if (!data.c_bpartner_id) {
                     Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Vendor is mandatory',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }
                if (!data.c_tax_id) {
                     Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Purchase Tax is mandatory',
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                // Lock submission
                window.isHeaderSubmitting = true;

                const isNew = {{ $isNew ? 'true' : 'false' }};
                const orderId = {{ isset($order) ? $order->c_order_id : 'null' }};

                // Show loading
                Swal.fire({
                    title: isNew ? 'Creating...' : 'Saving...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                if (isNew) {
                    axios.post('{{ route("purchase-order.store") }}', data)
                        .then(res => {
                            Swal.close();
                            if (res.data && res.data.data && res.data.data.encrypted_id) {
                                window.location.href = "{{ route('purchase-order.index') }}?document_id=" + res.data.data.encrypted_id;
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
                    let updateUrl = '{{ route("purchase-order.update", "ID_PLACEHOLDER") }}'.replace('ID_PLACEHOLDER', orderId);
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

            const PURCHASE_ORDER_ACTION_LABELS = @json($workflowActionLabels);
            const PURCHASE_ORDER_ACTION_CONFIRMATIONS = @json($workflowConfirmationMessages);

            function executeDocumentAction(action) {
                closeDocumentActionModal();

                let actionText = PURCHASE_ORDER_ACTION_LABELS[action] || action;
                let confirmText = PURCHASE_ORDER_ACTION_CONFIRMATIONS[action] || 'Are you sure you want to process this document?';

                Swal.fire({
                    title: `${actionText} Document?`,
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: action === '{{ $voidAction }}' ? '#ef4444' : '#4f46e5',
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

                axios.post('{{ route("purchase-order.process") }}', data)
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: res.data.message || `Document ${actionText.toLowerCase()}d successfully`,
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            // Reload page to Header Tab
                            window.location.href = "{{ route('purchase-order.index') }}?document_id={{ $docIdParam }}&tab=header";
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

            function deleteOrder() {
                Swal.fire({
                    title: 'Delete Order?',
                    text: "This action cannot be undone! All lines will also be deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDeleteOrder();
                    }
                });
            }

            function performDeleteOrder() {
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

                axios.delete('{{ route("purchase-order.delete") }}', { data: data })
                    .then(res => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: res.data.message || 'Order deleted successfully',
                            confirmButtonColor: '#4f46e5',
                            timer: 2000
                        }).then(() => {
                            // Redirect to list
                            window.location.href = '{{ route("purchase-order.index") }}';
                        });
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error deleting purchase order: ' + (err.response?.data?.message || err.message),
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

                axios.post('{{ route("purchase-order.attachment.upload") }}', formData, {
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

                        // Show Loading
                        Swal.fire({
                            title: 'Deleting...',
                            html: 'Processing 0 of ' + ids.length,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        let completed = 0;
                        const promises = ids.map(id => {
                            return axios.delete('{{ route("purchase-order.attachment.delete") }}', {
                                data: {
                                    document_id: docId,
                                    attachment_id: id
                                }
                            }).then(() => {
                                completed++;
                                const htmlContainer = Swal.getHtmlContainer();
                                if (htmlContainer) {
                                    htmlContainer.textContent = 'Processing ' + completed + ' of ' + ids.length;
                                }
                            }).catch(err => {
                                console.error('Failed to delete ' + id, err);
                            });
                        });

                        Promise.all(promises).then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: completed + ' attachments deleted.',
                                timer: 2000,
                                showConfirmButton: false,
                                didClose: () => {
                                    loadTabContent('attachments');
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

                        axios.delete('{{ route("purchase-order.attachment.delete") }}', {
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
                    $reqStatus = isset($order) ? $order->docstatus : 'DR';
                @endphp

                @if(in_array($reqStatus, $reactivateStatuses, true))
                    <!-- Complete: Re-Activate and Void options -->
                    <button type="button" onclick="executeDocumentAction('{{ $reactivateAction }}')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg transition-colors dark:bg-yellow-900/20 dark:hover:bg-yellow-900/30 dark:border-yellow-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="font-medium text-yellow-700 dark:text-yellow-300">{{ $workflowActionLabels[$reactivateAction] ?? 'Re-Activate' }}</span>
                        </div>
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="executeDocumentAction('{{ $voidAction }}')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:border-red-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                </path>
                            </svg>
                            <span class="font-medium text-red-700 dark:text-red-300">{{ $workflowActionLabels[$voidAction] ?? 'Void' }}</span>
                        </div>
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                @elseif(in_array($reqStatus, $completeVoidStatuses, true))
                    <!-- Not Approved: Complete and Void options -->
                    <button type="button" onclick="executeDocumentAction('{{ $completeAction }}')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:border-green-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium text-green-700 dark:text-green-300">{{ $workflowActionLabels[$completeAction] ?? 'Complete' }}</span>
                        </div>
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <button type="button" onclick="executeDocumentAction('{{ $voidAction }}')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:border-red-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                </path>
                            </svg>
                            <span class="font-medium text-red-700 dark:text-red-300">{{ $workflowActionLabels[$voidAction] ?? 'Void' }}</span>
                        </div>
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                @elseif(!in_array($reqStatus, $standardBlockedStatuses, true))
                    <!-- Draft and other statuses: Complete, Prepare, Void -->

                    <button type="button" onclick="executeDocumentAction('{{ $completeAction }}')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:border-green-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium text-green-700 dark:text-green-300">{{ $workflowActionLabels[$completeAction] ?? 'Complete' }}</span>
                        </div>
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <button type="button" onclick="executeDocumentAction('{{ $prepareAction }}')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:border-blue-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <span class="font-medium text-blue-700 dark:text-blue-300">{{ $workflowActionLabels[$prepareAction] ?? 'Prepare' }}</span>
                        </div>
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <button type="button" onclick="executeDocumentAction('{{ $voidAction }}')"
                        class="w-full flex items-center justify-between px-4 py-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:border-red-800">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                </path>
                            </svg>
                            <span class="font-medium text-red-700 dark:text-red-300">{{ $workflowActionLabels[$voidAction] ?? 'Void' }}</span>
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">Print
                            Preview</h3>
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
        window.openPrintModal = function (url) {
            const modal = document.getElementById('printModal');
            const iframe = document.getElementById('printFrame');
            if (modal && iframe) {
                iframe.src = url;
                modal.classList.remove("hidden");
                document.body.style.overflow = "hidden";
            }
        }

        window.closePrintModal = function () {
            const modal = document.getElementById('printModal');
            const iframe = document.getElementById('printFrame');
            if (modal) {
                modal.classList.add("hidden");
                if (iframe) iframe.src = "about:blank";
                document.body.style.overflow = "";
            }
        }

        function calculateLineTotal() {
            const qtyStr = document.getElementById('line_qty').value.replace(/,/g, '');
            const priceStr = document.getElementById('line_price').value.replace(/,/g, '');
            
            const qty = parseFloat(qtyStr) || 0;
            const price = parseFloat(priceStr) || 0;
            
            const lineNet = qty * price;

            // Recalculate withholding amount whenever line total changes
            calcLineWithholdingAmt();

            // Get tax rate from the header tax select (data-rate attribute)
            const taxSelect = document.getElementById('c_tax_id');
            let taxRate = 0;
            if (taxSelect && taxSelect.selectedIndex >= 0) {
                const selectedOpt = taxSelect.options[taxSelect.selectedIndex];
                taxRate = parseFloat(selectedOpt?.dataset?.rate) || 0;
            }
            const taxAmt = lineNet * taxRate / 100;

            // Withholding amount (already computed by calcLineWithholdingAmt)
            const whAmtStr = (document.getElementById('line_withholding_amount')?.value || '0').replace(/,/g, '');
            const whAmt = parseFloat(whAmtStr) || 0;

            // Line Amount = Net + Tax - Withholding
            const total = lineNet + taxAmt - whAmt;

            document.getElementById('line_amount').value = total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function onLineWithholdingToggle(checked) {
            const rateInput = document.getElementById('line_withholding_rate');
            rateInput.disabled = !checked;
            if (checked && (!rateInput.value || parseFloat(rateInput.value) === 0)) {
                rateInput.value = '2';
            }
            calculateLineTotal();
        }

        function calcLineWithholdingAmt() {
            const checkbox = document.getElementById('line_is_withholding');
            const amtInput = document.getElementById('line_withholding_amount');

            if (!checkbox || !checkbox.checked) {
                if (amtInput) amtInput.value = '0.00';
                return;
            }

            const qtyStr = (document.getElementById('line_qty')?.value || '').replace(/,/g, '');
            const priceStr = (document.getElementById('line_price')?.value || '').replace(/,/g, '');
            const rate = parseFloat(document.getElementById('line_withholding_rate')?.value) || 0;

            const qty = parseFloat(qtyStr) || 0;
            const price = parseFloat(priceStr) || 0;
            const lineAmt = qty * price;
            const withholdingAmt = lineAmt * rate / 100;

            if (amtInput) {
                amtInput.value = withholdingAmt.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }

        // =============================================
        // REQUISITION MODAL - Table-based Implementation
        // =============================================
        let reqCurrentPage = 1;
        let reqSearchTimer = null;
        let reqSelectedItems = []; // Multi-select: array of selected requisition line objects
        let reqLineQueue = [];    // Queue for sequential processing after confirmRequisitionAdd

        function openRequisitionModal() {
            const modal = document.getElementById('requisitionSelectionModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Reset state
            reqSelectedItems = [];
            document.getElementById('req_selected_count').textContent = 'No line selected';
            document.getElementById('req_confirm_btn').disabled = true;
            document.getElementById('req_search_input').value = '';
            // Load data
            loadRequisitionLines(1);
        }

        function closeRequisitionModal() {
            const modal = document.getElementById('requisitionSelectionModal');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            reqSelectedItems = [];
        }

        function debounceReqSearch(value) {
            clearTimeout(reqSearchTimer);
            reqSearchTimer = setTimeout(() => {
                loadRequisitionLines(1);
            }, 400);
        }

        function loadRequisitionLines(page) {
            reqCurrentPage = page;
            const search = document.getElementById('req_search_input').value;

            // Show loading
            document.getElementById('req_loading').classList.remove('hidden');
            document.getElementById('req_empty').classList.add('hidden');
            document.getElementById('req_table').classList.add('hidden');
            document.getElementById('req_pagination_info').innerHTML = '&nbsp;';
            document.getElementById('req_pagination_btns').innerHTML = '';

            fetch(`{{ route('purchase-order.api.requisition-lines') }}?q=${encodeURIComponent(search)}&page=${page}&per_page=15`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('req_loading').classList.add('hidden');

                    const results = data.results || [];
                    const pagination = data.pagination || {};

                    if (results.length === 0) {
                        document.getElementById('req_empty').classList.remove('hidden');
                        return;
                    }

                    document.getElementById('req_table').classList.remove('hidden');
                    renderReqTable(results);
                    renderReqPagination(pagination, page, data.total, data.per_page);
                })
                .catch(err => {
                    document.getElementById('req_loading').classList.add('hidden');
                    document.getElementById('req_empty').classList.remove('hidden');
                    console.error('Failed to load requisitions:', err);
                });
        }

        function renderReqTable(results) {
            const tbody = document.getElementById('req_table_body');
            tbody.innerHTML = '';

            results.forEach(item => {
                const isSelected = reqSelectedItems.some(s => s.id === item.id);
                const tr = document.createElement('tr');
                tr.className = `hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors cursor-pointer ${isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : ''}`;
                tr.dataset.id = item.id;
                tr.onclick = function() {
                    const cb = this.querySelector('.req-row-checkbox');
                    if (cb) { cb.checked = !cb.checked; onReqCheckboxChange(cb, item); }
                };

                tr.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="checkbox" class="req-row-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            data-id="${item.id}" ${isSelected ? 'checked' : ''}
                            onchange="onReqCheckboxChange(this, ${JSON.stringify(item).replace(/"/g, '&quot;')})"
                            onclick="event.stopPropagation()">
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            ${escapeHtml(item.po_number)}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900 dark:text-white leading-tight">${escapeHtml(item.product_name)}</div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5">${escapeHtml(item.product_value)}</div>
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-mono text-gray-700 dark:text-gray-300">${parseFloat(item.qty).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(tr);
            });

            // Sync master checkbox state after render
            syncReqMasterCheckbox();
        }

        function onReqCheckboxChange(checkbox, item) {
            const tr = checkbox.closest('tr');
            if (checkbox.checked) {
                if (!reqSelectedItems.some(s => s.id === item.id)) {
                    reqSelectedItems.push(item);
                }
                if (tr) tr.classList.add('bg-blue-50', 'dark:bg-blue-900/20');
            } else {
                reqSelectedItems = reqSelectedItems.filter(s => s.id !== item.id);
                if (tr) tr.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
            }
            syncReqMasterCheckbox();
            updateReqSelectionUI();
        }

        function toggleAllReqCheckboxes(masterCb) {
            const checkboxes = document.querySelectorAll('.req-row-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = masterCb.checked;
                const tr = cb.closest('tr');
                // Get the item data from the data-id then find from rendered table
                if (masterCb.checked) {
                    if (tr) tr.classList.add('bg-blue-50', 'dark:bg-blue-900/20');
                } else {
                    if (tr) tr.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                }
            });

            if (masterCb.checked) {
                // Collect all items currently rendered in table
                checkboxes.forEach(cb => {
                    const tr = cb.closest('tr');
                    if (!tr) return;
                    // Retrieve item from the onchange attribute via data injection
                    const onchangeAttr = cb.getAttribute('onchange') || '';
                    const idMatch = cb.dataset.id;
                    if (idMatch && !reqSelectedItems.some(s => String(s.id) === String(idMatch))) {
                        // Extract item from tr's onclick which has the full object
                        // We stored item JSON in the checkbox onchange, parse it
                        const match = onchangeAttr.match(/onReqCheckboxChange\(this,\s*(.+)\)$/);
                        if (match) {
                            try {
                                // The JSON is HTML-entity-encoded, decode it
                                const raw = match[1].replace(/&quot;/g, '"');
                                const item = JSON.parse(raw);
                                if (!reqSelectedItems.some(s => s.id === item.id)) {
                                    reqSelectedItems.push(item);
                                }
                            } catch (e) { /* skip */ }
                        }
                    }
                });
            } else {
                // Remove all currently-visible items from selected
                const visibleIds = Array.from(checkboxes).map(cb => String(cb.dataset.id));
                reqSelectedItems = reqSelectedItems.filter(s => !visibleIds.includes(String(s.id)));
            }

            updateReqSelectionUI();
        }

        function syncReqMasterCheckbox() {
            const masterCb = document.getElementById('req_select_all');
            if (!masterCb) return;
            const checkboxes = document.querySelectorAll('.req-row-checkbox');
            if (checkboxes.length === 0) { masterCb.checked = false; masterCb.indeterminate = false; return; }
            const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            if (checkedCount === 0) { masterCb.checked = false; masterCb.indeterminate = false; }
            else if (checkedCount === checkboxes.length) { masterCb.checked = true; masterCb.indeterminate = false; }
            else { masterCb.checked = false; masterCb.indeterminate = true; }
        }

        function updateReqSelectionUI() {
            const countEl = document.getElementById('req_selected_count');
            const confirmBtn = document.getElementById('req_confirm_btn');
            const n = reqSelectedItems.length;
            if (n > 0) {
                countEl.innerHTML = `<span class="font-medium text-blue-700 dark:text-blue-400">${n} line${n > 1 ? 's' : ''} selected</span>`;
                confirmBtn.disabled = false;
            } else {
                countEl.textContent = 'No line selected';
                confirmBtn.disabled = true;
            }
        }

        function renderReqPagination(pagination, currentPage, total, perPage) {
            const infoEl = document.getElementById('req_pagination_info');
            const btnsEl = document.getElementById('req_pagination_btns');

            // Info text
            if (total && perPage) {
                const from = ((currentPage - 1) * perPage) + 1;
                const to = Math.min(currentPage * perPage, total);
                infoEl.textContent = `Showing ${from}–${to} of ${total} results`;
            }

            btnsEl.innerHTML = '';

            // Prev button
            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.disabled = currentPage <= 1;
            prevBtn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors';
            prevBtn.textContent = '← Prev';
            prevBtn.onclick = () => loadRequisitionLines(currentPage - 1);
            btnsEl.appendChild(prevBtn);

            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.disabled = !pagination.more;
            nextBtn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors';
            nextBtn.textContent = 'Next →';
            nextBtn.onclick = () => loadRequisitionLines(currentPage + 1);
            btnsEl.appendChild(nextBtn);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function confirmRequisitionAdd() {
            if (reqSelectedItems.length === 0) {
                Swal.fire('No Selection', 'Please select at least one requisition line first.', 'warning');
                return;
            }

            // Build a queue from all selected items (clone array)
            reqLineQueue = [...reqSelectedItems];

            // Close Modal
            closeRequisitionModal();

            // Process first item
            processNextQueuedRequisitionLine();
        }

        function processNextQueuedRequisitionLine() {
            if (reqLineQueue.length === 0) return;

            const item = reqLineQueue.shift(); // take first item

            // Open/reset Line Form
            showCreateLineForm();

            // 1. Set Hidden Requisition Line ID
            const reqLineIdField = document.getElementById('m_requisitionline_id');
            if (reqLineIdField) reqLineIdField.value = item.id;

            // Show Requisition Info Banner (with queue counter if more items remain)
            const reqInfoDiv = document.getElementById('requisition_info_container');
            const reqInfoText = document.getElementById('requisition_info_text');
            if (reqInfoDiv && reqInfoText) {
                reqInfoDiv.classList.remove('hidden');
                const remaining = reqLineQueue.length;
                const queueNote = remaining > 0 ? ` <span class="ml-2 px-1.5 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 rounded">${remaining} more in queue</span>` : '';
                reqInfoText.innerHTML = `${escapeHtml(item.po_number)} — ${escapeHtml(item.product_value)} — ${escapeHtml(item.product_name)} (Qty: ${item.qty})${queueNote}`;
            }

            // 2. Set Product via Select2 then DISABLE it (locked to requisition)
            const productSelect = $('#m_product_id');
            if (productSelect.length) {
                if (productSelect.find(`option[value='${item.m_product_id}']`).length) {
                    productSelect.val(item.m_product_id).trigger('change');
                } else {
                    const newOption = new Option(`${item.product_value} - ${item.product_name}`, item.m_product_id, true, true);
                    productSelect.append(newOption).trigger('change');
                }

                // Lock visually — DO NOT use .prop('disabled') because disabled fields
                // are excluded from FormData and will cause "Product is required" error.
                // Instead: block interaction via CSS + inject a hidden input to carry the value.
                productSelect.next('.select2-container').css({
                    'pointer-events': 'none',
                    'opacity': '0.6'
                });

                // Remove name from the real select so FormData doesn't pick it up twice
                productSelect.removeAttr('name');

                // Remove any existing backup input, then inject fresh one
                $('#m_product_id_req_backup').remove();
                $('<input>').attr({
                    type: 'hidden',
                    id: 'm_product_id_req_backup',
                    name: 'm_product_id',
                    value: item.m_product_id
                }).appendTo('#createLineForm');

                // Also set the real select value (for UI consistency)
                productSelect.val(item.m_product_id);

                // Add lock badge to label
                const productLabel = productSelect.closest('div').prev('label');
                if (productLabel.length && !productLabel.find('.req-lock-badge').length) {
                    productLabel.append('<span class="req-lock-badge ml-2 text-xs font-medium text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded">Locked by Requisition</span>');
                }
            }

            // 3. Set Qty
            const qtyInput = document.getElementById('line_qty');
            if (qtyInput) {
                qtyInput.value = parseFloat(item.qty).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                calculateLineTotal();
            }

            // Show SweetAlert loading effect briefly
            Swal.fire({
                title: 'Loading from Requisition...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            setTimeout(() => Swal.close(), 800);
        }

        function clearRequisitionLink() {
            // Clear hidden field
            const reqLineIdField = document.getElementById('m_requisitionline_id');
            if (reqLineIdField) reqLineIdField.value = '';

            // Hide info banner
            const reqInfoDiv = document.getElementById('requisition_info_container');
            if (reqInfoDiv) reqInfoDiv.classList.add('hidden');

            // Re-enable the Product Select2 field
            const productSelect = $('#m_product_id');
            if (productSelect.length) {
                productSelect.next('.select2-container').css({
                    'pointer-events': '',
                    'opacity': ''
                });
                // Restore the name attribute so FormData picks it up again
                productSelect.attr('name', 'm_product_id');
            }
            // Remove hidden backup input
            $('#m_product_id_req_backup').remove();
            // Remove lock badge from label
            document.querySelectorAll('.req-lock-badge').forEach(el => el.remove());
        }

    </script>
@endpush