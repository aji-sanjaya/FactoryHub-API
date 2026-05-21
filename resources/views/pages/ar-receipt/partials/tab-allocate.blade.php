{{-- Allocate Tab (linking AR Invoices to this payment) --}}
@php
    $arReceiptConfig = config('idempiere.ar-receipt');
    $readOnlyStatuses = $arReceiptConfig['statuses']['read_only'] ?? ['CO', 'CL', 'VO', 'RE'];
    $invoiceLookupMinSearchLength = $arReceiptConfig['limits']['invoice_lookup_min_search_length'] ?? 3;
    $isReadOnly = !is_null($payment) && in_array($payment->docstatus, $readOnlyStatuses);
    $canEdit = !$isReadOnly;
    $encDocId = $docIdParam;
@endphp

<div class="p-6 sm:p-8">

    {{-- ── Allocation Lines Table Section ── --}}
    <div id="allocate-table-section">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                    Allocation Lines
                </h3>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400">
                    {{ count($allocations ?? []) }} item(s)
                </span>
            </div>
            @if($canEdit)
                <div class="flex items-center gap-2">
                    <button type="button" onclick="openInvoiceSelectModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm hover:shadow transition-all dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Allocate Invoice
                    </button>
                    <button type="button" id="deleteAllocSelectedBtn" onclick="deleteSelectedAllocations()"
                        style="display:none;"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span id="deleteAllocSelectedText">Delete</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        @if($canEdit)
                            <th class="px-4 py-3 text-left w-12">
                                <input type="checkbox" id="selectAllAlloc" onclick="toggleSelectAllAlloc(this)"
                                    class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            </th>
                        @endif
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12 text-center">
                            #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Invoice No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Invoice Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Invoice Amt</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-brand-600 uppercase tracking-wider">
                            Allocated</th>
                        @if($canEdit)
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">
                                Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100 dark:bg-gray-900 dark:divide-gray-700">
                    @forelse($allocations ?? [] as $idx => $alloc)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            @if($canEdit)
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                        class="alloc-checkbox w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700"
                                        value="{{ $alloc->c_paymentallocate_id }}" onchange="updateAllocDeleteState()">
                                </td>
                            @endif
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-sm font-semibold text-gray-900 dark:text-white">{{ $alloc->invoice_documentno ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $alloc->dateinvoiced ? \Carbon\Carbon::parse($alloc->dateinvoiced)->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-600 dark:text-gray-300">
                                {{ number_format($alloc->invoice_grandtotal ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-bold text-brand-600 dark:text-brand-400">
                                {{ number_format($alloc->amount ?? 0, 2) }}
                            </td>
                            @if($canEdit)
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" 
                                            onclick="editAllocation({{ json_encode([
                                                'id' => $alloc->c_paymentallocate_id,
                                                'c_invoice_id' => $alloc->c_invoice_id ?? 0,
                                                'invoice_documentno' => $alloc->invoice_documentno ?? '',
                                                'invoice_grandtotal' => $alloc->invoice_grandtotal ?? 0,
                                                'amount' => $alloc->amount ?? 0,
                                                'discountamt' => $alloc->discountamt ?? 0,
                                                'writeoffamt' => $alloc->writeoffamt ?? 0,
                                                'overunderamt' => $alloc->overunderamt ?? 0,
                                            ]) }})"
                                            class="btn btn-sm btn-warning rounded-sm bg-blue-100 p-2 hover:bg-blue-200 transition-colors" title="Edit">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="deleteAllocation({{ $alloc->c_paymentallocate_id }})"
                                            class="btn btn-sm btn-danger rounded-sm bg-red-100 p-2 hover:bg-red-200 transition-colors" title="Delete">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <td colspan="{{ $canEdit ? 7 : 6 }}" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div
                                        class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">No allocation lines yet</span>
                                    @if($canEdit)
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Click "Allocate Invoice" to link
                                            an invoice.</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Create / Edit Allocation Inline Form ── --}}
    @if($canEdit)
        <div id="allocate-create-form" class="hidden mt-2">
            <div>
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 id="alloc-form-title"
                            class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            New Allocation Line
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-10">Define the allocation amounts for the
                            selected invoice.</p>
                    </div> 
                </div>

                <form id="createAllocForm" onsubmit="event.preventDefault(); saveAllocationLine();" class="space-y-6">
                    <input type="hidden" name="document_id" value="{{ $encDocId }}">
                    <input type="hidden" id="c_invoice_id" value="">
                    <input type="hidden" id="inv_open_amt" value="0">
                    <input type="hidden" id="inv_grandtotal" value="0">

                    <div
                        class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-6">

                        {{-- Row 1: Readonly info --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice
                                    Number</label>
                                <input type="text" id="disp_invoice_no" readonly
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 sm:text-sm h-11 px-4 shadow-sm"
                                    value="-">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Invoice
                                    Amount (Grand Total)</label>
                                <input type="text" id="disp_invoice_amt" readonly
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 sm:text-sm text-right font-mono h-11 px-4 shadow-sm"
                                    value="0.00">
                            </div>
                        </div>

                        <hr class="border-gray-100 dark:border-gray-800">

                        {{-- Row 2: Inputs --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount
                                    <span class="text-red-500">*</span></label>
                                <input type="text" id="alloc_amount" required onfocus="qtyUnformat(this)"
                                    onblur="qtyFormat(this); calcRemainingAmt();"
                                    oninput="this.value=this.value.replace(/[^0-9.]/g,''); calcRemainingAmt();"
                                    class="block w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm text-right font-mono h-11 px-4 shadow-sm"
                                    value="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Discount
                                    Amount</label>
                                <input type="text" id="alloc_discount" onfocus="qtyUnformat(this)"
                                    onblur="qtyFormat(this); calcRemainingAmt();"
                                    oninput="this.value=this.value.replace(/[^0-9.]/g,''); calcRemainingAmt();"
                                    class="block w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm text-right font-mono h-11 px-4 shadow-sm"
                                    value="0.00">
                            </div>
                        </div>

                        {{-- Row 3: Inputs --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Write-off
                                    Amount</label>
                                <input type="text" id="alloc_writeoff" onfocus="qtyUnformat(this)"
                                    onblur="qtyFormat(this); calcRemainingAmt();"
                                    oninput="this.value=this.value.replace(/[^0-9.]/g,''); calcRemainingAmt();"
                                    class="block w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm text-right font-mono h-11 px-4 shadow-sm"
                                    value="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Over/Under
                                    Payment</label>
                                <input type="text" id="alloc_overunder" onfocus="qtyUnformat(this)"
                                    onblur="qtyFormat(this); calcRemainingAmt();"
                                    oninput="this.value=this.value.replace(/[^0-9.-]/g,''); calcRemainingAmt();"
                                    class="block w-full rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm text-right font-mono h-11 px-4 shadow-sm"
                                    value="0.00">
                                <p class="mt-1 text-xs text-gray-400">Can be negative.</p>
                            </div>
                        </div>

                        <hr class="border-gray-100 dark:border-gray-800">

                        {{-- Row 4: Remaining --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Open Amount
                                    (Before) <span class="text-xs font-normal text-gray-500">(readonly)</span></label>
                                <input type="text" id="disp_open_amt" readonly
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 sm:text-sm text-right font-mono h-11 px-4 shadow-sm"
                                    value="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Remaining
                                    Open Amount <span class="text-xs font-normal text-gray-500">(readonly)</span></label>
                                <input type="text" id="alloc_remaining" readonly
                                    class="block w-full rounded-lg border-gray-200 bg-gray-50 focus:ring-0 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 sm:text-sm text-right font-mono font-bold text-brand-600 h-11 px-4 shadow-sm"
                                    value="0.00">
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4">
                         <button type="button" onclick="hideCreateAllocForm()"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit" id="btn_save_alloc"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Save Allocation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

{{-- ── Invoice Selection Modal ── --}}
@if($canEdit)
    <div id="invoiceSelectModal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto p-4" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeInvoiceSelectModal()"></div>
        <div class="relative bg-white rounded-2xl text-left shadow-xl transform transition-all max-w-4xl w-full dark:bg-gray-900 z-10">

                {{-- Header --}}
                <div class="px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select from AR Invoice</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Select an open invoice to allocate</p>
                    </div>
                    <button onclick="closeInvoiceSelectModal()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Search Bar --}}
                <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex gap-3">
                    <input type="text" id="inv_search_input" placeholder="Search by Invoice No (min. {{ $invoiceLookupMinSearchLength }} characters)..."
                        oninput="debounceInvSearch()"
                        class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                </div>

                {{-- Results --}}
                <div class="overflow-y-auto" style="max-height: 55vh;">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Invoice No</th>
                                <th
                                    class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Date</th>
                                <th
                                    class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Grand Total</th>
                                <th
                                    class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Open Amount</th>
                                <th
                                    class="px-4 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-24">
                                    Select</th>
                            </tr>
                        </thead>
                        <tbody id="inv_table_body"
                            class="bg-white divide-y divide-gray-100 dark:bg-gray-900 dark:divide-gray-700">
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-xs text-gray-500">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div class="text-xs text-gray-500 w-1/2" id="inv_pagination_info"></div>
                    <div class="flex items-center gap-1 justify-end w-1/2" id="inv_pagination_btns"></div>
                </div>

        </div>
    </div>
@endif