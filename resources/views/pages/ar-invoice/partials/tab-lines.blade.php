{{-- Lines List Container --}}
<div id="lines-list-container" class="space-y-6">
    @php
        $arInvoiceConfig = config('idempiere.ar-invoice');
        $readOnlyStatuses = $arInvoiceConfig['statuses']['read_only'] ?? ['CO', 'CL', 'VO', 'RE'];
        $isReadOnly = !is_null($invoice) && in_array($invoice->docstatus, $readOnlyStatuses);
        $canEdit = !$isReadOnly;
        $encDocId = $docIdParam;
    @endphp

    {{-- Table Controls --}}
    <div
        class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-900 p-1 mt-5 mr-5 ml-5">
        {{-- Left: Title --}}
        <div class="flex items-center gap-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white hidden sm:block">Invoice Lines</h3>
            <div class="relative">
                <select name="per_page" onchange="handlePerPageLines(this.value)"
                    class="border border-gray-200 dark:border-gray-800 h-10 pl-3 pr-8 text-sm bg-gray-50 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all cursor-pointer dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 rows</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 rows</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 rows</option>
                </select>
            </div>
        </div>

        {{-- Right: Search + Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400 group-focus-within:text-brand-500 transition-colors" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="q_lines" name="q_lines" value="{{ request('q_lines') }}"
                    class="block w-full sm:w-64 pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-gray-800 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 placeholder-gray-400"
                    placeholder="Search product or description..."
                    onkeydown="if(event.key==='Enter'){event.preventDefault();handlePerPageLines(document.querySelector('[name=per_page]')?.value||10);}">
            </div>

            @if($canEdit)
                <div class="flex items-center gap-2">
                    <button type="button" id="deleteSelectedBtn" onclick="deleteSelectedLines()" style="display:none;"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span id="deleteSelectedText">Delete Selected</span>
                    </button>
                    <!-- From Shipment Button -->
                    <button type="button" onclick="openShipmentModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        From Shipment
                    </button>

                    <button type="button" onclick="showCreateLineForm()"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Line
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div
        class="overflow-hidden rounded-b-2xl border border-t-0 border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-t border-gray-200 dark:border-gray-800 bg-gray-50 text-left dark:bg-white/[0.03]">
                        @if($canEdit)
                            <th class="w-12 px-4 py-3">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAllLines(this)"
                                    class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            </th>
                        @endif
                        <th class="min-w-[50px] px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">#</th>
                        <th class="min-w-[200px] px-4 py-3 font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                            Product Details</th>
                        <th
                            class="min-w-[100px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                            Qty</th>
                        <th class="min-w-[80px] px-6 py-4 font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                            UOM</th>
                        <th
                            class="min-w-[130px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                            Unit Price</th>
                        <th
                            class="min-w-[140px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                            Net Amount</th>
                        <th class="min-w-[140px] px-6 py-4 font-medium text-gray-500 text-theme-sm dark:text-gray-400">
                            Shipment Ref</th>
                        @if($canEdit)
                            <th
                                class="min-w-[100px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                                Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse($lines as $line)
                        <tr
                            class="border-t border-gray-200 dark:border-gray-800 hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                            @if($canEdit)
                                <td class="w-12 px-4 py-4">
                                    <input type="checkbox"
                                        class="line-checkbox w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700"
                                        value="{{ $line->c_invoiceline_id }}" onchange="updateDeleteLinesButtonState()">
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap text-theme-xs text-gray-400 font-medium">
                                {{ $line->line }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span
                                        class="text-theme-xs font-semibold text-gray-900 dark:text-white">{{ $line->product_name ?? '-' }}</span>
                                    @if($line->product_code ?? false)
                                        <span
                                            class="text-theme-xs text-gray-500 font-mono mt-0.5 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded w-fit">{{ $line->product_code }}</span>
                                    @endif
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-theme-xs text-right font-sm text-gray-900 dark:text-white font-mono bg-gray-50/30 dark:bg-gray-800/30">
                                {{ number_format($line->qty, 2) }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-theme-xs text-gray-500 uppercase dark:text-gray-400">
                                {{ $line->uom_symbol ?? $line->uom_name ?? '-' }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-theme-xs text-right text-gray-600 dark:text-gray-400 font-mono">
                                {{ number_format($line->unit_price, 2) }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-theme-xs text-right font-bold text-gray-900 dark:text-white font-mono bg-brand-50/10 dark:bg-brand-900/10">
                                {{ number_format($line->net_amount, 2) }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-theme-xs text-gray-500 dark:text-gray-400 font-mono">
                                {{ $line->shipment_no ?? '-' }}
                            </td>
                            @if($canEdit)
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button"
                                            onclick="editLine({{ $line->c_invoiceline_id }}, '{{ addslashes($line->product_name ?? '') }}', {{ $line->m_product_id ?? 'null' }}, {{ $line->qty }}, {{ $line->unit_price }}, '{{ addslashes($line->description ?? '') }}', {{ $line->line }}, '{{ $line->uom_symbol ?? $line->uom_name ?? '' }}', {{ $line->m_inoutline_id ?? 'null' }}, '{{ addslashes($line->shipment_no ?? '') }}', '{{ addslashes($line->shipment_poref ?? '') }}', '{{ $line->shipment_date ? \Carbon\Carbon::parse($line->shipment_date)->format('d M Y') : '' }}')"
                                            class="btn btn-sm bg-yellow-100 me-1 p-2 rounded-sm hover:bg-yellow-200 transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4 text-yellow-700" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="deleteLine({{ $line->c_invoiceline_id }})"
                                            class="btn btn-sm rounded-sm bg-red-100 p-2 hover:bg-red-200 transition-colors"
                                            title="Delete">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 9 : 7 }}" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <div
                                        class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 dark:bg-gray-800">
                                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No line items yet
                                    </h3>
                                    <p class="text-gray-500 text-sm mb-6 dark:text-gray-400">Add products to this invoice to
                                        get started.</p>
                                    @if($canEdit)
                                        <button onclick="showCreateLineForm()" type="button"
                                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-brand-700 bg-brand-100 hover:bg-brand-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
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

        {{-- Footer Pagination --}}
        @if($lines->total() > 0)
            <div
                class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span class="font-medium text-gray-900 dark:text-white">{{ $lines->firstItem() }}</span>
                    to <span class="font-medium text-gray-900 dark:text-white">{{ $lines->lastItem() }}</span>
                    of <span class="font-medium text-gray-900 dark:text-white">{{ $lines->total() }}</span> results
                </div>
                <div class="w-full sm:w-auto">
                    {{ $lines->appends(request()->except('lines_page'))->links('vendor.pagination.simple-tailwind') }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ── Shipment Selection Modal ──────────────────────────────────────────── --}}
<div id="shipmentSelectionModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeShipmentModal()"></div>
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col z-10"
            style="max-height:85vh;">
            <!-- Header -->
            <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Select from Shipment</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Only completed shipments are shown. Type
                            at least 2 chars to search.</p>
                    </div>
                </div>
                <button type="button" onclick="closeShipmentModal()"
                    class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- Search -->
            <div
                class="px-6 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" id="shipment_search_input" placeholder="Search by Shipment No or Product..."
                            class="block w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                            oninput="debounceShipmentSearch(this.value)">
                    </div>
                    <button type="button" onclick="loadShipmentLines(1)"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
            <!-- Table Body -->
            <div class="flex-1 overflow-y-auto" style="min-height:200px;">
                <div id="shipment_loading" class="hidden py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-3">
                        <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="text-sm text-gray-500">Loading shipments...</span>
                    </div>
                </div>
                <div id="shipment_empty" class="py-16 text-center">
                    <p class="text-sm text-gray-500">Type at least 2 characters to search shipment lines.</p>
                </div>
                <table class="w-full text-sm" id="shipment_table" style="display:none;">
                    <thead
                        class="sticky top-0 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 z-10">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" id="shipment_select_all" onchange="onShipmentSelectAllChange(this)"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Shipment</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Product</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Qty</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Remaining</th>
                        </tr>
                    </thead>
                    <tbody id="shipment_table_body" class="divide-y divide-gray-100 dark:divide-gray-800"></tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div
                class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500" id="shipment_pagination_info">&nbsp;</div>
                    <div class="flex items-center gap-1" id="shipment_pagination_btns"></div>
                </div>
            </div>
            <!-- Footer -->
            <div
                class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                <span id="shipment_selected_count" class="text-sm text-gray-500">No line selected</span>
                <div class="flex gap-3">
                    <button type="button" onclick="closeShipmentModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="button" onclick="confirmShipmentAdd()" id="shipment_confirm_btn" disabled
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Create / Edit Line Form Panel (Hidden by Default) ────────────────── --}}
@if($canEdit)
    <div id="lines-create-form" class="hidden">
        <div class="bg-gray-50/50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 id="lineFormPanel"
                        class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        New Invoice Line
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-10">Search for a product and specify quantity
                        to add to the invoice.</p>
                </div>
                <button type="button" onclick="hideCreateLineForm()" class="text-gray-400 hover:text-gray-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="createLineForm" onsubmit="event.preventDefault(); saveLine();" class="space-y-6">
                <input type="hidden" name="document_id" value="{{ $encDocId }}">
                <input type="hidden" name="line_id" id="line_id" value="">
                {{-- Shipment line link (set by Modal) --}}
                <input type="hidden" name="m_inoutline_id" id="m_inoutline_id" value="">
                <input type="hidden" name="c_orderline_id" id="c_orderline_id" value="">

                {{-- Linked Shipment info banner (shown via JS) --}}
                <div id="shipment_info_container"
                    class="hidden bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-dashed border-blue-300 dark:border-blue-800 mb-4">
                    <label class="block text-sm font-medium text-blue-800 dark:text-blue-300 mb-1">Linked
                        Shipment</label>
                    <div class="flex items-center gap-2">
                        <span id="shipment_info_text" class="text-sm text-blue-900 dark:text-blue-200 font-medium"></span>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-6">
                    {{-- Product --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Select Product <span class="text-red-500">*</span>
                        </label>
                        <select id="m_product_id" name="m_product_id" class="w-full" required>
                            {{-- AJAX Populated --}}
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Qty --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Quantity <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" id="line_qty" name="qty" required
                                    onkeypress="return /[0-9.]/.test(event.key)" onblur="formatNumber(this)"
                                    class="block w-full rounded-lg border-gray-300 pl-4 pr-16 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm"
                                    placeholder="0.00">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <span id="product_uom" class="text-gray-400 sm:text-sm">Unit</span>
                                </div>
                            </div>
                        </div>

                        {{-- Unit Price --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Unit Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="line_price" name="price" onkeypress="return /[0-9.]/.test(event.key)"
                                    onblur="formatNumber(this)"
                                    class="block w-full rounded-lg border-gray-300 pl-10 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description /
                            Note</label>
                        <textarea id="line_description" name="description" rows="3"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-700 dark:border-gray-700 dark:text-white sm:text-sm p-3 placeholder-gray-400"
                            placeholder="Add any additional details or specifications..."></textarea>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" onclick="hideCreateLineForm()"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Line Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const DOC_ID = '{{ $encDocId }}';
            const STORE_URL = '{{ route("ar-invoice.line.store") }}';
            const UPD_URL = '{{ route("ar-invoice.line.update") }}';
            const DEL_URL = '{{ route("ar-invoice.line.delete") }}';
            const GR_URL = '{{ route("ar-invoice.api.shipment-lines-link") }}';
            const PROD_URL = '{{ route("ar-invoice.api.products") }}';
            const CSRF = '{{ csrf_token() }}';

            function initSelect2ForLineForm() {
                // Product Select2
                if ($('#m_product_id').length && !$('#m_product_id').data('select2')) {
                    $('#m_product_id').select2({
                        width: '100%', placeholder: 'Search Product...', allowClear: false,
                        ajax: {
                            url: PROD_URL, dataType: 'json', delay: 250,
                            data: p => ({ q: p.term, page: p.page || 1 }),
                            processResults: d => ({ results: d.results, pagination: { more: d.pagination.more } }),
                            cache: true
                        }
                    });
                    $('#m_product_id').off('change.apinv').on('change.apinv', function () {
                        if (window.isEditingLine) return;
                        const d = $(this).select2('data');
                        $('#product_uom').text(d && d[0] ? (d[0].uom_symbol || d[0].uom_name || 'Unit') : 'Unit');
                    });
                }

                // GR Line Select2
                if ($('#gr_line_id').length && !$('#gr_line_id').data('select2')) {
                    $('#gr_line_id').select2({
                        width: '100%', placeholder: '-- Search GR Line --', allowClear: true,
                        ajax: {
                            url: GR_URL, dataType: 'json', delay: 300,
                            data: p => ({ q: p.term, page: p.page || 1, customer_id: $('#c_bpartner_id').val() }),
                            processResults: d => ({ results: d.results, pagination: { more: d.pagination.more } }),
                            cache: false
                        }
                    });

                    // select2:select gives the FULL data object from processResults directly
                    $('#gr_line_id').on('select2:select', function (e) {
                        const item = e.params.data;
                        if (!item) return;

                        // Populate hidden fields so they're submitted with the form
                        document.getElementById('gr_inoutline_id').value = item.id || '';
                        document.getElementById('gr_orderline_id').value = item.c_orderline_id || '';

                        // Auto-fill Product
                        const label = (item.product_code ? item.product_code + ' - ' : '') + (item.product_name || '');
                        $('#m_product_id')
                            .append(new Option(label, item.m_product_id, true, true))
                            .trigger('change');

                        // Auto-fill Qty
                        const qtyEl = document.getElementById('line_qty');
                        if (qtyEl) {
                            qtyEl.value = parseFloat(item.qty || 0)
                                .toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }

                        // Set price + UOM AFTER product change event settles
                        // (avoids any handler from overwriting price)
                        const priceVal = parseFloat(item.unit_price || 0);
                        const uomText = item.uom_symbol || 'Unit';
                        setTimeout(function () {
                            const priceEl = document.getElementById('line_price');
                            if (priceEl) {
                                priceEl.value = priceVal > 0
                                    ? priceVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                    : '';
                            }
                            $('#product_uom').text(uomText);
                        }, 0);
                    });
                }
            }

            window.showCreateLineForm = function () {
                document.getElementById('line_id').value = '';
                document.getElementById('createLineForm').reset();
                // Clear any lingering shipment state
                if (typeof clearShipmentLink === 'function') clearShipmentLink();
                if (typeof $ !== 'undefined') {
                    if ($('#m_product_id').data('select2')) {
                        $('#m_product_id').prop('disabled', false); // Ensure it's enabled for new lines
                        $('#m_product_id').val(null).trigger('change');
                    }
                    $('#product_uom').text('Unit');
                }
                const title = document.querySelector('#lineFormPanel');
                if (title) title.childNodes[title.childNodes.length - 1].textContent = 'New Invoice Line';
                $('#lines-list-container').addClass('hidden');
                $('#lines-create-form').removeClass('hidden');

                // Init Select2 after panel is visible
                setTimeout(initSelect2ForLineForm, 80);
            };

            window.hideCreateLineForm = function () {
                $('#lines-create-form').addClass('hidden');
                $('#lines-list-container').removeClass('hidden');
            };

            window.clearShipmentLink = function () {
                document.getElementById('m_inoutline_id').value = '';
                document.getElementById('c_orderline_id').value = '';
                const container = document.getElementById('shipment_info_container');
                if (container) container.classList.add('hidden');

                // Re-enable product selection if link is removed
                if (typeof $ !== 'undefined' && $('#m_product_id').data('select2')) {
                    $('#m_product_id').prop('disabled', false);
                }
            };

            window.editLine = function (lineId, productName, productId, qty, price, desc, lineNo, uomName, mInOutLineId, shipmentNo, poRef, shipmentDate) {
                showCreateLineForm();
                window.isEditingLine = true;
                document.getElementById('line_id').value = lineId;

                if (typeof $ !== 'undefined' && productId) {
                    const opt = new Option(productName, productId, true, true);
                    $('#m_product_id').append(opt).trigger('change');
                }

                // Restore linked shipment info if available
                if (mInOutLineId) {
                    document.getElementById('m_inoutline_id').value = mInOutLineId;
                    const infoContainer = document.getElementById('shipment_info_container');
                    const infoText = document.getElementById('shipment_info_text');
                    if (infoContainer && infoText) {
                        let text = shipmentNo;
                        if (poRef) text += ` (PO: ${poRef})`;
                        if (shipmentDate) text += ` - ${shipmentDate}`;
                        infoText.textContent = text;
                        infoContainer.classList.remove('hidden');
                    }
                    // Disable product selection when linked to a shipment
                    if (typeof $ !== 'undefined') {
                        $('#m_product_id').prop('disabled', true);
                        if ($('#m_product_id').data('select2')) {
                            $('#m_product_id').trigger('change.select2');
                        }
                    }
                } else {
                    if (typeof clearShipmentLink === 'function') clearShipmentLink();
                }

                window.isEditingLine = false;
                if (uomName) $('#product_uom').text(uomName);
                document.getElementById('line_qty').value = parseFloat(qty).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('line_price').value = parseFloat(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('line_description').value = desc || '';
                const title = document.querySelector('#lineFormPanel');
                if (title) title.childNodes[title.childNodes.length - 1].textContent = 'Edit Invoice Line';
            };

            window.saveLine = function () {
                const form = document.getElementById('createLineForm');
                const lineId = document.getElementById('line_id').value;
                const rawData = Object.fromEntries(new FormData(form).entries());

                // Explicitly grab product ID, because disabled fields are not serialized by FormData
                const productId = $('#m_product_id').val() || rawData.m_product_id;

                const data = {
                    ...rawData,
                    m_product_id: productId,
                    qty: rawData.qty ? rawData.qty.replace(/,/g, '') : '',
                    unit_price: rawData.price ? rawData.price.replace(/,/g, '') : '0',
                };

                if (!data.m_product_id) {
                    Swal.fire({ icon: 'warning', title: 'Validation', text: 'Product is required.', confirmButtonColor: '#4f46e5' });
                    return;
                }
                if (!data.qty) {
                    Swal.fire({ icon: 'warning', title: 'Validation', text: 'Quantity is required.', confirmButtonColor: '#4f46e5' });
                    return;
                }

                const btn = form.querySelector('button[type="submit"]');
                let origHtml = '';
                if (btn) {
                    origHtml = btn.innerHTML;
                    btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white inline mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';
                    btn.disabled = true;
                }

                const url = lineId ? UPD_URL : STORE_URL;
                const method = lineId ? 'put' : 'post';

                axios[method](url, data)
                    .then(() => { hideCreateLineForm(); loadTabContent('lines'); })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Failed to save line.', confirmButtonColor: '#4f46e5' });
                        if (btn) { btn.innerHTML = origHtml; btn.disabled = false; }
                    });
            };

            window.deleteLine = function (lineId) {
                Swal.fire({
                    title: 'Delete Line?', text: 'This action cannot be undone!', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!', cancelButtonText: 'Cancel'
                }).then(r => { if (r.isConfirmed) performDelete([lineId]); });
            };

            window.deleteSelectedLines = function () {
                const ids = Array.from(document.querySelectorAll('.line-checkbox:checked')).map(c => c.value);
                if (!ids.length) return;
                Swal.fire({
                    title: `Delete ${ids.length} Line(s)?`, text: 'This action cannot be undone!', icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete them!', cancelButtonText: 'Cancel'
                }).then(r => { if (r.isConfirmed) performDelete(ids); });
            };

            function performDelete(lineIds) {
                axios.delete(DEL_URL, { data: { line_ids: lineIds, document_id: DOC_ID, _token: CSRF } })
                    .then(res => {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: res.data?.message || 'Lines deleted.', confirmButtonColor: '#4f46e5', timer: 2000 });
                        loadTabContent('lines');
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || err.message, confirmButtonColor: '#4f46e5' });
                    });
            }

            window.toggleSelectAllLines = function (cb) {
                document.querySelectorAll('.line-checkbox').forEach(c => c.checked = cb.checked);
                updateDeleteLinesButtonState();
            };

            window.updateDeleteLinesButtonState = function () {
                const checked = document.querySelectorAll('.line-checkbox:checked');
                const btn = document.getElementById('deleteSelectedBtn');
                const text = document.getElementById('deleteSelectedText');
                if (checked.length > 0) {
                    if (btn) btn.style.display = 'inline-flex';
                    if (text) text.textContent = `Delete Selected (${checked.length})`;
                } else {
                    if (btn) btn.style.display = 'none';
                }
                const all = document.querySelectorAll('.line-checkbox');
                const selAll = document.getElementById('selectAll');
                if (selAll) selAll.checked = all.length > 0 && all.length === checked.length;
            };

            window.handlePerPageLines = function (perPage) {
                const q = document.getElementById('q_lines')?.value || '';
                loadTabContent('lines', { per_page: perPage, q_lines: q, page: 1 });
            };

            window.formatNumber = function (input) {
                let value = input.value.replace(/,/g, '');
                if (/[^0-9.]/.test(value)) value = value.replace(/[^0-9.]/g, '');
                const parts = value.split('.');
                if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
                if (!value) { input.value = ''; return; }
                const sections = value.split('.');
                const integerPart = sections[0];
                const decimalPart = sections.length > 1 ? '.' + sections[1] : '';
                input.value = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + decimalPart;
            };
        })();
    </script>
@endif