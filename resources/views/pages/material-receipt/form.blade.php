@extends('layouts.app')

@section('content')

    @php
        $materialReceiptConfig = $materialReceiptConfig ?? config('idempiere.create-gr');
        $isNew       = is_null($receipt);
        $activeTab   = $activeTab ?? 'header';
        $docNo       = $isNew ? '** New **' : $receipt->documentno;
        $desc        = $isNew ? '' : $receipt->description;

        $defaultOrgId = null;
        if (isset($organizations) && count($organizations) > 0) {
            $sortedOrgs   = collect($organizations)->sortBy('id');
            $defaultOrgId = $sortedOrgs->first()->id;
        }

        $defaultWarehouseId = null;
        if (isset($warehouses) && count($warehouses) > 0) {
            $sortedWh           = collect($warehouses)->sortBy('id');
            $defaultWarehouseId = $sortedWh->first()->id;
        }

        $currentOrgId  = $isNew ? $defaultOrgId : $receipt->ad_org_id;
        $warehouseId   = $isNew ? $defaultWarehouseId : $receipt->m_warehouse_id;
        $movementDate  = $isNew ? now()->format('Y-m-d') : \Carbon\Carbon::parse($receipt->movementdate)->format('Y-m-d');
        $dateAcct      = $isNew ? now()->format('Y-m-d') : \Carbon\Carbon::parse($receipt->dateacct ?? $receipt->movementdate)->format('Y-m-d');
        $docIdParam    = request('document_id');
        $isReadOnly    = !$isNew && in_array($receipt->docstatus, $materialReceiptConfig['statuses']['read_only'], true);
        $draftStatuses = $materialReceiptConfig['statuses']['draft'] ?? ['DR'];
        $isDraft       = !$isNew && in_array($receipt->docstatus, $draftStatuses, true);
        $headerBadgeClasses = $materialReceiptConfig['statuses']['header_badge_classes'] ?? [];
        $hColor = $headerBadgeClasses[$receipt->docstatus ?? 'DR'] ?? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        $workflowConfig = $materialReceiptConfig['workflow'] ?? [];
        $completeFromStatuses = $workflowConfig['complete_from'] ?? [];
        $voidFromStatuses = $workflowConfig['void_from'] ?? [];
        $reverseFromStatuses = $workflowConfig['reverse_from'] ?? [];
        $closeFromStatuses = $workflowConfig['close_from'] ?? [];
        $reactivateAction = $workflowConfig['reactivate_action'] ?? 'RE';
        $reactivateFromStatuses = $workflowConfig['reactivate_from'] ?? [];
        $completeFromStatuses = array_map(static fn ($status) => strtoupper(trim((string) $status)), $completeFromStatuses);
        $reactivateFromStatuses = array_map(static fn ($status) => strtoupper(trim((string) $status)), $reactivateFromStatuses);
        $workflowActionLabels = $workflowConfig['action_labels'] ?? [];
        $workflowConfirmationMessages = $workflowConfig['confirmation_messages'] ?? [];
        $workflowActionDescriptions = $workflowConfig['button_descriptions'] ?? [];
        $currentDocStatus = strtoupper(trim((string) ($receipt->docstatus ?? 'DR')));
        $showCompleteAction = !$isNew && in_array($currentDocStatus, $completeFromStatuses, true);
        $showReactivateAction = !$isNew && $currentDocStatus === 'CO';
        $hasDocumentActions = !$isNew && (
            $showCompleteAction
            || $showReactivateAction
        );
    @endphp

    <div>
        <!-- Breadcrumb & Title -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('material-receipt.index') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $isNew ? 'New Material Receipt' : $docNo }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                        @if(!$isNew)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $hColor }}">{{ $status }}</span>
                            <span class="text-gray-300">•</span>
                        @endif
                        <span>{{ $isNew ? 'Create a new material receipt' : 'Manage receipt details and lines' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                @if(!$isNew)
                    <button type="button"
                        onclick="openPrintModal('{{ route('material-receipt.print', ['document_id' => $docIdParam]) }}')"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 shadow-sm transition-all gap-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print
                    </button> 
                @endif

                <!-- Header Save Button -->
                <div id="header-actions" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @if(!$isReadOnly)
                        <button onclick="submitHeader()"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm hover:shadow transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $isNew ? 'Create Receipt' : 'Save Changes' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Card with Tabs -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 overflow-visible relative">

            <!-- Tabs Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 px-6 sm:px-8 rounded-t-2xl">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="#" onclick="switchTab('header'); return false;" id="nav-header"
                        class="{{ $activeTab == 'header' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Header Details
                    </a>
                    <a href="#" onclick="switchTab('lines'); return false;" id="nav-lines"
                        class="{{ $activeTab == 'lines' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $isNew ? 'cursor-not-allowed opacity-50 pointer-events-none' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Lines
                    </a>
                    <a href="#" onclick="switchTab('attachments'); return false;" id="nav-attachments"
                        class="{{ $activeTab == 'attachments' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $isNew ? 'cursor-not-allowed opacity-50 pointer-events-none' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        Attachments
                    </a>
                    @if(!$isDraft && !$isNew)
                    <a href="#" onclick="switchTab('journals'); return false;" id="nav-journals"
                        class="{{ $activeTab == 'journals' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Jurnal
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Tab Content -->
            <div id="tab-content-wrapper">
                <div id="tab-header" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @include('pages.material-receipt.partials.tab-header')
                </div>
                <div id="tab-lines" class="{{ $activeTab == 'lines' ? 'block' : 'hidden' }}">
                    @include('pages.material-receipt.partials.tab-lines')
                </div>
                <div id="tab-attachments" class="{{ $activeTab == 'attachments' ? 'block' : 'hidden' }}">
                </div>
                @if(!$isDraft && !$isNew)
                <div id="tab-journals" class="{{ $activeTab == 'journals' ? 'block' : 'hidden' }}">
                </div>
                @endif
            </div>

        </div>
    </div>

    <!-- Document Action Modal -->
    @if(!$isNew)
    <div id="documentActionModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm">
            <div class="fixed inset-0 bg-gray-900/60" onclick="closeDocumentActionModal()"></div>
            <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full dark:bg-gray-900">
                <div class="px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-8 h-8 bg-brand-100 dark:bg-brand-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        Document Action
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select an action to perform on this document</p>
                </div>
                <div class="px-6 py-5 space-y-3">
                    @if($showCompleteAction)
                        <button onclick="processDocument('CO')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-green-200 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:border-green-800 dark:hover:bg-green-900/40 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-green-800 dark:text-green-300">{{ $workflowActionLabels['CO'] ?? 'Complete' }}</div>
                                <div class="text-xs text-green-600 dark:text-green-400">{{ $workflowActionDescriptions['CO'] ?? 'Process and complete this receipt' }}</div>
                            </div>
                        </button>
                    @endif
                    @if($showReactivateAction)
                        <button onclick="processDocument('{{ $reactivateAction }}')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-orange-200 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:border-orange-800 dark:hover:bg-orange-900/40 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-orange-800 dark:text-orange-300">{{ $workflowActionLabels[$reactivateAction] ?? 'Re-Active' }}</div>
                                <div class="text-xs text-orange-600 dark:text-orange-400">{{ $workflowActionDescriptions[$reactivateAction] ?? 'Create a new draft by copying this receipt, then reverse the old document' }}</div>
                            </div>
                        </button>
                    @endif
                </div>
                <div class="px-6 pb-5">
                    <button onclick="closeDocumentActionModal()"
                        class="w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
        <!-- Select2 -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- SweetAlert2 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <style>
            .select2-container--default .select2-selection--single {
                border: 1px solid #d1d5db; border-radius: 0.5rem; height: 46px;
                display: flex; align-items: center; font-size: 14px;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered { color: #1f2937; padding-left: 1rem; }
            .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px; right: 0.75rem; }
            .dark .select2-container--default .select2-selection--single { background-color: #111827; border-color: #374151; }
            .dark .select2-container--default .select2-selection--single .select2-selection__rendered { color: #e5e7eb; }
            .dark .select2-dropdown { background-color: #1f2937; border-color: #374151; color: #e5e7eb; }
            .dark .select2-container--default .select2-results__option--selectable { background-color: #1f2937; color: #e5e7eb; }
            .dark .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: #4f46e5; color: white; }
            .hidden { display: none !important; }
            .swal2-popup { font-size: 0.875rem !important; }
            .swal2-title { font-size: 1.125rem !important; }
            
            /* Flatpickr Calendar z-index fix */
            .flatpickr-calendar {
                z-index: 9999 !important;
            }
        </style>

        <script>
        function initScripts() {
            $('#c_bpartner_id, #org_id, #warehouse_id, #doc_type_id, #c_project_id, #adw_ad_user_checked_id, #adw_ad_user_approved_id').select2({
                width: '100%', placeholder: '- Select -'
            });
            $('#c_project_id').select2({ width: '100%', placeholder: '- Select -', allowClear: true }); 

            if ($('#m_product_id').length > 0) {
                $('#m_product_id').select2({
                    width: '100%', placeholder: 'Search Product...', allowClear: false,
                    ajax: {
                        url: '{{ route("material-receipt.api.products") }}',
                        dataType: 'json', delay: 250,
                        data: function (params) { return { q: params.term, page: params.page || 1 }; },
                        processResults: function (data, params) {
                            return { results: data.results, pagination: { more: data.pagination.more } };
                        }, cache: true
                    }
                });

                $('#m_product_id').off('change').on('change', function () {
                    const selectedData = $(this).select2('data');
                    if (selectedData && selectedData.length > 0) {
                        const product = selectedData[0];
                        const uomDisplay = product.uom_symbol || product.uom_name || 'Unit';
                        $('#product_uom').text(uomDisplay);
                    } else {
                        $('#product_uom').text('Unit');
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            initScripts();
            // Read tab from URL and activate it
            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');
            if (tabFromUrl && ['header', 'lines', 'attachments', 'journals'].includes(tabFromUrl)) {
                switchTab(tabFromUrl, true);
            }
        });

        // ── Tab Switching ──────────────────────────────────────────────────────
        function switchTab(tab, skipUrlUpdate) {
            const tabs = ['header', 'lines', 'attachments', 'journals'];
            tabs.forEach(t => {
                document.getElementById('tab-' + t)?.classList.add('hidden');
                const nav = document.getElementById('nav-' + t);
                if (nav) {
                    nav.classList.remove('border-brand-500', 'text-brand-600');
                    nav.classList.add('border-transparent', 'text-gray-500');
                }
            });

            document.getElementById('tab-' + tab)?.classList.remove('hidden');
            const activeNav = document.getElementById('nav-' + tab);
            if (activeNav) {
                activeNav.classList.remove('border-transparent', 'text-gray-500');
                activeNav.classList.add('border-brand-500', 'text-brand-600');
            }

            document.getElementById('header-actions')?.classList.toggle('hidden', tab !== 'header');
            document.getElementById('header-actions')?.classList.toggle('block', tab === 'header');

            // Update URL
            if (!skipUrlUpdate) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.pushState({}, '', url.toString());
            }

            // AJAX-load all tabs on every switch (skip if new doc)
            const isNew = {{ $isNew ? 'true' : 'false' }};
            if (!isNew) loadTabContent(tab);
        }

        function loadTabContent(tabName, params = {}) {
            const container = document.getElementById('tab-' + tabName);
            if (!container) return;

            // Show spinner
            container.innerHTML = `
                <div class="flex flex-col justify-center items-center py-24">
                    <div class="rounded-full bg-brand-50 p-4 mb-4">
                        <svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <span class="text-gray-500 font-medium dark:text-gray-400">Loading details...</span>
                </div>
            `;

            const url = new URL(window.location.href);
            url.searchParams.set('ajax_tab', tabName);
            url.searchParams.set('_t', Date.now()); // Prevent cache
            Object.keys(params).forEach(key => url.searchParams.set(key, params[key]));

            axios.get(url.toString())
                .then(res => {
                    container.innerHTML = res.data;
                    initScripts();
                    if (tabName === 'attachments') initAttachmentScripts();
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = `
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="rounded-full bg-red-100 p-3 mb-3">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-gray-900 font-medium">Failed to load content</h3>
                            <button onclick="loadTabContent('${tabName}')" class="mt-4 px-4 py-2 text-sm font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100">Try Again</button>
                        </div>
                    `;
                });
        }

        // ── Header Submit ──────────────────────────────────────────────────────
        function submitHeader() {
            const isNew = {{ $isNew ? 'true' : 'false' }};
            const docIdParam = '{{ $docIdParam }}';

            const payload = {
                org_id:                  $('#org_id').val(),
                warehouse_id:            $('#warehouse_id').val(),
                c_bpartner_id:           $('#c_bpartner_id').val(),
                movement_date:           document.getElementById('movement_date')?.value,
                date_acct:               document.getElementById('date_acct')?.value,
                doc_type_id:             $('#doc_type_id').val(),
                description:             document.getElementById('description')?.value,
                c_project_id:            $('#c_project_id').val() || null,
                adw_ad_user_checked_id:  $('#adw_ad_user_checked_id').val() || null,
                adw_ad_user_approved_id: $('#adw_ad_user_approved_id').val() || null,
                _token:                  '{{ csrf_token() }}'
            };

            if (isNew) {
                axios.post('{{ route("material-receipt.store") }}', payload)
                    .then(res => {
                        const encId = res.data.data.encrypted_id;
                        Swal.fire({ icon: 'success', title: 'Created!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => {
                                window.location.href = '{{ route("material-receipt.index") }}?document_id=' + encodeURIComponent(encId);
                            });
                    })
                    .catch(err => {
                        const data = err.response?.data;
                        const msg = data?.message
                            || (data?.errors ? JSON.stringify(data.errors) : null)
                            || 'Failed to create receipt.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    });
            } else {
                let receiptId = null;
                try {
                    const urlParams = new URLSearchParams(window.location.search);
                    const encId = urlParams.get('document_id');
                    receiptId = encId;
                } catch(e) {}

                axios.put('{{ route("material-receipt.update", ":id") }}'.replace(':id', '{{ $receipt?->m_inout_id }}'), payload)
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Saved!', text: res.data.message, timer: 1500, showConfirmButton: false });
                    })
                    .catch(err => {
                        const msg = err.response?.data?.message || 'Failed to save changes.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    });
            }
        }

        // ── Document Action Modal ──────────────────────────────────────────────
        function openDocumentActionModal() {
            document.getElementById('documentActionModal')?.classList.remove('hidden');
        }
        function closeDocumentActionModal() {
            document.getElementById('documentActionModal')?.classList.add('hidden');
        }

        function processDocument(action) {
            closeDocumentActionModal();
            const actionNames = @json($workflowActionLabels);
            const actionConfirmations = @json($workflowConfirmationMessages);
            Swal.fire({
                title: (actionNames[action] || action) + ' Receipt?',
                text: actionConfirmations[action] || ('Are you sure you want to ' + (actionNames[action] || action).toLowerCase() + ' this receipt?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: action === 'CO' ? '#16a34a' : action === 'VO' ? '#dc2626' : '#d97706',
                confirmButtonText: 'Yes, ' + (actionNames[action] || action),
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    if (action === 'RE') {
                        Swal.fire({
                            title: 'Processing Re-Active...',
                            html: 'Copying document and reversing the original.<br>Please wait.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                    }

                    axios.post('{{ route("material-receipt.process") }}', {
                        document_id: '{{ $docIdParam }}',
                        doc_action:  action,
                        _token:      '{{ csrf_token() }}'
                    })
                    .then(res => {
                        const newDocumentId = res.data?.new_document_id;
                        Swal.fire({ icon: 'success', title: 'Done!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => {
                                if (newDocumentId) {
                                    window.location.href = '{{ route("material-receipt.index") }}?document_id=' + encodeURIComponent(newDocumentId);
                                    return;
                                }
                                window.location.reload();
                            });
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Action failed.' });
                    });
                }
            });
        }

        function deleteOrder() {
            Swal.fire({
                title: 'Delete Receipt?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.delete('{{ route("material-receipt.delete") }}', {
                        data: { document_id: '{{ $docIdParam }}', _token: '{{ csrf_token() }}' }
                    })
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => { window.location.href = '{{ route("material-receipt.index") }}'; });
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Delete failed.' });
                    });
                }
            });
        }

        // ── Lines Tab: helper functions (delegated to tab-lines scripts) ───────
        function formatNumber(input) {
            const raw = input.value.replace(/[^0-9.]/g, '');
            const num = parseFloat(raw);
            input.value = isNaN(num) ? '' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function parseFormattedNumber(str) {
            return parseFloat((str || '0').replace(/,/g, '')) || 0;
        }

        function calculateLineTotal() {
            const qty    = parseFormattedNumber(document.getElementById('line_qty')?.value);
            const price  = parseFormattedNumber(document.getElementById('line_price')?.value);
            const total  = qty * price;
            const amtEl  = document.getElementById('line_amount');
            if (amtEl) amtEl.value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // ── Attachments AJAX helpers (initiated after tab loads) ───────────────
        function initAttachmentScripts() {
            const docIdParam = '{{ $docIdParam }}';

            window.handleFileUpload = function(files) {
                if (!files || files.length === 0) return;
                const file = files[0];
                const formData = new FormData();
                formData.append('file', file);
                formData.append('document_id', docIdParam);
                formData.append('_token', '{{ csrf_token() }}');

                Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                axios.post('{{ route("material-receipt.attachment.upload") }}', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                }).then(() => {
                    Swal.fire({ icon: 'success', title: 'Uploaded!', timer: 1000, showConfirmButton: false })
                        .then(() => loadTabContent('attachments'));
                }).catch(err => {
                    Swal.fire({ icon: 'error', title: 'Upload Failed', text: err.response?.data?.message || 'Upload failed' });
                });
            };

            window.deleteAttachment = function(attId) {
                Swal.fire({
                    title: 'Delete Attachment?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Delete'
                }).then(result => {
                    if (result.isConfirmed) {
                        axios.delete('{{ route("material-receipt.attachment.delete") }}', {
                            data: { document_id: docIdParam, attachment_id: attId, _token: '{{ csrf_token() }}' }
                        }).then(() => {
                            loadTabContent('attachments');
                        }).catch(err => {
                            Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Delete failed' });
                        });
                    }
                });
            };

            window.openAttachmentPreview = function(url, name) {
                const modal = document.getElementById('printModal');
                if (!modal) return;
                document.getElementById('printFrame').src = url;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            };

            window.toggleSelectAll = function(cb) {
                document.querySelectorAll('.attachment-checkbox').forEach(c => c.checked = cb.checked);
                updateDeleteButtonState();
            };

            window.updateDeleteButtonState = function() {
                const checked = document.querySelectorAll('.attachment-checkbox:checked').length;
                const btn = document.getElementById('btnDeleteSelected');
                if (btn) btn.classList.toggle('hidden', checked === 0);
            };

            window.deleteSelectedAttachments = function() {
                const ids = [...document.querySelectorAll('.attachment-checkbox:checked')].map(c => c.value);
                if (!ids.length) return;
                Swal.fire({
                    title: 'Delete ' + ids.length + ' attachment(s)?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Delete All'
                }).then(result => {
                    if (result.isConfirmed) {
                        Promise.all(ids.map(id =>
                            axios.delete('{{ route("material-receipt.attachment.delete") }}', {
                                data: { document_id: docIdParam, attachment_id: id, _token: '{{ csrf_token() }}' }
                            })
                        )).then(() => {
                            document.getElementById('tab-attachments').innerHTML = '';
                            document.getElementById('nav-attachments').click();
                        });
                    }
                });
            };
        }

        // Print Modal helpers
        window.openPrintModal = function(url) {
            const modal = document.getElementById('printModal');
            const iframe = document.getElementById('printFrame');
            if (!modal || !iframe) return;
            iframe.src = url;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };
        window.closePrintModal = function() {
            const modal = document.getElementById('printModal');
            const iframe = document.getElementById('printFrame');
            if (!modal) return;
            modal.classList.add('hidden');
            if (iframe) iframe.src = 'about:blank';
            document.body.style.overflow = '';
        };
        </script>

        <!-- Print/Preview Modal -->
        <div id="printModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
    @endpush

@endsection
