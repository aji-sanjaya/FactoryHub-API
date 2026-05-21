@php
    $materialReceiptConfig = $materialReceiptConfig ?? config('idempiere.create-gr');
    $isNew = is_null($receipt);
    $isReadOnly = !$isNew && in_array($receipt->docstatus, $materialReceiptConfig['statuses']['read_only'] ?? [], true);
    $isDraft = !$isNew && in_array($receipt->docstatus, $materialReceiptConfig['statuses']['draft'] ?? ['DR'], true);
    $cs = $receipt->docstatus ?? 'DR';
@endphp

<div class="p-6 sm:p-8">

    <!-- Lines Table Section (hidden when form is open) -->
    <div id="lines-table-section">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                    Receipt Lines
                </h3>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400">
                    {{ count($lines) }} item(s)
                </span>
            </div>
            @if(!$isReadOnly)
                <div class="flex items-center gap-2">
                    <button onclick="openPoModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm hover:shadow transition-all dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        From PO
                    </button>
                    <!-- <button onclick="showCreateLineForm()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 shadow-sm hover:shadow transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Line
                        </button> -->
                </div>
            @endif
        </div>

        <!-- Lines Table -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-12">
                            #</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Product</th>
                        <th
                            class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-28">
                            Qty</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-20">
                            UOM</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            PO Reference</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            Description</th>
                        @if(!$isReadOnly)
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-24">
                                Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="lines-tbody" class="bg-white divide-y divide-gray-100 dark:bg-gray-900 dark:divide-gray-700">
                    @forelse($lines as $idx => $line)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            id="line-row-{{ $line->m_inoutline_id }}">
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $line->product_name ?? '-' }}
                                </div>
                                @if($line->product_code ?? null)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $line->product_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">
                                {{ number_format($line->qty ?? $line->movementqty ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $line->uom_symbol ?? $line->uom_name ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $line->po_documentno ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ $line->description ?? '-' }}
                            </td>
                            @if(!$isReadOnly)
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            onclick="editLine({{ $line->m_inoutline_id }}, {{ $line->m_product_id }}, '{{ addslashes($line->product_name ?? '') }}', {{ $line->qty ?? $line->movementqty ?? 0 }}, '{{ addslashes($line->description ?? '') }}', '{{ $line->c_orderline_id ?? '' }}', '{{ addslashes($line->po_documentno ?? '') }}', {{ $line->po_line ?? 'null' }})"
                                            class="btn btn-sm btn-warning bg-yellow-100 me-1 p-2 rounded-sm" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                        </button>
                                        <button onclick="deleteLine({{ $line->m_inoutline_id }})"
                                            class="btn btn-sm btn-danger rounded-sm bg-red-100 p-2" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="{{ $isReadOnly ? 6 : 7 }}" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div
                                        class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m0 8H9m4 0h-4" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">No receipt lines yet</span>
                                    @if(!$isReadOnly)
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Click "Add Line" or "From PO" to
                                            get started</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div><!-- /#lines-table-section -->

    <!-- Create / Edit Line Form (inline) -->
    @if(!$isReadOnly)
        <div id="lines-create-form" class="hidden">
            <div
                class="bg-gray-50/50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8 mt-5">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 id="line-form-title"
                            class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            New Receipt Line
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-10">Search for a product and specify
                            quantity to add to this receipt.</p>
                    </div>
                    <button type="button" onclick="hideCreateLineForm()" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="createLineForm" onsubmit="event.preventDefault(); saveLine();" class="space-y-6">
                    <input type="hidden" name="document_id" value="{{ $docIdParam }}">
                    <input type="hidden" name="line_id" id="editing_line_id" value="">
                    <input type="hidden" id="c_orderline_id" value="">

                    <div
                        class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-6">

                        <!-- Product -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Select Product <span class="text-red-500">*</span>
                            </label>
                            <select id="m_product_id" name="m_product_id" class="w-full" required disabled></select>
                            <p class="mt-1 text-xs text-gray-400">Product is set from PO or product selection and cannot be
                                changed here.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Qty -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Quantity <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" id="line_qty" name="qty" required onfocus="qtyUnformat(this)"
                                        onblur="qtyFormat(this)" oninput="this.value=this.value.replace(/[^0-9.]/g,'')"
                                        class="block w-full rounded-lg border-gray-300 pl-4 pr-12 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm"
                                        placeholder="0.00">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span id="product_uom" class="text-gray-400 sm:text-sm">Unit</span>
                                    </div>
                                </div>
                            </div>

                            <!-- PO Reference -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">PO
                                    Reference</label>
                                <div id="po_ref_display"
                                    class="flex items-center h-11 px-4 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 shadow-sm">
                                    - None -
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
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between gap-3 pt-4">
                        <!-- Left: Delete (edit mode only) -->
                        <div>
                            <button type="button" id="form_delete_line_btn" onclick="deleteCurrentLine()"
                                class="hidden px-5 py-2.5 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors shadow-sm dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/40 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Save Line Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<!-- PO Selection Modal -->
@if(!$isReadOnly)
    <div id="poModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div
            class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 backdrop-blur-sm">
            <div class="fixed inset-0 bg-gray-900/60" onclick="closePoModal()"></div>
            <div
                class="relative inline-block align-bottom bg-white rounded-2xl text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full dark:bg-gray-900">
                <div class="px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select from Purchase Order</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Choose PO lines to receive</p>
                    </div>
                    <button onclick="closePoModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Search & filters -->
                <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex gap-3">
                    <input type="text" id="po-search" placeholder="Search by PO number or product (min. 3 chars)..."
                        oninput="debouncePoSearch()"
                        class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                    <button onclick="loadPoLines()"
                        class="px-4 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition-colors">
                        Search
                    </button>
                </div>

                <!-- Results -->
                <div class="overflow-y-auto" style="max-height: 55vh;">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                            <tr>
                                <th
                                    class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    PO Number</th>
                                <th
                                    class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Product</th>
                                <th
                                    class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Ordered Qty</th>
                                <th
                                    class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Received</th>
                                <th
                                    class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                    Remaining</th>
                                <th
                                    class="px-3 py-2 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-20">
                                    Select</th>
                            </tr>
                        </thead>
                        <tbody id="po-lines-tbody"
                            class="bg-white divide-y divide-gray-100 dark:bg-gray-900 dark:divide-gray-700">
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-gray-400">
                                    Use search above to find PO lines
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button onclick="closePoModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if(!$isReadOnly)
    <script>
        const DOC_ID = '{{ $docIdParam }}';
        const LINES_URL = '{{ route("material-receipt.line.store") }}';
        const PO_API_URL = '{{ route("material-receipt.api.po-lines") }}';

        // ── Line Form ───────────────────────────────────────────────────────────────
        function showCreateLineForm() {
            document.getElementById('editing_line_id').value = '';
            document.getElementById('c_orderline_id').value = '';
            document.getElementById('line_qty').value = '';
            document.getElementById('line_description').value = '';
            document.getElementById('po_ref_display').textContent = '- None -';
            document.getElementById('form_delete_line_btn').classList.add('hidden');
            const titleEl = document.getElementById('line-form-title');
            titleEl.innerHTML = `<div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></div> New Receipt Line`;
            if (typeof $ !== 'undefined') {
                $('#m_product_id').val(null).trigger('change');
                $('#product_uom').text('Unit');
            }
            document.getElementById('lines-table-section').classList.add('hidden');
            document.getElementById('lines-create-form').classList.remove('hidden');
            document.getElementById('lines-create-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function editLine(lineId, productId, productName, qty, description, orderlineId, poDocumentno, poLine) {
            document.getElementById('editing_line_id').value = lineId;
            document.getElementById('c_orderline_id').value = orderlineId || '';
            const qtyEl = document.getElementById('line_qty');
            qtyEl.value = qty;
            qtyFormat(qtyEl);
            document.getElementById('line_description').value = description || '';
            document.getElementById('form_delete_line_btn').classList.remove('hidden');
            const titleEl = document.getElementById('line-form-title');
            titleEl.innerHTML = `<div class="w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></div> Edit Receipt Line`;

            const poRef = (poDocumentno && poLine)
                ? poDocumentno + ' - Line ' + poLine
                : (poDocumentno || (orderlineId ? 'Linked to PO' : '- None -'));
            document.getElementById('po_ref_display').textContent = poRef;

            if (typeof $ !== 'undefined' && productId) {
                const opt = new Option(productName, productId, true, true);
                $('#m_product_id').append(opt).trigger('change');
            }

            document.getElementById('lines-table-section').classList.add('hidden');
            document.getElementById('lines-create-form').classList.remove('hidden');
            document.getElementById('lines-create-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideCreateLineForm() {
            document.getElementById('lines-create-form').classList.add('hidden');
            if (typeof loadTabContent === 'function') {
                loadTabContent('lines');
            } else {
                document.getElementById('lines-table-section').classList.remove('hidden');
            }
        }

        function deleteCurrentLine() {
            const lineId = document.getElementById('editing_line_id').value;
            if (!lineId) return;
            hideCreateLineForm();
            deleteLine(lineId);
        }

        function saveLine() {
            const editingId = document.getElementById('editing_line_id').value;
            const productId = $('#m_product_id').val();
            const qty = document.getElementById('line_qty')?.value;
            const desc = document.getElementById('line_description')?.value;
            const orderlineId = document.getElementById('c_orderline_id')?.value;

            if (!productId || !qty) {
                Swal.fire({ icon: 'warning', title: 'Required', text: 'Please select a product and enter a quantity.' });
                return;
            }

            const payload = {
                document_id: DOC_ID,
                m_product_id: productId,
                qty: parseFloat((qty || '0').replace(/,/g, '')) || 0,
                description: desc || '',
                c_orderline_id: orderlineId || null,
                _token: '{{ csrf_token() }}'
            };

            const isEdit = editingId && editingId !== '';

            if (isEdit) {
                payload.line_id = editingId;
                axios.put('{{ route("material-receipt.line.update") }}', payload)
                    .then(() => {
                        Swal.fire({ icon: 'success', title: 'Saved!', timer: 900, showConfirmButton: false })
                            .then(() => { hideCreateLineForm(); });
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Failed to update line.' });
                    });
            } else {
                axios.post(LINES_URL, payload)
                    .then(() => {
                        Swal.fire({ icon: 'success', title: 'Added!', timer: 900, showConfirmButton: false })
                            .then(() => { hideCreateLineForm(); });
                    })
                    .catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Failed to save line.' });
                    });
            }
        }

        function deleteLine(lineId) {
            Swal.fire({
                title: 'Delete Line?',
                text: 'This line will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    axios.delete('{{ route("material-receipt.line.delete") }}', {
                        data: { line_ids: [lineId], _token: '{{ csrf_token() }}' }
                    }).then(() => {
                        if (typeof loadTabContent === 'function') {
                            loadTabContent('lines');
                        } else {
                            window.location.reload();
                        }
                    }).catch(err => {
                        Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.message || 'Failed to delete line.' });
                    });
                }
            });
        }

        function updateLineCount() {
            const rowCount = document.querySelectorAll('#lines-tbody tr[id^="line-row-"]').length;
            const badge = document.querySelector('.bg-brand-100.text-brand-700');
            if (badge) badge.textContent = rowCount + ' item(s)';
            if (rowCount === 0) {
                const tbody = document.getElementById('lines-tbody');
                if (tbody) {
                    tbody.innerHTML = `<tr id="empty-row">
                        <td colspan="7" class="px-4 py-10 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">No receipt lines yet</span>
                            </div>
                        </td>
                    </tr>`;
                }
            }
        }

        // ── PO Modal ────────────────────────────────────────────────────────────────
        let _poSearchTimer = null;
        function debouncePoSearch() {
            clearTimeout(_poSearchTimer);
            _poSearchTimer = setTimeout(() => loadPoLines(), 400);
        }

        // ── Qty Formatter ───────────────────────────────────────────────────────────
        function qtyFormat(input) {
            const raw = parseFloat((input.value || '0').replace(/,/g, ''));
            if (!isNaN(raw) && raw > 0) {
                input.value = raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
        function qtyUnformat(input) {
            input.value = (input.value || '').replace(/,/g, '');
        }

        function openPoModal() {
            document.getElementById('poModal').classList.remove('hidden');
            document.getElementById('po-search').value = '';
            const tbody = document.getElementById('po-lines-tbody');
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-xs text-gray-400">Type at least 3 characters to search PO lines</td></tr>';
        }
        function closePoModal() {
            document.getElementById('poModal').classList.add('hidden');
        }

        function loadPoLines() {
            const q = document.getElementById('po-search')?.value || '';
            const tbody = document.getElementById('po-lines-tbody');
            if (q.length < 3) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-xs text-gray-400">Type at least 3 characters to search PO lines</td></tr>';
                return;
            }
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-xs text-gray-400">Loading...</td></tr>';

            const poParams = { q: q };
            if (DOC_ID && DOC_ID !== 'new') poParams.document_id = DOC_ID;
            axios.get(PO_API_URL, { params: poParams })
                .then(res => {
                    const lines = res.data.data || [];
                    if (lines.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-xs text-gray-400">No PO lines found</td></tr>';
                        return;
                    }
                    tbody.innerHTML = lines.map(l => `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-3 py-2 text-xs font-medium text-gray-900 dark:text-white">${l.po_documentno || '-'}</td>
                            <td class="px-3 py-2">
                                <div class="text-xs text-gray-900 dark:text-white">${l.product_name || '-'}</div>
                                ${l.product_code ? `<div class="text-[10px] text-gray-400">${l.product_code}</div>` : ''}
                            </td>
                            <td class="px-3 py-2 text-xs text-right text-gray-900 dark:text-white">${Number(l.ordered_qty || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                            <td class="px-3 py-2 text-xs text-right text-gray-500 dark:text-gray-400">${Number(l.received_qty || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                            <td class="px-3 py-2 text-xs text-right font-medium ${(l.remaining_qty || 0) > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'}">${Number(l.remaining_qty || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                            <td class="px-3 py-2 text-center">
                                <button onclick="selectPoLine(${JSON.stringify(l).replace(/"/g, '&quot;')})"
                                    class="px-2.5 py-1 text-[11px] font-medium text-white bg-brand-600 hover:bg-brand-700 rounded transition-colors ${(l.remaining_qty || 0) <= 0 ? 'opacity-50 cursor-not-allowed' : ''}"
                                    ${(l.remaining_qty || 0) <= 0 ? 'disabled' : ''}>
                                    Select
                                </button>
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-xs text-red-400">Failed to load PO lines</td></tr>';
                });
        }

        function selectPoLine(line) {
            closePoModal();
            showCreateLineForm();

            document.getElementById('c_orderline_id').value = line.c_orderline_id;
            const qtyEl2 = document.getElementById('line_qty');
            qtyEl2.value = line.remaining_qty || line.ordered_qty;
            qtyFormat(qtyEl2);
            const poRefText = (line.po_documentno && line.po_line)
                ? line.po_documentno + ' - Line ' + line.po_line
                : (line.po_documentno || 'Linked PO');
            document.getElementById('po_ref_display').textContent = poRefText;
            const titleEl = document.getElementById('line-form-title');
            titleEl.innerHTML = `<div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div> Receive from PO: ${line.po_documentno || ''}`;

            if (typeof $ !== 'undefined' && line.m_product_id) {
                const opt = new Option(line.product_name + (line.product_code ? ' (' + line.product_code + ')' : ''), line.m_product_id, true, true);
                $('#m_product_id').append(opt).trigger('change');
            }
        }
    </script>
@endif