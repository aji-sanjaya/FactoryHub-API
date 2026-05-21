@extends('layouts.app')

@section('content')

    @php
        $arInvoiceConfig = config('idempiere.ar-invoice');
        $statusConfig = $arInvoiceConfig['statuses'] ?? [];
        $workflowConfig = $arInvoiceConfig['workflow'] ?? [];
        $isNew      = is_null($invoice);
        $activeTab  = $activeTab ?? 'header';
        $docNo      = $isNew ? '** New **' : $invoice->documentno;
        $docIdParam = request('document_id');
        $readOnlyStatuses = $statusConfig['read_only'] ?? ['CO', 'CL', 'VO', 'RE'];
        $draftStatus = $statusConfig['draft'] ?? 'DR';
        $inProgressStatus = $statusConfig['in_progress'] ?? 'IP';
        $completeFromStatuses = $workflowConfig['complete_from'] ?? [$draftStatus, $inProgressStatus];
        $voidFromStatuses = $workflowConfig['void_from'] ?? [$draftStatus];
        $reverseFromStatuses = $workflowConfig['reverse_from'] ?? ['CO'];
        $closeFromStatuses = $workflowConfig['close_from'] ?? ['CO'];
        $printExcludedStatuses = $workflowConfig['print_excluded'] ?? ['VO'];
        $shipmentLinesPerPage = $arInvoiceConfig['limits']['shipment_lines_per_page'] ?? 15;
        $isReadOnly = !$isNew && in_array($invoice->docstatus, $readOnlyStatuses);
    @endphp

    <div>
        <!-- Breadcrumb & Title -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('ar-invoice.index') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $isNew ? 'New AR Invoice' : $docNo }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                        @if(!$isNew)
                            @php
                                $hColor = match($invoice->docstatus) {
                                    'NA','VO','RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'CO','CL','AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'IP'           => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'DR'           => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    default        => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $hColor }}">{{ $status }}</span>
                            <span class="text-gray-300">•</span>
                        @endif
                        <span>{{ $isNew ? 'Create a new customer invoice' : 'Manage invoice details and lines' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <!-- Header Save Button (shown only on Header tab) -->
                <div id="header-actions" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @if(!$isReadOnly)
                        <button onclick="submitHeader()"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm hover:shadow transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $isNew ? 'Create Invoice' : 'Save Changes' }}
                        </button>
                    @endif
                    @if(!$isNew && !in_array($invoice->docstatus, $printExcludedStatuses))
                        <button onclick="openInvoicePrintModal('{{ route('ar-invoice.print', ['id' => \Illuminate\Support\Facades\Crypt::encryptString($invoice->c_invoice_id)]) }}')"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 shadow-sm hover:shadow transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Card with Tabs -->
        <div class="bg-white border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 overflow-visible relative rounded-t-2xl rounded-b-2xl">

            <!-- Tabs Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 px-6 sm:px-8">
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
                    @include('pages.ar-invoice.partials.tab-header')
                </div>
                <div id="tab-lines" class="{{ $activeTab == 'lines' ? 'block' : 'hidden' }}">
                    @if(!$isNew)
                        @include('pages.ar-invoice.partials.tab-lines')
                    @endif
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
                    @php $cs = $invoice->docstatus ?? $draftStatus; @endphp
                    @if(in_array($cs, $completeFromStatuses))
                        <button onclick="processDocument('CO')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-green-200 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:border-green-800 dark:hover:bg-green-900/40 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-green-800 dark:text-green-300">Complete</div>
                                <div class="text-xs text-green-600 dark:text-green-400">Process and complete this invoice</div>
                            </div>
                        </button>
                    @endif
                    @if(in_array($cs, $voidFromStatuses))
                        <button onclick="processDocument('VO')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:border-red-800 dark:hover:bg-red-900/40 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-red-800 dark:text-red-300">Void</div>
                                <div class="text-xs text-red-600 dark:text-red-400">Void this invoice document</div>
                            </div>
                        </button>
                    @endif
                    @if(in_array($cs, $reverseFromStatuses))
                        <button onclick="processDocument('RC')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-orange-200 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:border-orange-800 dark:hover:bg-orange-900/40 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-orange-800 dark:text-orange-300">Reverse</div>
                                <div class="text-xs text-orange-600 dark:text-orange-400">Reverse this completed invoice</div>
                            </div>
                        </button>
                        @if(in_array($cs, $closeFromStatuses))
                            <button onclick="processDocument('CL')"
                                class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-800/80 transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-300">Close</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Close this invoice document</div>
                                </div>
                            </button>
                        @endif
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

    <!-- Invoice Print Modal -->
    <div id="invoicePrintModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm">
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-2 sm:pb-2 h-[85vh] flex flex-col">
                    <div class="flex-shrink-0 flex justify-between items-center pb-1 relative z-10" style="background-color: transparent;">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title"></h3>
                        <button onclick="closeInvoicePrintModal()" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                    <div class="flex-1 w-full relative bg-gray-100 rounded-lg overflow-hidden z-0">
                        <iframe id="invoicePrintFrame" src="" class="absolute inset-0 w-full h-full border-0"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <!-- Select2 -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- SweetAlert2 -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <style>
            /* Flatpickr Calendar z-index */
            .flatpickr-calendar {
                z-index: 9999 !important;
            }

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
        </style>

        <script>
        function initScripts() {
            $('#c_bpartner_id, #org_id, #doc_type_id, #c_currency_id, #c_paymentterm_id, #c_project_id, #c_tax_id, #ad_user_id').select2({
                width: '100%', placeholder: '- Select -'
            });
            $('#c_project_id').select2({ width: '100%', placeholder: '- None -', allowClear: true });
            $('#ad_user_id').select2({ width: '100%', placeholder: '- Select Contact -', allowClear: false });
        }

        document.addEventListener('DOMContentLoaded', function () {
            initScripts();
            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');
            if (tabFromUrl && ['header', 'lines', 'attachments', 'journals'].includes(tabFromUrl)) {
                switchTab(tabFromUrl, true);
            }
            
            // Initialization for Due Date Calculation
            if(document.getElementById('invoice_date')) {
                document.getElementById('invoice_date').addEventListener('change', calculateDueDate);
            }
            if(document.getElementById('due_date')) {
                document.getElementById('due_date').addEventListener('change', calculateDueDate);
            }
            $('#c_paymentterm_id').on('change', calculateDueDate);
            calculateDueDate(); // Initial calculation if fields are populated
            
            // Load customer contacts when customer changes
            $('#c_bpartner_id').on('change', function() {
                loadCustomerContacts($(this).val());
            });
        });

        function parseDateByFormat(value, format) {
            if (!value) return null;

            const formatTokens = format.split(/[^A-Za-z]+/).filter(Boolean);
            const valueTokens = value.split(/[^0-9]+/).filter(Boolean);

            if (formatTokens.length !== valueTokens.length) return null;

            let day;
            let month;
            let year;

            formatTokens.forEach((token, idx) => {
                const numeric = parseInt(valueTokens[idx], 10);
                if (Number.isNaN(numeric)) {
                    return;
                }

                switch (token.toLowerCase()) {
                    case 'y':
                        year = valueTokens[idx].length === 2 ? 2000 + numeric : numeric;
                        break;
                    case 'm':
                    case 'n':
                        month = numeric;
                        break;
                    case 'd':
                    case 'j':
                        day = numeric;
                        break;
                    default:
                        break;
                }
            });

            if (!year || !month || !day) return null;

            const parsed = new Date(year, month - 1, day);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        }

        function formatDateByFormat(dateObj, format) {
            const pad = (num) => String(num).padStart(2, '0');
            const replacements = {
                Y: dateObj.getFullYear(),
                y: String(dateObj.getFullYear()).slice(-2),
                m: pad(dateObj.getMonth() + 1),
                n: dateObj.getMonth() + 1,
                d: pad(dateObj.getDate()),
                j: dateObj.getDate(),
            };

            return format.replace(/[Yymndj]/g, (token) => replacements[token] ?? token);
        }

        function getDateFromPicker(input) {
            if (!input) return null;
            if (input._flatpickr && input._flatpickr.selectedDates.length) {
                return input._flatpickr.selectedDates[0];
            }

            const format = input.dataset.dateFormat || 'Y-m-d';
            return parseDateByFormat(input.value, format);
        }

        function setPickerDate(input, dateObj) {
            if (!input || !dateObj) return;
            if (input._flatpickr) {
                input._flatpickr.setDate(dateObj, true);
            } else {
                const format = input.dataset.dateFormat || 'Y-m-d';
                input.value = formatDateByFormat(dateObj, format);
            }
        }

        function calculateDueDate() {
            const invoiceInput = document.getElementById('invoice_date');
            const termSelect = document.getElementById('c_paymentterm_id');
            const dueDateInput = document.getElementById('due_date');

            if (!invoiceInput || !termSelect || !dueDateInput) return;

            const selectedOption = termSelect.options[termSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) return;

            const netDays = parseInt(selectedOption.getAttribute('data-netdays') || '0', 10);
            const invoiceDate = getDateFromPicker(invoiceInput);

            if (!invoiceDate || Number.isNaN(netDays)) return;

            const dueDate = new Date(invoiceDate.getTime());
            dueDate.setDate(dueDate.getDate() + netDays);

            setPickerDate(dueDateInput, dueDate);
        }

        function loadCustomerContacts(customerId) {
            const contactSelect = $('#ad_user_id');
            
            // Clear current options
            contactSelect.empty().append('<option value="">- Select Contact -</option>');
            
            if (!customerId) {
                contactSelect.trigger('change');
                return;
            }
            
            // Fetch customer contacts
            axios.get('{{ route("ar-invoice.api.customer-contacts") }}', {
                params: { customer_id: customerId }
            })
            .then(response => {
                const contacts = response.data.results;
                let minId = null;
                
                contacts.forEach(contact => {
                    const option = new Option(contact.text, contact.id, false, false);
                    contactSelect.append(option);
                    
                    // Track smallest ID
                    if (minId === null || contact.id < minId) {
                        minId = contact.id;
                    }
                });
                
                // Set default to smallest ID if contacts exist
                if (minId !== null) {
                    contactSelect.val(minId).trigger('change');
                } else {
                    contactSelect.trigger('change');
                }
            })
            .catch(error => {
                console.error('Error loading customer contacts:', error);
            });
        }

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

            if (!skipUrlUpdate) {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.pushState({}, '', url.toString());
            }

            const isNew = {{ $isNew ? 'true' : 'false' }};
            if (!isNew) loadTabContent(tab);
        }

        function loadTabContent(tabName) {
            const container = document.getElementById('tab-' + tabName);
            if (!container) return;

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
            url.searchParams.set('_t', Date.now());

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

        // Header Submit
        function submitHeader() {
            const isNew = {{ $isNew ? 'true' : 'false' }};
            const payload = {
                org_id:           $('#org_id').val(),
                c_bpartner_id:    $('#c_bpartner_id').val(),
                ad_user_id:       $('#ad_user_id').val() || null,
                invoice_date:     document.getElementById('invoice_date')?.value,
                due_date:         document.getElementById('due_date')?.value,
                date_acct:        document.getElementById('date_acct')?.value,
                doc_type_id:      $('#doc_type_id').val(),
                c_currency_id:    $('#c_currency_id').val(),
                c_paymentterm_id: $('#c_paymentterm_id').val(),
                po_reference:     document.getElementById('po_reference')?.value,
                description:      document.getElementById('description')?.value,
                c_project_id:     $('#c_project_id').val() || null,
                c_tax_id:         $('#c_tax_id').val() || null,
                _token:           '{{ csrf_token() }}'
            };

            if (isNew) {
                axios.post('{{ route("ar-invoice.store") }}', payload)
                    .then(res => {
                        const encId = res.data.data.encrypted_id;
                        Swal.fire({ icon: 'success', title: 'Created!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => {
                                window.location.href = '{{ route("ar-invoice.index") }}?document_id=' + encodeURIComponent(encId);
                            });
                    })
                    .catch(err => {
                        const data = err.response?.data;
                        const msg = data?.message || (data?.errors ? JSON.stringify(data.errors) : null) || 'Failed to create invoice.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    });
            } else {
                axios.put('{{ route("ar-invoice.update", ":id") }}'.replace(':id', '{{ $invoice?->c_invoice_id }}'), payload)
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Saved!', text: res.data.message, timer: 1500, showConfirmButton: false });
                    })
                    .catch(err => {
                        const msg = err.response?.data?.message || 'Failed to save changes.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    });
            }
        }

        // Document Action Modal
        function openDocumentActionModal() { document.getElementById('documentActionModal')?.classList.remove('hidden'); }
        function closeDocumentActionModal() { document.getElementById('documentActionModal')?.classList.add('hidden'); }

        function processDocument(action) {
            closeDocumentActionModal();
            const actionNames = { CO: 'Complete', VO: 'Void', RC: 'Reverse', CL: 'Close' };
            Swal.fire({
                title: actionNames[action] + ' Invoice?',
                text: 'Are you sure you want to ' + actionNames[action].toLowerCase() + ' this invoice?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: action === 'CO' ? '#16a34a' : action === 'VO' ? '#dc2626' : '#d97706',
                confirmButtonText: 'Yes, ' + actionNames[action],
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.post('{{ route("ar-invoice.process") }}', {
                        document_id: '{{ $docIdParam }}',
                        doc_action:  action,
                        _token:      '{{ csrf_token() }}'
                    })
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Done!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => window.location.reload());
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Action failed.' });
                    });
                }
            });
        }

        function deleteInvoice() {
            Swal.fire({
                title: 'Delete Invoice?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.delete('{{ route("ar-invoice.delete") }}', {
                        data: { document_id: '{{ $docIdParam }}', _token: '{{ csrf_token() }}' }
                    })
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => { window.location.href = '{{ route("ar-invoice.index") }}'; });
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Delete failed.' });
                    });
                }
            });
        }

        // Number formatting helpers
        function formatNumber(input) {
            const raw = input.value.replace(/[^0-9.]/g, '');
            const num = parseFloat(raw);
            input.value = isNaN(num) ? '' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function parseFormattedNumber(str) {
            return parseFloat((str || '0').replace(/,/g, '')) || 0;
        }
        function calculateLineTotal() {
            const qty   = parseFormattedNumber(document.getElementById('line_qty')?.value);
            const price = parseFormattedNumber(document.getElementById('line_price')?.value);
            const total = qty * price;
            const el    = document.getElementById('line_amount');
            if (el) el.value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Attachment helpers
        function initAttachmentScripts() {
            const docIdParam = '{{ $docIdParam }}';

            window.handleFileUpload = function(files) {
                if (!files || files.length === 0) return;
                const formData = new FormData();
                formData.append('file', files[0]);
                formData.append('document_id', docIdParam);
                formData.append('_token', '{{ csrf_token() }}');

                Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                axios.post('{{ route("ar-invoice.attachment.upload") }}', formData, {
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
                    title: 'Delete Attachment?', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Delete'
                }).then(result => {
                    if (result.isConfirmed) {
                        axios.delete('{{ route("ar-invoice.attachment.delete") }}', {
                            data: { document_id: docIdParam, attachment_id: attId, _token: '{{ csrf_token() }}' }
                        }).then(() => {
                            loadTabContent('attachments');
                        }).catch(err => {
                            Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Delete failed' });
                        });
                    }
                });
            };

            window.openAttachmentPreview = function(url) {
                const modal = document.getElementById('printModal');
                if (!modal) return;
                document.getElementById('printFrame').src = url;
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            };

            window.toggleSelectAll = cb => {
                document.querySelectorAll('.attachment-checkbox').forEach(c => c.checked = cb.checked);
                updateDeleteButtonState();
            };

            window.updateDeleteButtonState = () => {
                const checked = document.querySelectorAll('.attachment-checkbox:checked').length;
                document.getElementById('btnDeleteSelected')?.classList.toggle('hidden', checked === 0);
            };

            window.deleteSelectedAttachments = function() {
                const ids = [...document.querySelectorAll('.attachment-checkbox:checked')].map(c => c.value);
                if (!ids.length) return;
                Swal.fire({
                    title: 'Delete ' + ids.length + ' attachment(s)?', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Delete All'
                }).then(result => {
                    if (result.isConfirmed) {
                        Promise.all(ids.map(id =>
                            axios.delete('{{ route("ar-invoice.attachment.delete") }}', {
                                data: { document_id: docIdParam, attachment_id: id, _token: '{{ csrf_token() }}' }
                            })
                        )).then(() => loadTabContent('attachments'));
                    }
                });
            };
        }

        // Invoice Print Modal Functions
        window.openInvoicePrintModal = function(url) {
            const modal = document.getElementById('invoicePrintModal');
            const iframe = document.getElementById('invoicePrintFrame');
            iframe.src = url;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeInvoicePrintModal = function() {
            const modal = document.getElementById('invoicePrintModal');
            const iframe = document.getElementById('invoicePrintFrame');
            modal.classList.add('hidden');
            iframe.src = 'about:blank';
            document.body.style.overflow = '';
        };

        // Attachment Preview modal
        window.closePrintModal = function() {
            const modal = document.getElementById('printModal');
            if (!modal) return;
            modal.classList.add('hidden');
            document.getElementById('printFrame').src = 'about:blank';
            document.body.style.overflow = '';
        };
        </script>

        <!-- Preview Modal -->
        <div id="printModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm bg-gray-500/30">
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full dark:bg-gray-800">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 h-[85vh] flex flex-col">
                        <div class="flex-shrink-0 flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Attachment Preview</h3>
                            <button onclick="closePrintModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none bg-transparent border-0">
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
        <script>
        // ====================================================
        // REQUISITION MODAL — AR Invoice
        // ====================================================
        let shipmentCurrentPage  = 1;
        let shipmentSearchTimer  = null;
        let shipmentSelectedItems = []; // Changed to array for multi-select
        let shipmentCurrentResults = []; // Keep track of current page's results for select-all

        window.openShipmentModal = function () {
            const modal = document.getElementById('shipmentSelectionModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Start fresh
            shipmentSelectedItems = [];
            shipmentCurrentResults = [];
            document.getElementById('shipment_selected_count').textContent = 'No line selected';
            document.getElementById('shipment_confirm_btn').disabled = true;
            document.getElementById('shipment_search_input').value = '';
            document.getElementById('shipment_empty').classList.remove('hidden');
            document.getElementById('shipment_table').style.display = 'none';
        };

        window.closeShipmentModal = function () {
            const modal = document.getElementById('shipmentSelectionModal');
            if (modal) modal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.debounceShipmentSearch = function (value) {
            clearTimeout(shipmentSearchTimer);
            shipmentSearchTimer = setTimeout(() => loadShipmentLines(1), 400);
        };

        window.loadShipmentLines = function (page) {
            shipmentCurrentPage = page;
            const search = document.getElementById('shipment_search_input').value.trim();

            document.getElementById('shipment_loading').classList.remove('hidden');
            document.getElementById('shipment_empty').classList.add('hidden');
            document.getElementById('shipment_table').style.display = 'none';
            document.getElementById('shipment_pagination_info').innerHTML = '&nbsp;';
            document.getElementById('shipment_pagination_btns').innerHTML = '';

            const docId = '{{ $docIdParam ?? "" }}';
            fetch(`{{ route('ar-invoice.api.shipment-lines') }}?q=${encodeURIComponent(search)}&page=${page}&per_page={{ $shipmentLinesPerPage }}&document_id=${encodeURIComponent(docId)}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('shipment_loading').classList.add('hidden');
                    const results = data.results || [];
                    shipmentCurrentResults = results; // Store for select-all
                    if (results.length === 0) {
                        document.getElementById('shipment_empty').classList.remove('hidden');
                        document.getElementById('shipment_empty').querySelector('p').textContent =
                            search.length < 2 ? 'Type at least 2 characters to search.' : 'No shipment lines found.';
                        return;
                    }
                    document.getElementById('shipment_table').style.display = '';
                    renderShipmentTable(results);
                    renderShipmentPagination(data.pagination || {}, page, data.total, data.per_page);
                })
                .catch(err => {
                    document.getElementById('shipment_loading').classList.add('hidden');
                    document.getElementById('shipment_empty').classList.remove('hidden');
                    console.error('Failed to load shipment lines:', err);
                });
        };

        function renderShipmentTable(results) {
            const tbody = document.getElementById('shipment_table_body');
            tbody.innerHTML = '';
            
            // Update select-all checkbox state based on if everything on page is selected
            const selectAllCb = document.getElementById('shipment_select_all');
            if (selectAllCb) {
                selectAllCb.checked = results.length > 0 && results.every(item => shipmentSelectedItems.some(i => i.id === item.id));
            }

            results.forEach(item => {
                const isSelected = shipmentSelectedItems.some(i => i.id === item.id);
                const tr = document.createElement('tr');
                tr.className = `hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors cursor-pointer ${isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : ''}`;
                tr.dataset.id = item.id;
                tr.onclick = function () { selectShipmentRow(this, item); };
                tr.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="checkbox" class="shipment-row-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            data-id="${item.id}" ${isSelected ? 'checked' : ''}
                            onchange="onShipmentCheckboxChange(this, ${JSON.stringify(item).replace(/"/g, '&quot;')})"
                            onclick="event.stopPropagation()">
                    </td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                        ${escShipment(item.shipment_no)}
                        <br><small class="text-xs text-gray-500 font-mono mt-0.5">${item.poreference ? escShipment(item.poreference) : '-'}</small>
                    </td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                        ${escShipment(item.product_name)}<br>
                        <small class="text-[11px] text-gray-400 font-mono font-normal">${escShipment(item.product_code)}</small>
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-mono text-gray-700 dark:text-gray-300">${parseFloat(item.qty).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                    <td class="px-4 py-3 text-right text-sm font-mono text-gray-700 dark:text-gray-300">${parseFloat(item.remaining_qty).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        window.selectShipmentRow = function (tr, item) {
            const cb = tr.querySelector('.shipment-row-checkbox');
            if (cb) {
                cb.checked = !cb.checked;
                window.onShipmentCheckboxChange(cb, item);
            }
        };

        window.onShipmentCheckboxChange = function (checkbox, item) {
            if (checkbox.checked) {
                checkbox.closest('tr').classList.add('bg-blue-50','dark:bg-blue-900/20');
                if (!shipmentSelectedItems.some(i => i.id === item.id)) {
                    shipmentSelectedItems.push(item);
                }
            } else {
                checkbox.closest('tr').classList.remove('bg-blue-50','dark:bg-blue-900/20');
                shipmentSelectedItems = shipmentSelectedItems.filter(i => i.id !== item.id);
            }
            
            // Check if all on current page are selected -> update header checkbox
            const selectAllCb = document.getElementById('shipment_select_all');
            if (selectAllCb && shipmentCurrentResults.length > 0) {
                selectAllCb.checked = shipmentCurrentResults.every(resItem => shipmentSelectedItems.some(i => i.id === resItem.id));
            }

            updateShipmentSelectionUI();
        };

        window.onShipmentSelectAllChange = function (checkbox) {
            const isChecked = checkbox.checked;
            
            if (isChecked) {
                // Add all visible lines that aren't already selected
                shipmentCurrentResults.forEach(item => {
                    if (!shipmentSelectedItems.some(i => i.id === item.id)) {
                        shipmentSelectedItems.push(item);
                    }
                });
            } else {
                // Remove all visible lines from the selection
                const visibleIds = shipmentCurrentResults.map(item => item.id);
                shipmentSelectedItems = shipmentSelectedItems.filter(i => !visibleIds.includes(i.id));
            }

            // Re-render table UI based on new selection state
            renderShipmentTable(shipmentCurrentResults);
            updateShipmentSelectionUI();
        };

        function updateShipmentSelectionUI() {
            const countEl = document.getElementById('shipment_selected_count');
            const confirmBtn = document.getElementById('shipment_confirm_btn');
            if (shipmentSelectedItems.length > 0) {
                countEl.innerHTML = `<span class="font-medium text-blue-700 dark:text-blue-400">${shipmentSelectedItems.length} line(s) selected</span>`;
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Add ${shipmentSelectedItems.length} Selected`;
            } else {
                countEl.textContent = 'No lines selected';
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Add Selected`;
            }
        }

        function renderShipmentPagination(pagination, currentPage, total, perPage) {
            const infoEl = document.getElementById('shipment_pagination_info');
            const btnsEl = document.getElementById('shipment_pagination_btns');
            if (total && perPage) {
                const from = ((currentPage - 1) * perPage) + 1;
                const to   = Math.min(currentPage * perPage, total);
                infoEl.textContent = `Showing ${from}–${to} of ${total} results`;
            }
            btnsEl.innerHTML = '';
            const prevBtn = document.createElement('button');
            prevBtn.type = 'button'; prevBtn.disabled = currentPage <= 1;
            prevBtn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors';
            prevBtn.textContent = '← Prev'; prevBtn.onclick = () => loadShipmentLines(currentPage - 1);
            btnsEl.appendChild(prevBtn);
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button'; nextBtn.disabled = !pagination.more;
            nextBtn.className = 'px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors';
            nextBtn.textContent = 'Next →'; nextBtn.onclick = () => loadShipmentLines(currentPage + 1);
            btnsEl.appendChild(nextBtn);
        }

        function escShipment(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        window.confirmShipmentAdd = async function () {
            if (shipmentSelectedItems.length === 0) {
                Swal.fire('No Selection','Please select at least one shipment line.','warning');
                return;
            }
            closeShipmentModal();
            Swal.fire({
                title: 'Adding Lines...',
                html: `Processing 1 of ${shipmentSelectedItems.length}`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            // Loop and save each one directly to the backend
            let successCount = 0;
            let errorCount = 0;

            const storeUrl = '{{ route("ar-invoice.line.store") }}';
            const csrfToken = '{{ csrf_token() }}';
            const docId = '{{ $encDocId ?? request("document_id") ?? "" }}';

            for (let i = 0; i < shipmentSelectedItems.length; i++) {
                const item = shipmentSelectedItems[i];
                Swal.getHtmlContainer().textContent = `Processing ${i + 1} of ${shipmentSelectedItems.length}...`;

                const taxIdField = document.getElementById('c_tax_id');
                const taxId = taxIdField ? taxIdField.value : '';

                const payload = {
                    _token: csrfToken,
                    document_id: docId,
                    line_id: '',
                    m_inoutline_id: item.id,
                    c_orderline_id: item.c_orderline_id || '',
                    m_product_id: item.m_product_id,
                    qty: item.qty.toString(),
                    unit_price: item.unit_price ? item.unit_price.toString() : '0',
                    c_tax_id: taxId,
                    description: `Added from Shipment: ${item.shipment_no}`
                };

                try {
                    const res = await fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        successCount++;
                    } else {
                        console.error('Failed to add line.', await res.text());
                        errorCount++;
                    }
                } catch (e) {
                    console.error('Network error.', e);
                    errorCount++;
                }
            }

            // Finished processing all
            if (errorCount > 0) {
                Swal.fire('Warning', `Finished with ${errorCount} error(s). ${successCount} lines added.`, 'warning').then(() => {
                    if (typeof loadTabContent === 'function') loadTabContent('lines');
                    shipmentSelectedItems = [];
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Added!',
                    text: `Successfully added ${successCount} shipment lines.`,
                    timer: 1500,
                    showConfirmButton: false

                }).then(() => {
                    if (typeof loadTabContent === 'function') loadTabContent('lines');
                    shipmentSelectedItems = [];
                    // Ensure the select all checkbox is cleared visually
                    const selectAllCb = document.getElementById('shipment_select_all');
                    if (selectAllCb) selectAllCb.checked = false;
                });
            }
        };

        window.clearShipmentLink = function () {
            const inoutField = document.getElementById('m_inoutline_id');
            if (inoutField) inoutField.value = '';
            const orderField = document.getElementById('c_orderline_id');
            if (orderField) orderField.value = '';
            const infoDiv = document.getElementById('shipment_info_container');
            if (infoDiv) infoDiv.classList.add('hidden');
            const productSelect = $('#m_product_id');
            if (productSelect.length) {
                productSelect.next('.select2-container').css({'pointer-events':'','opacity':''});
                productSelect.attr('name','m_product_id');
            }
            $('#m_product_id_req_backup').remove();
            document.querySelectorAll('.shipment-lock-badge').forEach(el => el.remove());
        };

    </script>
    @endpush

@endsection
