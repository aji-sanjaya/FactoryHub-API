@php
    $requisitionConfig = $requisitionConfig ?? config('idempiere.create-pr');
    $lineEditableStatuses = $requisitionConfig['statuses']['line_editable'] ?? [];
    $linePerPageOptions = $requisitionConfig['limits']['line_per_page_options'] ?? [10, 25, 50, 100];
    $defaultLinePerPage = $requisitionConfig['limits']['line_default_per_page'] ?? 10;
@endphp

<!-- Lines List Container -->
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
            @if(isset($requisition) && in_array($requisition->docstatus, $lineEditableStatuses, true))
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
                            {{-- <th scope="col"
                                class="min-w-[100px] px-6 py-4 font-medium text-right text-gray-500 text-theme-sm dark:text-gray-400">
                                Total</th> --}}
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
                                        value="{{ $line->m_requisitionline_id }}" onchange="updateDeleteLinesButtonState()">
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
                                {{-- <td
                                    class="px-6 py-4 whitespace-nowrap text-theme-xs text-right font-bold text-gray-900 dark:text-white font-mono bg-brand-50/10 dark:bg-brand-900/10">
                                    {{ number_format($line->linenetamt, 2) }}
                                </td> --}}
                                <td class="px-6 py-4 text-theme-xs text-gray-500 dark:text-gray-400">
                                    <div class="truncate max-w-xs whitespace-nowrap" title="{{ $line->description }}">
                                        {{ $line->description ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(!$isReadOnly)
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                onclick="editLine({{ $line->m_requisitionline_id }}, '{{ $line->product_code }} - {{ $line->product_name }}', {{ $line->m_product_id }}, {{ $line->qty }}, {{ $line->priceactual }}, '{{ $line->description }}', {{ $line->line }}, '{{ $line->uom_name }}')"
                                                class="btn btn-sm btn-warning bg-yellow-100 me-1 p-2 rounded-sm" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button type="button" onclick="deleteLine({{ $line->m_requisitionline_id }})"
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
                                            requisition to get started.</p>
                                        @if(isset($requisition) && in_array($requisition->docstatus, $lineEditableStatuses, true))
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
                        New Requisition Line
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
                <input type="hidden" name="document_id" value="{{ $docIdParam }}">
                <input type="hidden" name="line_id" id="line_id" value="">

                <div
                    class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 space-y-6">
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
                                <input type="text" id="line_qty" name="qty" required oninput="formatNumber(this)"
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
                                <input type="text" id="line_price" name="price" oninput="formatNumber(this)"
                                    class="block w-full rounded-lg border-gray-300 pl-10 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white sm:text-sm h-11 shadow-sm"
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
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4">
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
            </form>
        </div>
    </div>
</div>