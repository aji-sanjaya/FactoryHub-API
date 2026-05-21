@extends('layouts.app')

@section('content')

    @php
        $apPaymentConfig = config('idempiere.ap-payment');
        $statusConfig = $apPaymentConfig['statuses'];
        $workflowConfig = $apPaymentConfig['workflow'];
        $isNew      = is_null($payment);
        $activeTab  = $activeTab ?? 'header';
        $docNo      = $isNew ? '** New **' : $payment->documentno;
        $docIdParam = request('document_id');
        $isReadOnly = !$isNew && in_array($payment->docstatus, $statusConfig['read_only']);
    @endphp

    <div>
        {{-- Breadcrumb & Title --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('ap-payment.index') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $isNew ? 'New AP Payment' : $docNo }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                        @if(!$isNew)
                            @php
                                $hColor = $statusConfig['header_badge_classes'][$payment->docstatus] ?? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $hColor }}">{{ $status }}</span>
                            <span class="text-gray-300">•</span>
                        @endif
                        <span>{{ $isNew ? 'Create a new vendor payment' : 'Manage payment details' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                {{-- Header Save Button --}}
                <div id="header-actions" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @if(!$isReadOnly)
                        <button onclick="submitHeader()"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm hover:shadow transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $isNew ? 'Create Payment' : 'Save Changes' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main Card with Tabs --}}
        <div class="bg-white border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 overflow-visible relative rounded-t-2xl rounded-b-2xl">

            {{-- Tabs Header --}}
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 px-6 sm:px-8">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="#" onclick="switchTab('header'); return false;" id="nav-header"
                        class="{{ $activeTab == 'header' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Header Details
                    </a>
                    @if(!$isNew)
                    <a href="#" onclick="switchTab('allocate'); return false;" id="nav-allocate"
                        class="{{ $activeTab == 'allocate' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        Allocate
                    </a>
                    @endif
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

            {{-- Tab Content --}}
            <div id="tab-content-wrapper">
                <div id="tab-header" class="{{ $activeTab == 'header' ? 'block' : 'hidden' }}">
                    @include('pages.ap-payment.partials.tab-header')
                </div>
                <div id="tab-attachments" class="{{ $activeTab == 'attachments' ? 'block' : 'hidden' }}">
                </div>
                @if(!$isNew)
                <div id="tab-allocate" class="{{ $activeTab == 'allocate' ? 'block' : 'hidden' }}">
                </div>
                @endif
                @if(!$isDraft && !$isNew)
                <div id="tab-journals" class="{{ $activeTab == 'journals' ? 'block' : 'hidden' }}">
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Document Action Modal --}}
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
                    @php $cs = $payment->docstatus ?? 'DR'; @endphp
                    @if(in_array($cs, $workflowConfig['complete_from']))
                        <button onclick="processDocument('CO')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-green-200 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:border-green-800 dark:hover:bg-green-900/40 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-green-800 dark:text-green-300">{{ $workflowConfig['action_labels']['CO'] }}</div>
                                <div class="text-xs text-green-600 dark:text-green-400">{{ $workflowConfig['button_descriptions']['CO'] }}</div>
                            </div>
                        </button>
                    @endif
                    @if(in_array($cs, $workflowConfig['void_from']))
                        <button onclick="processDocument('VO')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:border-red-800 dark:hover:bg-red-900/40 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-red-800 dark:text-red-300">{{ $workflowConfig['action_labels']['VO'] }}</div>
                                <div class="text-xs text-red-600 dark:text-red-400">{{ $workflowConfig['button_descriptions']['VO'] }}</div>
                            </div>
                        </button>
                    @endif
                    @if(in_array($cs, $workflowConfig['reverse_from']))
                        <button onclick="processDocument('RC')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-orange-200 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:border-orange-800 dark:hover:bg-orange-900/40 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-orange-800 dark:text-orange-300">{{ $workflowConfig['action_labels']['RC'] }}</div>
                                <div class="text-xs text-orange-600 dark:text-orange-400">{{ $workflowConfig['button_descriptions']['RC'] }}</div>
                            </div>
                        </button>
                    @endif
                    @if(in_array($cs, $workflowConfig['close_from']))
                        <button onclick="processDocument('CL')"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-800/80 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-800 dark:text-gray-300">{{ $workflowConfig['action_labels']['CL'] }}</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $workflowConfig['button_descriptions']['CL'] }}</div>
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

    {{-- Attachment Preview Modal --}}
    <div id="printModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm bg-gray-500/30">
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full dark:bg-gray-800">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 h-[85vh] flex flex-col">
                    <div class="flex-shrink-0 flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Attachment Preview</h3>
                        <button onclick="closePrintModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none bg-transparent border-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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
        {{-- Select2 --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        {{-- SweetAlert2 --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <style>
            .flatpickr-calendar { z-index: 9999 !important; }
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
            $('#c_bpartner_id, #org_id, #doc_type_id, #c_currency_id, #payment_rule, #c_bankaccount_id').select2({
                width: '100%', placeholder: '- Select -'
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            initScripts();
            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');
            if (tabFromUrl && ['header', 'attachments', 'allocate', 'journals'].includes(tabFromUrl)) {
                switchTab(tabFromUrl, true);
            }
        });

        function switchTab(tab, skipUrlUpdate) {
            const tabs = ['header', 'attachments', 'allocate', 'journals'];
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
                    if (tabName === 'allocate') initAllocateScripts();
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

        function submitHeader() {
            const isNew = {{ $isNew ? 'true' : 'false' }};
            const payload = {
                org_id:           $('#org_id').val(),
                c_bpartner_id:    $('#c_bpartner_id').val(),
                payment_date:     document.getElementById('payment_date')?.value,
                date_acct:        document.getElementById('date_acct')?.value,
                doc_type_id:      $('#doc_type_id').val(),
                c_currency_id:    $('#c_currency_id').val(),
                payment_rule:     $('#payment_rule').val(),
                c_bankaccount_id: $('#c_bankaccount_id').val() || null,
                pay_amt:          document.getElementById('pay_amt')?.value?.replace(/,/g, '') || '0',
                description:      document.getElementById('description')?.value,
                _token:           '{{ csrf_token() }}'
            };

            if (isNew) {
                axios.post('{{ route("ap-payment.store") }}', payload)
                    .then(res => {
                        const encId = res.data.data.encrypted_id;
                        Swal.fire({ icon: 'success', title: 'Created!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => {
                                window.location.href = '{{ route("ap-payment.index") }}?document_id=' + encodeURIComponent(encId);
                            });
                    })
                    .catch(err => {
                        const data = err.response?.data;
                        const msg = data?.message || (data?.errors ? JSON.stringify(data.errors) : null) || 'Failed to create payment.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    });
            } else {
                axios.put('{{ route("ap-payment.update", ":id") }}'.replace(':id', '{{ $payment?->c_payment_id }}'), payload)
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Saved!', text: res.data.message, timer: 1500, showConfirmButton: false });
                    })
                    .catch(err => {
                        const msg = err.response?.data?.message || 'Failed to save changes.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    });
            }
        }

        function openDocumentActionModal() { document.getElementById('documentActionModal')?.classList.remove('hidden'); }
        function closeDocumentActionModal() { document.getElementById('documentActionModal')?.classList.add('hidden'); }

        function processDocument(action) {
            closeDocumentActionModal();
            const actionNames = @json($workflowConfig['action_labels']);
            const confirmationMessages = @json($workflowConfig['confirmation_messages']);
            Swal.fire({
                title: actionNames[action] + ' Payment?',
                text: confirmationMessages[action] || ('Are you sure you want to ' + actionNames[action].toLowerCase() + ' this payment?'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: action === 'CO' ? '#16a34a' : action === 'VO' ? '#dc2626' : '#d97706',
                confirmButtonText: 'Yes, ' + actionNames[action],
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.post('{{ route("ap-payment.process") }}', {
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

        function deletePayment() {
            Swal.fire({
                title: 'Delete Payment?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.delete('{{ route("ap-payment.delete") }}', {
                        data: { document_id: '{{ $docIdParam }}', _token: '{{ csrf_token() }}' }
                    })
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: res.data.message, timer: 1500, showConfirmButton: false })
                            .then(() => { window.location.href = '{{ route("ap-payment.index") }}'; });
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Delete failed.' });
                    });
                }
            });
        }

        function formatNumber(input) {
            const raw = input.value.replace(/[^0-9.]/g, '');
            const num = parseFloat(raw);
            input.value = isNaN(num) ? '' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function initAllocateScripts() {
            const docIdParam = '{{ $docIdParam }}';
            const CSRF = '{{ csrf_token() }}';
            const SEARCH_URL = '{{ route("ap-payment.api.open-invoices") }}';
            const ALLOCATE_URL = '{{ route("ap-payment.allocate.store") }}';
            const DELETE_URL = '{{ route("ap-payment.allocate.delete") }}';
            const OPEN_INVOICE_SEARCH_MIN_CHARS = {{ $apPaymentConfig['limits']['open_invoice_search_min_chars'] }};

            let invSearchTimer = null;
            let invCurrentPage = 1;
            let invSearchQuery = '';

            // ── Number formatting helpers ──
            window.qtyFormat = function (input) {
                let v = (input.value || '').replace(/,/g, '');
                let raw = parseFloat(v);
                if (!isNaN(raw)) {
                    input.value = raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else {
                    input.value = '0.00';
                }
            };

            window.qtyUnformat = function (input) {
                input.value = (input.value || '').replace(/,/g, '');
            };

            function parseAmt(val) {
                let n = parseFloat((val || '').replace(/,/g, ''));
                return isNaN(n) ? 0 : n;
            }

            // ── Calculations ──
            window.calcRemainingAmt = function () {
                const openAmt = parseAmt(document.getElementById('inv_open_amt')?.value || 0);
                const amt = parseAmt(document.getElementById('alloc_amount')?.value || 0);
                const disc = parseAmt(document.getElementById('alloc_discount')?.value || 0);
                const writeoff = parseAmt(document.getElementById('alloc_writeoff')?.value || 0);
                const overunder = parseAmt(document.getElementById('alloc_overunder')?.value || 0);

                const remaining = openAmt - (amt + disc + writeoff + overunder);
                const remInput = document.getElementById('alloc_remaining');
                if (remInput) remInput.value = remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            // ── Forms Show/Hide ──
            window.showCreateAllocForm = function () {
                document.getElementById('allocate-table-section')?.classList.add('hidden');
                document.getElementById('allocate-create-form')?.classList.remove('hidden');
                document.getElementById('allocate-create-form')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            };

            window.hideCreateAllocForm = function () {
                document.getElementById('allocate-create-form')?.classList.add('hidden');
                document.getElementById('allocate-table-section')?.classList.remove('hidden');
                
                // Reset edit mode
                window.editingAllocId = undefined;
                
                // Reset form title and button text
                const formTitle = document.getElementById('alloc-form-title');
                if (formTitle) formTitle.innerHTML = '<div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg></div>New Allocation Line';
                
                const saveBtn = document.getElementById('btn_save_alloc');
                if (saveBtn) saveBtn.innerHTML = '<svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>Save Allocation';
            };

            // ── Modal Open / Close ──
            window.openInvoiceSelectModal = function () {
                const searchInput = document.getElementById('inv_search_input');
                const modal = document.getElementById('invoiceSelectModal');
                const tbody = document.getElementById('inv_table_body');
                
                if (!modal) {
                    console.error('Invoice select modal not found');
                    return;
                }
                
                if (searchInput) searchInput.value = '';
                invSearchQuery = '';
                
                // Show initial instruction message
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Search to find invoices</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Enter at least ${OPEN_INVOICE_SEARCH_MIN_CHARS} characters in the search box above</p>
                    </td></tr>`;
                }
                
                document.getElementById('inv_pagination_info').innerHTML = '';
                document.getElementById('inv_pagination_btns').innerHTML = '';
                
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            window.closeInvoiceSelectModal = function () {
                const modal = document.getElementById('invoiceSelectModal');
                if (!modal) return;
                
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            // ── Search ──
            window.debounceInvSearch = function () {
                clearTimeout(invSearchTimer);
                invSearchTimer = setTimeout(() => {
                    invSearchQuery = document.getElementById('inv_search_input')?.value || '';
                    loadOpenInvoices(1);
                }, 400);
            };

            // ── Load Invoices ──
            function loadOpenInvoices(page) {
                invCurrentPage = page;
                const tbody = document.getElementById('inv_table_body');
                if (!tbody) return;
                
                // Require minimum 3 characters to search
                if (!invSearchQuery || invSearchQuery.trim().length < OPEN_INVOICE_SEARCH_MIN_CHARS) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Enter at least ${OPEN_INVOICE_SEARCH_MIN_CHARS} characters to search</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Type invoice number or keyword to find invoices</p>
                    </td></tr>`;
                    document.getElementById('inv_pagination_info').innerHTML = '';
                    document.getElementById('inv_pagination_btns').innerHTML = '';
                    return;
                }
                
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-xs text-gray-500">Loading...</td></tr>';

                axios.get(SEARCH_URL, {
                    params: { document_id: docIdParam, q: invSearchQuery, page }
                }).then(res => {
                    const data = res.data;

                    if (!data.data || data.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-xs text-gray-500">${invSearchQuery ? 'No matching invoices found.' : 'No open invoices found for this vendor.'}</td></tr>`;
                        document.getElementById('inv_pagination_info').innerHTML = '';
                        document.getElementById('inv_pagination_btns').innerHTML = '';
                        return;
                    }

                    tbody.innerHTML = data.data.map(inv => {
                        const openAmt = parseFloat(inv.open_amt || 0);
                        const isFullyAllocated = openAmt <= 0;
                        
                        return `
                        <tr class="${isFullyAllocated ? 'opacity-50 bg-gray-50 dark:bg-gray-800/30' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'} transition-colors">
                            <td class="px-4 py-3 text-sm font-semibold ${isFullyAllocated ? 'text-gray-400 dark:text-gray-600' : 'text-gray-900 dark:text-white'}">
                                ${inv.documentno || '-'}
                                ${isFullyAllocated ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Fully Allocated</span>' : ''}
                            </td>
                            <td class="px-4 py-3 text-sm ${isFullyAllocated ? 'text-gray-400 dark:text-gray-600' : 'text-gray-500 dark:text-gray-400'}">${inv.dateinvoiced ? new Date(inv.dateinvoiced).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono ${isFullyAllocated ? 'text-gray-400 dark:text-gray-600' : 'text-gray-600 dark:text-gray-300'}">${parseFloat(inv.grandtotal).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-bold ${isFullyAllocated ? 'text-gray-400 dark:text-gray-600' : 'text-orange-600 dark:text-orange-400'}">${openAmt.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                            <td class="px-4 py-3 text-center">
                                ${isFullyAllocated 
                                    ? '<button disabled class="px-3 py-1.5 text-[11px] font-medium text-gray-400 bg-gray-200 rounded cursor-not-allowed dark:bg-gray-700 dark:text-gray-600" title="No balance available">Select</button>'
                                    : `<button onclick="selectInvoice(${JSON.stringify(inv).replace(/"/g, '&quot;')})" class="px-3 py-1.5 text-[11px] font-medium text-white bg-brand-600 hover:bg-brand-700 rounded transition-colors">Select</button>`
                                }
                            </td>
                        </tr>
                    `;
                    }).join('');

                    document.getElementById('inv_pagination_info').textContent =
                        `Showing ${data.from}–${data.to} of ${data.total} invoices`;
                    renderInvPagination(data.current_page, data.last_page);

                }).catch(err => {
                    tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-xs text-red-500">Error loading invoices.</td></tr>';
                });
            }
            window.loadOpenInvoices = loadOpenInvoices;

            function renderInvPagination(current, last) {
                const btns = document.getElementById('inv_pagination_btns');
                if (!btns) return;
                btns.innerHTML = '';
                if (last <= 1) return;

                const prev = document.createElement('button');
                prev.textContent = '← Prev';
                prev.className = 'px-3 py-1.5 text-xs border rounded-lg ' + (current <= 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700');
                prev.disabled = current <= 1;
                prev.onclick = () => loadOpenInvoices(current - 1);
                btns.appendChild(prev);

                const next = document.createElement('button');
                next.textContent = 'Next →';
                next.className = 'px-3 py-1.5 text-xs border rounded-lg ' + (current >= last ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-700');
                next.disabled = current >= last;
                next.onclick = () => loadOpenInvoices(current + 1);
                btns.appendChild(next);
            }

            // ── Select Invoice ──
            window.selectInvoice = function (inv) {
                window.closeInvoiceSelectModal();
                window.showCreateAllocForm();

                const c_invoice_id = document.getElementById('c_invoice_id');
                const disp_invoice_no = document.getElementById('disp_invoice_no');
                const disp_invoice_amt = document.getElementById('disp_invoice_amt');
                const inv_open_amt = document.getElementById('inv_open_amt');
                const inv_grandtotal = document.getElementById('inv_grandtotal');
                const disp_open_amt = document.getElementById('disp_open_amt');
                const alloc_amount = document.getElementById('alloc_amount');
                const alloc_discount = document.getElementById('alloc_discount');
                const alloc_writeoff = document.getElementById('alloc_writeoff');
                const alloc_overunder = document.getElementById('alloc_overunder');

                if (c_invoice_id) c_invoice_id.value = inv.c_invoice_id;
                if (disp_invoice_no) disp_invoice_no.value = inv.documentno;
                if (disp_invoice_amt) disp_invoice_amt.value = parseFloat(inv.grandtotal).toLocaleString('en-US', { minimumFractionDigits: 2 });

                const grandTotal = parseFloat(inv.grandtotal);
                const openAmt = parseFloat(inv.open_amt);
                if (inv_grandtotal) inv_grandtotal.value = grandTotal;
                if (inv_open_amt) inv_open_amt.value = openAmt;
                if (disp_open_amt) disp_open_amt.value = openAmt.toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (alloc_amount) alloc_amount.value = openAmt.toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (alloc_discount) alloc_discount.value = '0.00';
                if (alloc_writeoff) alloc_writeoff.value = '0.00';
                if (alloc_overunder) alloc_overunder.value = '0.00';

                window.calcRemainingAmt();
            };

            // ── Save Allocation ──
            window.saveAllocationLine = function () {
                const c_invoice_id = document.getElementById('c_invoice_id')?.value;
                const invoice_amt = parseAmt(document.getElementById('inv_grandtotal')?.value);
                const amount = parseAmt(document.getElementById('alloc_amount')?.value);
                const discount_amt = parseAmt(document.getElementById('alloc_discount')?.value);
                const writeoff_amt = parseAmt(document.getElementById('alloc_writeoff')?.value);
                const overunder_amt = parseAmt(document.getElementById('alloc_overunder')?.value);

                if (!c_invoice_id) return;
                if (amount <= 0 && discount_amt <= 0 && writeoff_amt <= 0) {
                    Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'At least one allocation amount must be > 0.' });
                    return;
                }

                const btn = document.getElementById('btn_save_alloc');
                if (!btn) return;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white inline mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';

                const payload = {
                    document_id: docIdParam,
                    c_invoice_id: c_invoice_id,
                    invoice_amt: invoice_amt,
                    amount: amount,
                    discount_amt: discount_amt,
                    writeoff_amt: writeoff_amt,
                    overunder_amt: overunder_amt,
                    _token: CSRF
                };

                // Check if we're editing an existing allocation
                const isEditing = window.editingAllocId !== undefined;
                const url = isEditing ? '{{ url("/ap-payment/allocate") }}/' + window.editingAllocId : ALLOCATE_URL;
                const method = isEditing ? 'put' : 'post';

                axios[method](url, payload).then(res => {
                    window.hideCreateAllocForm();
                    const successMsg = isEditing ? 'Allocation updated successfully.' : 'Invoice allocated successfully.';
                    Swal.fire({ icon: 'success', title: isEditing ? 'Updated!' : 'Allocated!', text: res.data.message || successMsg, timer: 1500, showConfirmButton: false })
                        .then(() => loadTabContent('allocate'));
                }).catch(err => {
                    Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Allocation failed.' });
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            };

            // ── Delete Allocation ──
            window.deleteAllocation = function (lineId) {
                Swal.fire({
                    title: 'Unlink Invoice?', text: 'This will remove the allocation from this payment.', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, remove it!', cancelButtonText: 'Cancel'
                }).then(r => { if (r.isConfirmed) performAllocDelete([lineId]); });
            };

            // ── Edit Allocation ──
            window.editAllocation = function (alloc) {
                window.showCreateAllocForm();

                const c_invoice_id = document.getElementById('c_invoice_id');
                const disp_invoice_no = document.getElementById('disp_invoice_no');
                const disp_invoice_amt = document.getElementById('disp_invoice_amt');
                const inv_open_amt = document.getElementById('inv_open_amt');
                const inv_grandtotal = document.getElementById('inv_grandtotal');
                const disp_open_amt = document.getElementById('disp_open_amt');
                const alloc_amount = document.getElementById('alloc_amount');
                const alloc_discount = document.getElementById('alloc_discount');
                const alloc_writeoff = document.getElementById('alloc_writeoff');
                const alloc_overunder = document.getElementById('alloc_overunder');

                // Set invoice info
                if (c_invoice_id) c_invoice_id.value = alloc.c_invoice_id;
                if (disp_invoice_no) disp_invoice_no.value = alloc.invoice_documentno;
                if (disp_invoice_amt) disp_invoice_amt.value = parseFloat(alloc.invoice_grandtotal).toLocaleString('en-US', { minimumFractionDigits: 2 });
                
                const grandTotal = parseFloat(alloc.invoice_grandtotal);
                if (inv_grandtotal) inv_grandtotal.value = grandTotal;
                
                // Calculate open amount (add back the current allocation amounts)
                const currentTotal = parseFloat(alloc.amount) + parseFloat(alloc.discountamt) + parseFloat(alloc.writeoffamt);
                const openAmt = currentTotal;
                if (inv_open_amt) inv_open_amt.value = openAmt;
                if (disp_open_amt) disp_open_amt.value = openAmt.toLocaleString('en-US', { minimumFractionDigits: 2 });

                // Set allocation amounts
                if (alloc_amount) alloc_amount.value = parseFloat(alloc.amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (alloc_discount) alloc_discount.value = parseFloat(alloc.discountamt).toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (alloc_writeoff) alloc_writeoff.value = parseFloat(alloc.writeoffamt).toLocaleString('en-US', { minimumFractionDigits: 2 });
                if (alloc_overunder) alloc_overunder.value = parseFloat(alloc.overunderamt).toLocaleString('en-US', { minimumFractionDigits: 2 });

                // Store edit mode
                window.editingAllocId = alloc.id;

                // Update form title and button
                const formTitle = document.getElementById('alloc-form-title');
                if (formTitle) formTitle.innerHTML = '<div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></div>Edit Allocation Line';
                
                const saveBtn = document.getElementById('btn_save_alloc');
                if (saveBtn) saveBtn.innerHTML = '<svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>Update Allocation';

                window.calcRemainingAmt();
            };

            window.deleteSelectedAllocations = function () {
                const ids = Array.from(document.querySelectorAll('.alloc-checkbox:checked')).map(c => c.value);
                if (!ids.length) return;
                Swal.fire({
                    title: `Unlink ${ids.length} Invoice(s)?`, text: 'This cannot be undone!', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, remove!'
                }).then(r => { if (r.isConfirmed) performAllocDelete(ids); });
            };

            function performAllocDelete(ids) {
                axios.delete(DELETE_URL, {
                    data: { line_ids: ids, document_id: docIdParam, _token: CSRF }
                }).then(res => {
                    Swal.fire({ icon: 'success', title: 'Unlinked!', text: res.data.message || 'Allocation(s) removed.', timer: 1500, showConfirmButton: false });
                    loadTabContent('allocate');
                }).catch(err => {
                    Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || err.message });
                });
            }

            window.toggleSelectAllAlloc = function (cb) {
                document.querySelectorAll('.alloc-checkbox').forEach(c => c.checked = cb.checked);
                window.updateAllocDeleteState();
            };

            window.updateAllocDeleteState = function () {
                const checked = document.querySelectorAll('.alloc-checkbox:checked');
                const btn = document.getElementById('deleteAllocSelectedBtn');
                const text = document.getElementById('deleteAllocSelectedText');
                if (checked.length > 0) {
                    if (btn) btn.style.display = 'inline-flex';
                    if (text) text.textContent = `Delete (${checked.length})`;
                } else {
                    if (btn) btn.style.display = 'none';
                }
                const all = document.querySelectorAll('.alloc-checkbox');
                const selAll = document.getElementById('selectAllAlloc');
                if (selAll) selAll.checked = all.length > 0 && all.length === checked.length;
            };
        }

        function initAttachmentScripts() {
            const docIdParam = '{{ $docIdParam }}';

            window.handleFileUpload = function(files) {
                if (!files || files.length === 0) return;
                const formData = new FormData();
                formData.append('file', files[0]);
                formData.append('document_id', docIdParam);
                formData.append('_token', '{{ csrf_token() }}');

                Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                axios.post('{{ route("ap-payment.attachment.upload") }}', formData, {
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
                        axios.delete('{{ route("ap-payment.attachment.delete") }}', {
                            data: { document_id: docIdParam, attachment_id: attId, _token: '{{ csrf_token() }}' }
                        }).then(() => loadTabContent('attachments'))
                          .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Delete failed' }));
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
                            axios.delete('{{ route("ap-payment.attachment.delete") }}', {
                                data: { document_id: docIdParam, attachment_id: id, _token: '{{ csrf_token() }}' }
                            })
                        )).then(() => loadTabContent('attachments'));
                    }
                });
            };
        }

        window.closePrintModal = function() {
            const modal = document.getElementById('printModal');
            if (!modal) return;
            modal.classList.add('hidden');
            document.getElementById('printFrame').src = 'about:blank';
            document.body.style.overflow = '';
        };
        </script>
    @endpush

@endsection
