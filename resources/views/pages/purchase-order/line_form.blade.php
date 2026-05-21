@extends('layouts.app')

@section('content')
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">
        @php
            $requisitionIdEncrypted = request()->query('document_id');
            // Assuming document_id is passed to view via Controller
        @endphp

        <!-- Header / Breadcrumb -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('requisition.index', ['document_id' => $document_id, 'tab' => 'lines']) }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                Back to Requisition
            </a>
            <div class="flex gap-3">
                <!-- Actions if needed -->
            </div>
        </div>

        <!-- Form Card with Tab-like appearance -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <a href="{{ route('requisition.index', ['document_id' => $document_id]) }}"
                        class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                        Header
                    </a>
                    <a href="{{ route('requisition.index', ['document_id' => $document_id, 'tab' => 'lines']) }}"
                        class="border-brand-500 text-brand-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium"
                        aria-current="page">
                        Requisition Lines
                    </a>
                </nav>
            </div>

            <!-- Content Area: Create Line Form -->
            <div class="p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">Create New Line</h3>

                <form action="{{ route('requisition.line.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="document_id" value="{{ $document_id }}">

                    <div class="space-y-6 max-w-3xl">
                        <!-- Product -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product*</label>
                            <select id="m_product_id" name="m_product_id"
                                class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                required>
                                <!-- AJAX Populated -->
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Qty -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity*</label>
                                <input type="number" name="qty" id="qty" min="0.01" step="0.01" required
                                    oninput="recalcWithholding()"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            </div>

                            <!-- Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price</label>
                                <input type="number" name="price" id="price" min="0" step="0.01"
                                    oninput="recalcWithholding()"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description /
                                Note</label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"></textarea>
                        </div>

                        <!-- Withholding Tax (PPh23) -->
                        <div class="border border-orange-100 dark:border-orange-900/40 rounded-lg p-4 space-y-4 bg-orange-50/40 dark:bg-orange-900/10">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="is_withholding" name="is_withholding" value="1"
                                    onchange="onWithholdingToggle(this.checked)"
                                    class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-400 cursor-pointer">
                                <label for="is_withholding" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                                    Is Withholding Tax
                                    <span class="ml-1 text-xs font-normal text-orange-500">(PPh23)</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Withholding Rate -->
                                <div>
                                    <label for="withholding_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Withholding Rate (%)
                                    </label>
                                    <div class="relative">
                                        <input type="number" id="withholding_rate" name="withholding_rate"
                                            value="2" min="0" step="0.01" disabled
                                            oninput="recalcWithholding()"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-400 focus:ring-orange-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm pr-8 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400 dark:disabled:bg-gray-800 transition-colors">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 text-sm">%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Withholding Amount -->
                                <div>
                                    <label for="withholding_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Withholding Amount
                                        <span class="text-xs text-gray-400 font-normal ml-1">(auto)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 text-sm">Rp</span>
                                        </div>
                                        <input type="text" id="withholding_amount" name="withholding_amount"
                                            readonly placeholder="0.00"
                                            class="w-full rounded-md border-gray-200 bg-gray-50 pl-10 cursor-not-allowed dark:bg-gray-800/50 dark:border-gray-600 dark:text-orange-400 text-orange-600 sm:text-sm font-medium">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('requisition.index', ['document_id' => $document_id, 'tab' => 'lines']) }}"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-5 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600 focus:ring-4 focus:ring-brand-500/50">
                                Save Line
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <!-- Select2 Resources -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <style>
            /* Select2 Custom Styling to match Tailwind */
            .select2-container--default .select2-selection--single {
                background-color: transparent;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                height: 46px;
                display: flex;
                align-items: center;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #1f2937;
                padding-left: 1rem;
                line-height: normal;
                width: 100%;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 44px;
                right: 0.75rem;
            }

            .dark .select2-container--default .select2-selection--single {
                background-color: #111827;
                border-color: #374151;
            }

            .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #e5e7eb;
            }

            .dark .select2-dropdown {
                background-color: #1f2937;
                border-color: #374151;
                color: #e5e7eb;
            }

            .dark .select2-container--default .select2-results__option--selectable {
                background-color: #1f2937;
                color: #e5e7eb;
            }

            .dark .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
                background-color: #4f46e5;
                color: white;
            }

            .dark .select2-search__field {
                background-color: #374151;
                color: white;
                border-color: #4b5563;
                border-radius: 0.25rem;
            }
        </style>

        <script>
            $(document).ready(function () {
                // Init Product Select2
                $('#m_product_id').select2({
                    width: '100%',
                    placeholder: 'Search Product...',
                    ajax: {
                        url: '{{ route("requisition.api.products") }}',
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
            });

            function onWithholdingToggle(checked) {
                const rateInput = document.getElementById('withholding_rate');
                rateInput.disabled = !checked;
                if (checked) {
                    if (!rateInput.value || rateInput.value === '0') {
                        rateInput.value = '2';
                    }
                }
                recalcWithholding();
            }

            function recalcWithholding() {
                const isChecked = document.getElementById('is_withholding').checked;
                const amtInput = document.getElementById('withholding_amount');

                if (!isChecked) {
                    amtInput.value = '';
                    return;
                }

                const qty = parseFloat(document.getElementById('qty').value) || 0;
                const price = parseFloat(document.getElementById('price').value) || 0;
                const rate = parseFloat(document.getElementById('withholding_rate').value) || 0;

                const lineAmount = qty * price;
                const withholdingAmt = lineAmount * rate / 100;

                amtInput.value = withholdingAmt.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        </script>
    @endpush
@endsection