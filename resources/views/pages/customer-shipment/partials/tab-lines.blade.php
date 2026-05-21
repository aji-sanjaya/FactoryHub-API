<!-- Lines List Container -->
@php
    $customerShipmentConfig = config('idempiere.customer-shipment');
@endphp
<div id="lines-list-container" class="space-y-6">
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
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 rows</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 rows</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 rows</option>
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
            @if(isset($shipment) && in_array($shipment->docstatus, $customerShipmentConfig['statuses']['editable_lines']))
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
                    <button type="button" onclick="openFromSOModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md transition-all focus:ring-4 focus:ring-green-500/30 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>From SO</span>
                    </button>
                    <button type="button" onclick="showCreateLineForm()"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md transition-all focus:ring-4 focus:ring-brand-500/30 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4"></path>
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
                                        value="{{ $line->m_inoutline_id }}" onchange="updateDeleteLinesButtonState()">
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
                                <td class="px-6 py-4 text-theme-xs text-gray-500 dark:text-gray-400">
                                    <div class="truncate max-w-xs whitespace-nowrap" title="{{ $line->description }}">
                                        {{ $line->description ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(!$isReadOnly)
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                onclick="editLine({{ $line->m_inoutline_id }}, '{{ addslashes($line->product_code) }} - {{ addslashes($line->product_name) }}', {{ $line->m_product_id }}, {{ $line->qty }}, '{{ addslashes($line->description) }}', {{ $line->line }}, '{{ $line->uom_name }}', {{ $line->uom_precision ?? 2 }}, '{{ $line->so_documentno ?? '' }}', {{ $line->c_orderline_id ?? 'null' }})"
                                                class="btn btn-sm btn-warning bg-yellow-100 me-1 p-2 rounded-sm" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button type="button" onclick="deleteLine({{ $line->m_inoutline_id }})"
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
                                <td colspan="7" class="px-6 py-16 text-center">
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
                                            shipment to get started.</p>
                                        @if(isset($shipment) && in_array($shipment->docstatus, $customerShipmentConfig['statuses']['editable_lines']))
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
        </div>
    @endif
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
                        New Shipment Line
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-10">Search for a product and specify
                        quantity to add to the shipment.</p>
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
                <input type="hidden" name="line_id" id="line_id" value="">
                <input type="hidden" name="c_orderline_id" id="c_orderline_id" value="">

                <div
                    class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-6">

                    <!-- Linked SO Info (shown in edit mode when line is linked to SO) -->
                    <div id="linkedSOInfo" class="hidden">
                        <div class="rounded-lg border-2 border-dashed border-brand-200 bg-brand-50/50 dark:bg-brand-900/10 dark:border-brand-700 px-4 py-3">
                            <h4 class="text-sm font-semibold text-brand-700 dark:text-brand-400">Linked Sales Order</h4>
                            <p id="linkedSOText" class="text-sm text-brand-600 dark:text-brand-300 mt-0.5"></p>
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
                                <input type="text" id="line_qty" name="qty" required oninput="formatNumber(this)" onblur="enforceDecimals(this)"
                                    class="block w-full rounded-lg border-gray-300 pl-4 pr-12 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm"
                                    placeholder="0.00">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <span id="product_uom" class="text-gray-400 sm:text-sm">Unit</span>
                                </div>
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
                    <!-- Left: Delete (only in edit mode) -->
                    <div id="lineDeleteBtnWrapper" class="hidden">
                        <button type="button"
                            onclick="deleteCurrentLine()"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-500/30 shadow-sm transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            Delete Line
                        </button>
                    </div>

                    <!-- Right: Cancel + Save -->
                    <div class="flex items-center gap-3 ml-auto">
                        <button type="button" onclick="hideCreateLineForm()"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
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

<!-- From SO Modal -->
<div id="fromSOModal" class="fixed inset-0 z-999999 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeFromSOModal()"></div>
        <div class="relative w-full bg-white rounded-2xl text-left shadow-xl sm:max-w-5xl dark:bg-gray-900">
            <!-- Header -->
            <div class="px-4 sm:px-6 pt-5 pb-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">Select from Sales Order</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">Choose SO lines to ship</p>
                </div>
                <button onclick="closeFromSOModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 flex-shrink-0 ml-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Filter -->
            <div class="px-4 sm:px-6 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Sales Order</label>
                        <select id="soDocumentFilter" class="w-full text-sm rounded-lg border-gray-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                            <option value="">-- Select Sales Order --</option>
                        </select>
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date Promised</label>
                        <x-form.date-picker id="soDatePromisedFilter" name="so_date_promised" placeholder="Select Date" dateFormat="Y-m-d"/>
                    </div>
                    <div class="sm:col-span-2">
                        <button onclick="loadSOLines()"
                            class="w-full px-4 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition-colors h-11">
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="overflow-y-auto overflow-x-auto" style="max-height: 55vh;">
                <div id="soLinesLoading" class="hidden">
                    <table class="min-w-full"><tbody>
                        <tr><td colspan="8" class="px-4 py-8 text-center text-xs text-gray-400">Loading...</td></tr>
                    </tbody></table>
                </div>

                <div id="soLinesEmpty" class="hidden">
                    <table class="min-w-full"><tbody>
                        <tr><td colspan="8" class="px-4 py-8 text-center text-xs text-gray-400">No SO lines found with remaining balance</td></tr>
                    </tbody></table>
                </div>

                <table id="soLinesTable" class="hidden min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-10">
                                <input type="checkbox" id="soSelectAll" onclick="toggleSOSelectAll(this)"
                                    class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                            </th>
                            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 whitespace-nowrap">Date</th>
                            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 whitespace-nowrap">Product</th>
                            <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 whitespace-nowrap">Ordered</th>
                            <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 whitespace-nowrap">Delivered</th>
                            <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 whitespace-nowrap">Remaining</th>
                            <th class="px-3 py-2 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 whitespace-nowrap">Ship Qty</th>
                            <th class="px-3 py-2 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 whitespace-nowrap">UOM</th>
                        </tr>
                    </thead>
                    <tbody id="soLinesBody" class="bg-white divide-y divide-gray-100 dark:bg-gray-900 dark:divide-gray-700">
                    </tbody>
                </table>

                <div id="soLinesPrompt">
                    <table class="min-w-full"><tbody>
                        <tr><td colspan="8" class="px-4 py-8 text-center text-xs text-gray-400">Select a Sales Order above to view available lines</td></tr>
                    </tbody></table>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="text-xs text-gray-500 dark:text-gray-400 text-center sm:text-left">
                    <span id="soSelectedCount">0</span> line(s) selected
                </div>
                <div class="flex items-center gap-3 justify-end">
                    <button onclick="closeFromSOModal()"
                        class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors text-center">
                        Close
                    </button>
                    <button type="button" onclick="processFromSO()" id="btnProcessFromSO"
                        class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Selected Lines
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .flatpickr-calendar {
        z-index: 9999999 !important;
    }
    #fromSOModal .select2-container .select2-selection--single {
        height: 44px !important;
        display: flex;
        align-items: center;
    }
    #fromSOModal .select2-container .select2-selection--single .select2-selection__arrow {
        height: 44px !important;
    }
