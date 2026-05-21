@php
    $purchaseOrderConfig = $purchaseOrderConfig ?? config('idempiere.create-po');
    $lineEditableStatuses = $purchaseOrderConfig['statuses']['line_editable'] ?? [];
    $linePerPageOptions = $purchaseOrderConfig['limits']['line_per_page_options'] ?? [10, 25, 50];
    $defaultLinePerPage = $purchaseOrderConfig['limits']['line_default_per_page'] ?? 10;
@endphp

<div id="lines-list-container" class="space-y-6">
    <input type="hidden" id="hidden_pricelist_id" value="{{ isset($order) ? $order->m_pricelist_id : '' }}">
    <!-- Table Controls (Search & Actions) -->
    <div
        class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-900 p-1 mt-5 mr-5 ml-5">

        <!-- Left Side: Per Page & Title (Optional) -->
        <div class="flex items-center gap-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white hidden sm:block">Line Items</h3>
            <!-- Per Page -->
            <div class="relative">
                <select name="per_page" onchange="handlePerPageLines(this.value)"
                    class="border border-gray-200 dark:border-gray-800 h-10 pl-3 pr-8 text-sm bg-gray-50 border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all cursor-pointer dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                    @php
                        $selectedPerPage = (int) request('per_page', $defaultLinePerPage);
                    @endphp
                    @foreach($linePerPageOptions as $linePerPageOption)
                        <option value="{{ $linePerPageOption }}" {{ $selectedPerPage === (int) $linePerPageOption ? 'selected' : '' }}>{{ $linePerPageOption }} rows</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Right Side: Search & Create -->
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <!-- Search -->
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400 group-focus-within:text-brand-500 transition-colors"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="q_lines" name="q_lines" value="{{ request('q_lines') }}"
                    class="block w-full sm:w-64 pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-gray-800 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 placeholder-gray-400"
                    placeholder="Search products or descriptions..."
                    onkeydown="if(event.key === 'Enter') { event.preventDefault(); handleSearchLines(this.value); }">
            </div>

            <!-- Create Action -->
            <!-- Check Order Status: Draft (DR), In Progress (IP), Invalid (IN) allow editing -->
            @if(isset($order) && in_array($order->docstatus, $lineEditableStatuses, true))
                <div class="flex items-center gap-2">
                    <button type="button" id="deleteSelectedBtn" onclick="deleteSelectedLines()" style="display: none;"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md transition-all focus:ring-4 focus:ring-red-500/30 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        <span id="deleteSelectedText">Delete Selected</span>
                    </button>

                    <!-- NEW: Add from Requisition Button -->
                    <button type="button" onclick="openRequisitionModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md transition-all focus:ring-4 focus:ring-blue-500/30 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                        <span>From Requisition</span>
                    </button>

                    <button type="button" onclick="showCreateLineForm()"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md transition-all focus:ring-4 focus:ring-brand-500/30 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Add Line</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Table Card -->
    @if(isset($lines))
        <div
            class="overflow-hidden rounded-b-2xl border border-t-0 border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-t border-gray-200 dark:border-gray-800 bg-gray-50 text-left dark:bg-white/[0.03]">
                            <th scope="col" class="w-12 px-4 py-3">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAllLines(this)"
                                    class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            </th>
                            <th scope="col" class="min-w-[50px] px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                                #</th>
                            <th scope="col"
                                class="min-w-[200px] px-4 py-3 font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                                Product Details</th>
                            <th scope="col"
                                class="min-w-[100px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                                Qty</th>
                            <th scope="col"
                                class="min-w-[100px] px-6 py-4 font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                                UOM</th>
                            <th scope="col"
                                class="min-w-[100px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                                Price</th>
                            <th scope="col"
                                class="min-w-[200px] px-6 py-4 font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                                Description</th>
                            <th scope="col"
                                class="min-w-[120px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse($lines as $line)
                            <tr class="border-t border-gray-200 dark:border-gray-800">
                                <td class="w-12 px-4 py-4">
                                    <input type="checkbox"
                                        class="line-checkbox w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700"
                                        value="{{ $line->c_orderline_id }}" onchange="updateDeleteLinesButtonState()">
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-theme-xs text-gray-400 font-medium group-hover:text-gray-600 dark:group-hover:text-gray-300">
                                    {{ $line->line }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-theme-xs font-semibold text-gray-900 dark:text-white">{{ $line->product_name }}</span>
                                        <span
                                            class="text-theme-xs text-gray-500 font-mono mt-0.5 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded w-fit">{{ $line->product_code }}</span>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-theme-xs text-right font-sm text-gray-900 dark:text-white font-mono bg-gray-50/30 dark:bg-gray-800/30">
                                    {{ number_format($line->qty, 2) }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-theme-xs text-gray-500 uppercase dark:text-gray-400">
                                    {{ $line->uom_name }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-theme-xs text-right text-gray-600 dark:text-gray-400 font-mono">
                                    {{ number_format($line->priceactual, 2) }}
                                </td>
                                <td class="px-6 py-4 text-theme-xs text-gray-500 dark:text-gray-400">
                                    <div class="truncate max-w-xs whitespace-nowrap" title="{{ $line->description }}">
                                        {{ $line->description ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(!$isReadOnly)
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                onclick="editLine({{ $line->c_orderline_id }}, '{{ addslashes($line->product_code) }} - {{ addslashes($line->product_name) }}', {{ $line->m_product_id }}, {{ $line->qty }}, {{ $line->priceactual }}, '{{ addslashes($line->description ?? '') }}', {{ $line->line }}, '{{ addslashes($line->uom_name ?? '') }}', {{ $line->m_requisitionline_id ?? 'null' }}, '{{ addslashes($line->requisition_documentno ?? '') }}', {{ ($line->is_withholding === 'Y' || $line->is_withholding === true) ? 'true' : 'false' }}, {{ $line->withholding_rate ?? 2 }}, '{{ $line->withholding_amount ?? '0.00' }}')"
                                                class="btn btn-sm btn-warning bg-yellow-100 me-1 p-2 rounded-sm" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button type="button" onclick="deleteLine({{ $line->c_orderline_id }})"
                                                class="btn btn-sm btn-danger rounded-sm bg-red-100 p-2" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div
                                            class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 dark:bg-gray-800">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                                </path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No lines items yet
                                        </h3>
                                        <p class="text-gray-500 text-sm mb-6 dark:text-gray-400">Add products to this
                                            order to get started.</p>
                                        @if(isset($order) && in_array($order->docstatus, $lineEditableStatuses, true))
                                            <button onclick="showCreateLineForm()" type="button"
                                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-brand-700 bg-brand-100 hover:bg-brand-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                                Add First Line
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer Pagination -->
            @if($lines->total() > 0)
                <div
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Showing <span class="font-medium text-gray-900 dark:text-white">{{ $lines->firstItem() }}</span> to
                        <span class="font-medium text-gray-900 dark:text-white">{{ $lines->lastItem() }}</span> of <span
                            class="font-medium text-gray-900 dark:text-white">{{ $lines->total() }}</span> results
                    </div>
                    <div class="w-full sm:w-auto">
                        {{ $lines->appends(request()->except('lines_page'))->links('vendor.pagination.simple-tailwind') }}
                    </div>
                </div>
            @endif


            <!-- Order Summary -->

        </div>
    @endif
</div>

<!-- Requisition Modal -->
<div id="requisitionSelectionModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" aria-hidden="true" onclick="closeRequisitionModal()">
        </div>

        <!-- Modal Panel -->
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col z-10"
            style="max-height: 85vh;">
            <!-- Modal Header -->
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Select from Requisition</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Only completed requisitions are shown</p>
                    </div>
                </div>
                <button type="button" onclick="closeRequisitionModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Search & Filter Bar -->
            <div
                class="px-6 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="req_search_input" placeholder="Search by Requisition No or Product..."
                            class="block w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                            oninput="debounceReqSearch(this.value)">
                    </div>
                    <button type="button" onclick="loadRequisitionLines(1)"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 rounded-lg border border-blue-200 dark:border-blue-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Table Body (Scrollable) -->
            <div class="flex-1 overflow-y-auto" style="min-height: 200px;">
                <!-- Loading State -->
                <div id="req_loading" class="hidden py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-3">
                        <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="text-sm text-gray-500">Loading requisitions...</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="req_empty" class="hidden py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-3">
                        <div
                            class="w-14 h-14 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No completed requisitions found</p>
                    </div>
                </div>

                <!-- Table -->
                <table class="w-full text-sm" id="req_table">
                    <thead
                        class="sticky top-0 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 z-10">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" id="req_select_all"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                                    title="Select / Deselect All"
                                    onclick="toggleAllReqCheckboxes(this)">
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Req No</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Product</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Qty</th>
                        </tr>
                    </thead>
                    <tbody id="req_table_body" class="divide-y divide-gray-100 dark:divide-gray-800">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400" id="req_pagination_info">&nbsp;</div>
                    <div class="flex items-center gap-1" id="req_pagination_btns"></div>
                </div>
            </div>

            <!-- Action Footer -->
            <div
                class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                <span id="req_selected_count" class="text-sm text-gray-500 dark:text-gray-400">No line selected</span>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRequisitionModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmRequisitionAdd()" id="req_confirm_btn" disabled
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Create Line Form (Hidden by Default) -->
<div id="lines-create-form" class="hidden">
    <div class="bg-gray-50/50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
        <div class="">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        New Order Line
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-10">Search for a product and specify
                        quantity to add to the document.</p>
                </div>
                <button type="button" onclick="hideCreateLineForm()" class="text-gray-400 hover:text-gray-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="createLineForm" onsubmit="event.preventDefault(); saveLine();" class="space-y-6">
                <!-- Use document_id param from URL or controller -->
                <input type="hidden" name="document_id" value="{{ $docIdParam }}">
                <input type="hidden" name="line_id" id="line_id" value="">

                <!-- NEW: M_RequisitionLine_ID Hidden Field -->
                <input type="hidden" name="m_requisitionline_id" id="m_requisitionline_id" value="">

                <div
                    class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-6">
                    <!-- NEW Requisition Info Field (Visible but Readonly) -->
                    <div id="requisition_info_container"
                        class="hidden bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-dashed border-blue-300 dark:border-blue-800 mb-4">
                        <label class="block text-sm font-medium text-blue-800 dark:text-blue-300 mb-1">Linked
                            Requisition</label>
                        <div class="flex items-center gap-2">
                            <span id="requisition_info_text"
                                class="text-sm text-blue-900 dark:text-blue-200 font-medium"></span>
                            <!-- <button type="button" id="req_remove_link_btn" onclick="clearRequisitionLink()"
                                class="text-red-500 hover:text-red-700 text-xs underline ml-2">Remove Link</button> -->
                        </div>
                    </div>

                    <!-- Product -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Select Product
                            <span class="text-red-500">*</span></label>
                        <select id="m_product_id" name="m_product_id" class="w-full" required>
                            <!-- AJAX Populated -->
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Qty -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quantity
                                <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" id="line_qty" name="qty" required
                                    oninput="formatNumber(this); calculateLineTotal();"
                                    class="block w-full rounded-lg border-gray-300 pl-4 pr-12 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm"
                                    placeholder="0.00">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <span id="product_uom" class="text-gray-400 sm:text-sm">Unit</span>
                                </div>
                            </div>
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Price <span
                                    class="text-gray-400 text-xs font-normal ml-1">(Optional)</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="line_price" name="price"
                                    oninput="formatNumber(this); calculateLineTotal();"
                                    class="block w-full rounded-lg border-gray-300 pl-10 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tax (Read-only, from Header) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tax
                                <span class="text-gray-400 text-xs font-normal ml-1">(From Header)</span></label>
                            <input type="text" id="c_tax_line_id" readonly
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 cursor-not-allowed dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 sm:text-sm h-11 shadow-sm px-4"
                                placeholder="Tax from header">
                            <input type="hidden" id="c_tax_line_value" name="c_tax_id" value="">
                        </div>

                        <!-- Line Amount (Total) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Line Amount
                                <span class="text-gray-400 text-xs font-normal ml-1">(Qty * Price)</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="line_amount" readonly
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 pl-10 cursor-not-allowed dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 sm:text-sm h-11 shadow-sm"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description /
                            Note</label>
                        <textarea id="line_description" name="description" rows="3"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-700 dark:text-white sm:text-sm p-3 placeholder-gray-400"
                            placeholder="Add any additional details or specifications..."></textarea>
                    </div>

                    <!-- Withholding Tax (PPh23) -->
                    <div class="border border-orange-100 dark:border-orange-900/40 rounded-lg p-4 space-y-4 bg-orange-50/40 dark:bg-orange-900/10">
                        <!-- IsWithholding -->
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="line_is_withholding" name="is_withholding" value="1"
                                onchange="onLineWithholdingToggle(this.checked)"
                                class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-400 cursor-pointer">
                            <label for="line_is_withholding" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                                Is Withholding Tax
                                <span class="ml-1 text-xs font-normal text-orange-500">(PPh23)</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- WithholdingRate -->
                            <div>
                                <label for="line_withholding_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Withholding Rate (%)
                                </label>
                                <div class="relative">
                                    <input type="number" id="line_withholding_rate" name="withholding_rate"
                                        value="2" min="0" step="0.01" disabled
                                        oninput="calculateLineTotal()"
                                        class="block w-full rounded-lg border-gray-300 pr-8 focus:border-orange-400 focus:ring-orange-400 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm px-4 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed dark:disabled:bg-gray-800/60 transition-colors">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-400 text-sm">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- WithholdingAmount -->
                            <div>
                                <label for="line_withholding_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Withholding Amount
                                    <span class="text-xs text-gray-400 font-normal ml-1">(Rate % × Line Amount)</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">Rp</span>
                                    </div>
                                    <input type="text" id="line_withholding_amount" name="withholding_amount"
                                        readonly placeholder="0.00"
                                        class="block w-full rounded-lg border-gray-200 bg-gray-50 pl-10 cursor-not-allowed dark:bg-gray-700/50 dark:border-gray-600 dark:text-orange-400 text-orange-600 sm:text-sm h-11 shadow-sm font-medium">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between gap-3 pt-4">
                    <!-- Left: Delete (edit mode only) -->
                    <div>
                        <button type="button" id="form_delete_line_btn" onclick="deleteCurrentLine()"
                            class="hidden px-5 py-2.5 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors shadow-sm dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/40 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            Delete Line
                        </button>
                    </div>
                    <!-- Right: Cancel + Save -->
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="hideCreateLineForm()"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            Save Line Item
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>