</style>

<script>
    function enforceDecimals(input) {
        const raw = input.value.replace(/,/g, '').trim();
        if (raw === '' || isNaN(parseFloat(raw))) return;
        const num = parseFloat(raw);

        // Read precision from data attribute (set when product is selected)
        const attr = input.dataset.precision;
        const decimals = (attr !== undefined && attr !== '') ? parseInt(attr) : 2;

        input.value = num.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function formatNumber(input) {
        const raw = input.value.replace(/,/g, '');
        if (raw === '' || isNaN(parseFloat(raw))) return;
        const parts = raw.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        input.value = parts.join('.');
    }

    // From SO Modal Functions
    let soLinesData = [];

    function openFromSOModal() {
        document.getElementById('fromSOModal').classList.remove('hidden');
        // Init Select2 on SO filter dropdown
        $('#soDocumentFilter').select2({
            width: '100%',
            placeholder: '-- Select Sales Order --',
            allowClear: true,
            dropdownParent: $('#fromSOModal')
        }).off('change.sofilter').on('change.sofilter', function() {
            $('#soDocumentFilter').select2('close');
            const val = $(this).val();
            if (val) {
                loadSOLines();
            } else {
                // Clear table when no SO selected, show prompt
                document.getElementById('soLinesTable').classList.add('hidden');
                document.getElementById('soLinesEmpty').classList.add('hidden');
                document.getElementById('soLinesLoading').classList.add('hidden');
                document.getElementById('soLinesPrompt').classList.remove('hidden');
                soLinesData = [];
                updateSOSelectedCount();
            }
        });

        // Date promised filter change via flatpickr
        $('#soDatePromisedFilter').on('change', function() {
            const soVal = $('#soDocumentFilter').val();
            if (soVal) loadSOLines();
        });
        // Show prompt initially
        document.getElementById('soLinesPrompt').classList.remove('hidden');
        // Load SO document list only (no lines yet)
        loadSODocuments();
    }

    function closeFromSOModal() {
        document.getElementById('fromSOModal').classList.add('hidden');
        if ($('#soDocumentFilter').data('select2')) {
            $('#soDocumentFilter').select2('destroy');
        }
        soLinesData = [];
    }

    function loadSODocuments() {
        // Fetch all SO documents for this bpartner (no filter, just to populate dropdown)
        const params = new URLSearchParams({ document_id: '{{ $docIdParam ?? "" }}' });
        axios.get('{{ route("customer-shipment.api.so-lines") }}?' + params.toString())
            .then(response => {
                const soDocuments = response.data.so_documents || [];
                const $filter = $('#soDocumentFilter');
                $filter.empty().append('<option value="">-- Select Sales Order --</option>');
                soDocuments.forEach(doc => {
                    $filter.append(new Option(doc, doc, false, false));
                });
                $filter.val('').trigger('change.select2');
            })
            .catch(err => console.error('Failed to load SO documents', err));
    }

    function loadSOLines() {
        const filterVal = $('#soDocumentFilter').val() || '';
        if (!filterVal) return; // Do nothing if no SO selected
        const loading = document.getElementById('soLinesLoading');
        const empty = document.getElementById('soLinesEmpty');
        const table = document.getElementById('soLinesTable');
        const body = document.getElementById('soLinesBody');

        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        table.classList.add('hidden');
        document.getElementById('soLinesPrompt').classList.add('hidden');

        const params = new URLSearchParams({
            document_id: '{{ $docIdParam ?? "" }}',
        });
        if (filterVal) params.append('so_document_no', filterVal);
        const dateVal = document.getElementById('soDatePromisedFilter').value;
        if (dateVal) params.append('date_promised', dateVal);

        axios.get('{{ route("customer-shipment.api.so-lines") }}?' + params.toString())
            .then(response => {
                loading.classList.add('hidden');
                soLinesData = response.data.data || [];

                // No need to rebuild dropdown here since it was loaded separately

                if (soLinesData.length === 0) {
                    empty.classList.remove('hidden');
                    updateSOSelectedCount();
                    return;
                }

                // Render table rows
                body.innerHTML = '';
                soLinesData.forEach((line, idx) => {
                    const precision = line.uom_precision || 2;
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors';
                    row.innerHTML = `
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" class="so-line-checkbox w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700"
                                data-index="${idx}" onchange="toggleSORowQty(this)">
                        </td>
                        <td class="px-3 py-2 text-xs font-medium text-gray-900 dark:text-white">${line.date_promised ? new Date(line.date_promised).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-'}<br><span class="text-[10px] text-gray-400">Line ${line.line}</span></td>
                        <td class="px-3 py-2">
                            <div class="text-xs text-gray-900 dark:text-white">${line.product_name}</div>
                            ${line.product_code ? `<div class="text-[10px] text-gray-400">${line.product_code}</div>` : ''}
                        </td>
                        <td class="px-3 py-2 text-xs text-right text-gray-900 dark:text-white">${parseFloat(line.qty_ordered).toLocaleString('en-US', {minimumFractionDigits: precision, maximumFractionDigits: precision})}</td>
                        <td class="px-3 py-2 text-xs text-right text-gray-500 dark:text-gray-400">${parseFloat(line.qty_delivered).toLocaleString('en-US', {minimumFractionDigits: precision, maximumFractionDigits: precision})}</td>
                        <td class="px-3 py-2 text-xs text-right font-medium ${parseFloat(line.qty_balance) > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'}">${parseFloat(line.qty_balance).toLocaleString('en-US', {minimumFractionDigits: precision, maximumFractionDigits: precision})}</td>
                        <td class="px-3 py-2 text-right">
                            <input type="text" class="so-ship-qty w-24 px-2 py-2 text-xs text-right border border-gray-300 rounded focus:ring-1 focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 disabled:opacity-40 disabled:bg-gray-100 dark:disabled:bg-gray-700"
                                data-index="${idx}" data-precision="${precision}" data-max="${line.qty_balance}"
                                value="${parseFloat(line.qty_balance).toLocaleString('en-US', {minimumFractionDigits: precision, maximumFractionDigits: precision})}"
                                onblur="validateSOShipQty(this)" disabled>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 uppercase">${line.uom_name || ''}</td>
                    `;
                    body.appendChild(row);
                });

                table.classList.remove('hidden');
                document.getElementById('soSelectAll').checked = false;
                updateSOSelectedCount();
            })
            .catch(err => {
                loading.classList.add('hidden');
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to Load',
                    text: 'Could not load Sales Order lines. Please try again.',
                    confirmButtonColor: '#4f46e5'
                });
            });
    }

    function toggleSORowQty(checkbox) {
        const idx = checkbox.dataset.index;
        const qtyInput = document.querySelector(`.so-ship-qty[data-index="${idx}"]`);
        if (qtyInput) {
            qtyInput.disabled = !checkbox.checked;
            if (checkbox.checked) qtyInput.focus();
        }
        updateSOSelectedCount();
    }

    function toggleSOSelectAll(checkbox) {
        document.querySelectorAll('.so-line-checkbox').forEach(cb => {
            cb.checked = checkbox.checked;
            const idx = cb.dataset.index;
            const qtyInput = document.querySelector(`.so-ship-qty[data-index="${idx}"]`);
            if (qtyInput) qtyInput.disabled = !checkbox.checked;
        });
        updateSOSelectedCount();
    }

    function validateSOShipQty(input) {
        const raw = parseFloat(input.value.replace(/,/g, '')) || 0;
        const max = parseFloat(input.dataset.max) || 0;
        const precision = parseInt(input.dataset.precision) || 2;
        const val = Math.min(Math.max(raw, 0), max);
        input.value = val.toLocaleString('en-US', {minimumFractionDigits: precision, maximumFractionDigits: precision});
    }

    function updateSOSelectedCount() {
        const checked = document.querySelectorAll('.so-line-checkbox:checked').length;
        document.getElementById('soSelectedCount').textContent = checked;
        document.getElementById('btnProcessFromSO').disabled = checked === 0;
    }

    function processFromSO() {
        const checkedBoxes = document.querySelectorAll('.so-line-checkbox:checked');
        if (checkedBoxes.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Lines Selected',
                text: 'Please select at least one SO line to import.',
                confirmButtonColor: '#4f46e5'
            });
            return;
        }

        const selectedLines = [];
        checkedBoxes.forEach(cb => {
            const idx = parseInt(cb.dataset.index);
            const line = soLinesData[idx];
            if (line) {
                const qtyInput = document.querySelector(`.so-ship-qty[data-index="${idx}"]`);
                const qty = qtyInput ? parseFloat(qtyInput.value.replace(/,/g, '')) || 0 : line.qty_balance;
                if (qty <= 0) return;
                selectedLines.push({
                    c_orderline_id: line.c_orderline_id,
                    m_product_id: line.m_product_id,
                    qty: qty,
                    description: line.description || '',
                });
            }
        });

        closeFromSOModal();

        Swal.fire({
            title: 'Importing...',
            html: `Creating ${selectedLines.length} line(s) from Sales Order.<br><small>Please wait...</small>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        axios.post('{{ route("customer-shipment.line.store-from-so") }}', {
            document_id: '{{ $docIdParam ?? "" }}',
            lines: selectedLines,
        })
        .then(response => {
            const data = response.data;

            let summaryHtml = `<div class="text-center">
                <p class="text-green-600"><strong>Successfully imported:</strong> ${data.imported || 0}</p>`;
            if (data.failed > 0) {
                summaryHtml += `<p class="text-red-600"><strong>Failed:</strong> ${data.failed}</p>`;
                if (data.errors && data.errors.length > 0) {
                    summaryHtml += `<p class="text-sm text-gray-600 mt-2">${data.errors.join('<br>')}</p>`;
                }
            }
            summaryHtml += `</div>`;

            Swal.fire({
                icon: data.failed > 0 ? 'warning' : 'success',
                title: data.failed > 0 ? 'Import Completed with Errors' : 'Import Successful!',
                html: summaryHtml,
                confirmButtonColor: '#4f46e5',
                timer: data.failed > 0 ? undefined : 3000
            }).then(() => {
                loadTabContent('lines');
            });
        })
        .catch(error => {
            console.error(error);
            let errorMessage = 'Failed to import from Sales Order';
            if (error.response && error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Import Failed',
                html: errorMessage,
                confirmButtonColor: '#4f46e5'
            });
        });
    }
</script>
