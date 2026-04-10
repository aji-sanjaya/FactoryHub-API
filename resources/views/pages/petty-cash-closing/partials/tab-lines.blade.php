<div id="lines-tab-container" class="space-y-6">
    
    {{-- Section 1: Add/Edit Line Form --}}
    <div id="line-form-card" class="hidden bg-white dark:bg-gray-900 rounded-xl">
        <div class="p-12">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <span id="form-title">Add New Line</span>
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-10">Enter the details for this petty cash line item.</p>
                </div>
            </div>

            <form id="lineForm" onsubmit="event.preventDefault(); saveLine();" class="space-y-6">
                <input type="hidden" name="line_id" id="line_id" value="">
                <input type="hidden" name="document_id" value="{{ $docIdParam }}">

                <div class="grid grid-cols-1 gap-6"> 
                    <!-- NEW Requisition Info Field (Visible but Readonly) -->
                    <div id="requisition_info_container"
                        class="hidden bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-dashed border-blue-300 dark:border-blue-800 mb-4">
                        <label class="block text-sm font-medium text-blue-800 dark:text-blue-300 mb-1">Linked Request Line</label>
                        <div class="flex items-center gap-2">
                            <span id="requisition_info_text"
                                class="text-sm text-blue-900 dark:text-blue-200 font-medium"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Value --}}
                        <div>
                            <label for="line_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Item <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="line_name" 
                                name="name"
                                {{ $isReadOnly ? 'readonly' : '' }}
                                class="pl-3 block w-full rounded-lg text-sm bg-white border border-gray-300  focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 sm:text-sm h-11 {{ $isReadOnly ? 'bg-gray-50 dark:bg-gray-700/50 cursor-not-allowed' : '' }}"
                                placeholder="Enter item name">
                        </div>

                         {{-- Name --}}
                        {{-- <div>
                            <label for="line_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Item <span class="text-gray-400 text-xs font-normal ml-1">(Optional)</span>
                            </label>
                            <input 
                                type="text" 
                                id="line_name" 
                                name="name"
                                {{ $isReadOnly ? 'readonly' : '' }}
                                class="pl-3 block w-full rounded-lg text-sm bg-white border border-gray-300  focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 sm:text-sm h-11 {{ $isReadOnly ? 'bg-gray-50 dark:bg-gray-700/50 cursor-not-allowed' : '' }}"
                                placeholder="Enter name">
                        </div> --}}

                        {{-- Amount --}}
                        <div>
                            <label for="line_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span id="currency_symbol" class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input 
                                    type="text" 
                                    id="line_amount" 
                                    name="amount" 
                                    step="0.01" 
                                    min="0.01"
                                    required
                                    {{ $isReadOnly ? 'readonly' : '' }}
                                    class="block w-full pl-10 pr-3 rounded-lg text-sm bg-white border border-gray-300  focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 sm:text-sm h-11 shadow-sm text-right {{ $isReadOnly ? 'bg-gray-50 dark:bg-gray-700/50 cursor-not-allowed' : '' }}"
                                    placeholder="0.00"
                                    inputmode="decimal"
                                    oninput="formatNumber(this);">
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="line_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            Description <span class="text-gray-400 text-xs font-normal ml-1">(Optional)</span>
                        </label>
                        <textarea 
                            id="line_description" 
                            name="description" 
                            rows="3" 
                            {{ $isReadOnly ? 'readonly' : '' }}
                            class="block w-full rounded-lg text-sm bg-white border border-gray-300  focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400  sm:text-sm p-3  {{ $isReadOnly ? 'bg-gray-50 dark:bg-gray-700/50 cursor-not-allowed' : '' }}"
                            placeholder="Describe the petty cash item..."></textarea>
                    </div> 
                </div>

                {{-- Form Actions --}}
                @if(!$isReadOnly)
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        {{-- Delete Button (Left) - Only show when editing --}}
                        <button 
                            type="button" 
                            id="deleteLineBtn"
                            onclick="deleteCurrentLine()"
                            style="display: none;"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-500/30 shadow-sm transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </button>
                        
                        {{-- Right Side Buttons --}}
                        <div class="flex items-center gap-3 ml-auto">
                            <button 
                                type="button" 
                                onclick="clearLineForm()"
                                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                id="saveLineBtn"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/30 shadow-sm transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span id="saveLineBtnText">Add Line</span>
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Section 2: Lines Table --}}
    <div id="lines-table-card" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm">
        {{-- Table Header Controls --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 p-5 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-4">  
                {{-- Per Page --}}
                <div class="relative">
                    <select
                        name="per_page_lines"
                        onchange="handlePerPageLines(this.value)"
                        class="border border-gray-200 dark:border-gray-700 h-10 pl-3 pr-8 text-sm bg-gray-50 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all cursor-pointer dark:bg-gray-800 dark:text-gray-300">
                        <option value="10" {{ request('per_page_lines') == 10 ? 'selected' : '' }}>10 rows</option>
                        <option value="25" {{ request('per_page_lines') == 25 ? 'selected' : '' }}>25 rows</option>
                        <option value="50" {{ request('per_page_lines') == 50 ? 'selected' : '' }}>50 rows</option>
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
                        class="block w-full sm:w-64 pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 dark:border-gray-800 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all dark:bg-gray-800  dark:text-gray-200 placeholder-gray-400"
                        placeholder="Search description..."
                        onkeydown="if(event.key==='Enter'){event.preventDefault();handleSearchLines();}">
                </div>

                @if(!$isReadOnly)
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            id="deleteSelectedBtn"
                            onclick="deleteSelectedLines()"
                            style="display: none;"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span id="deleteSelectedText">Delete Selected</span>
                        </button>

                        <button type="button" onclick="openRequestModal()"
                            class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            From Request
                        </button>

                        <button type="button" onclick="showLineForm()"
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
        @if(isset($lines))
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 text-left">
                            @if(!$isReadOnly)
                                <th scope="col" class="w-12 px-4 py-3">
                                    <input 
                                        type="checkbox" 
                                        id="selectAllLines" 
                                        onclick="toggleSelectAllLines(this)"
                                        class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700">
                                </th>
                            @endif
                            <th scope="col" class="min-w-[50px] px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                NO
                            </th>
                            <th scope="col" class="min-w-[120px] px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Item
                            </th>
                            <th scope="col" class="min-w-[250px] px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col" class="min-w-[120px] px-4 py-3 text-xs font-medium text-right text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Amount
                            </th>
                            @if(!$isReadOnly)
                                <th scope="col" class="min-w-[100px] px-4 py-3 text-xs font-medium text-right text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($lines as $index => $line)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                @if(!$isReadOnly)
                                    <td class="w-12 px-4 py-4">
                                        <input 
                                            type="checkbox"
                                            class="line-checkbox w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700"
                                            value="{{ $line->dpk_pettycash_closingline_id }}"
                                            onchange="updateDeleteButtonState()">
                                    </td>
                                @endif
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 font-medium">
                                    {{ $lines->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 font-mono">
                                    {{ $line->name ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                    <div class="max-w-xs">
                                        {{ $line->description }}
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white font-mono">
                                    {{ number_format($line->amount, 0, '.', ',') }}
                                </td>
                                @if(!$isReadOnly)
                                    <td class=" whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-1">
                                            <button 
                                                type="button"
                                                onclick='editLine({{ json_encode($line) }})'
                                                class="p-1 bg-yellow-200 text-yellow-600 hover:text-yellow-700 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-sm transition-colors"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                            </button>
                                            <button 
                                                type="button"
                                                onclick="deleteLine({{ $line->dpk_pettycash_closingline_id }})"
                                                class="mr-4 bg-red-200 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-sm transition-colors p-1"
                                                title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                    <td colspan="{{ $isReadOnly ? '4' : '6' }}" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 dark:bg-gray-800">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No petty cash lines yet</h3>
                                        <p class="text-gray-500 text-sm mb-6 dark:text-gray-400">Add your first petty cash line item to get started.</p>
                                        @if(!$isReadOnly)
                                            <button type="button" onclick="showLineForm()"
                                                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-brand-700 bg-brand-100 hover:bg-brand-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                                Add First Line
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    
                    {{-- Total Footer --}}
                    @if($lines->count() > 0)
                        <tfoot class="border-t-2 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <td colspan="{{ $isReadOnly ? '3' : '4' }}" class="px-4 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Total Amount:
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-white font-mono">
                                    {{ number_format($lines->sum('amount'), 0, '.', ',') }}
                                </td>
                                @if(!$isReadOnly)
                                    <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            {{-- Pagination Footer --}}
            @if($lines->total() > 0)
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Showing <span class="font-medium text-gray-900 dark:text-white">{{ $lines->firstItem() }}</span> to
                        <span class="font-medium text-gray-900 dark:text-white">{{ $lines->lastItem() }}</span> of 
                        <span class="font-medium text-gray-900 dark:text-white">{{ $lines->total() }}</span> results
                    </div>
                    <div class="w-full sm:w-auto">
                        {{ $lines->appends(request()->except('lines_page'))->links('vendor.pagination.simple-tailwind') }}
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<script>
/**
 * Save or update a line item
 */
function saveLine() {
    var form = document.getElementById('lineForm');
    var formData = new FormData(form);
    var lineId = document.getElementById('line_id').value;

    var url = lineId
        ? '{{ route("petty-cash-closing.line.update") }}'
        : '{{ route("petty-cash-closing.line.store") }}';

    var method = lineId ? 'PUT' : 'POST';

    // Add CSRF token
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Convert FormData to JSON
    var data = {};
    formData.forEach(function (value, key) {
        data[key] = value;
    });

    if (data.amount) {
        data.amount = String(data.amount).replace(/,/g, '');
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: lineId ? 'Updating...' : 'Saving...',
            text: 'Please wait',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });
    }

    var responseOk = false;

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(function (response) {
        responseOk = response.ok;
        return response.json();
    })
    .then(function (data) {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        if (data && (data.success === true || responseOk)) {
            // Show success message
            showToast('Success', data.message || 'Line saved successfully', 'success');
            // Clear form
            clearLineForm();
            // Show loading state before returning to table
            var tableCard = document.getElementById('lines-table-card');
            if (tableCard) {
                tableCard.innerHTML = '<div class="flex flex-col justify-center items-center py-24">' +
                    '<div class="rounded-full bg-brand-50 p-4 mb-4">' +
                    '<svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                    '</svg>' +
                    '</div>' +
                    '<span class="text-gray-500 font-medium dark:text-gray-400">Loading details...</span>' +
                    '</div>';
            }
            // Reload lines table without full page refresh
            setTimeout(function () {
                if (typeof loadTabContent === 'function') {
                    loadTabContent('lines');
                }
            }, 800);
        } else {
            showToast('Error', data.message || 'Failed to save line', 'error');
        }
    })
    .catch(function (error) {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        showToast('Error', 'An error occurred while saving the line', 'error');
    });
}

/**
 * Populate form for editing a line
 */
function editLine(lineData) {
    var formCard = document.getElementById('line-form-card');
    var tableCard = document.getElementById('lines-table-card');
    if (formCard) {
        formCard.classList.remove('hidden');
    }
    if (tableCard) {
        tableCard.classList.add('hidden');
    }
    // Scroll to form
    document.getElementById('line-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Populate form fields
    var lineIdEl = document.getElementById('line_id');
    var lineDescEl = document.getElementById('line_description');
    var lineAmountEl = document.getElementById('line_amount');
    var lineNameEl = document.getElementById('line_name');

    if (lineIdEl) lineIdEl.value = lineData.dpk_pettycash_closingline_id;
    if (lineDescEl) lineDescEl.value = lineData.description || '';
    if (lineAmountEl) {
        lineAmountEl.value = lineData.amount || '';
        if (lineData.amount && parseFloat(lineData.amount) > 0) {
            formatNumber(lineAmountEl);
        }
    }
    if (lineNameEl) lineNameEl.value = lineData.name || '';
    
    // Request Info logic
    var reqInfoContainer = document.getElementById('requisition_info_container');
    var reqInfoText = document.getElementById('requisition_info_text');
    if (lineData.dpk_pettycash_requestline_id && lineData.request_documentno) {
        reqInfoContainer.classList.remove('hidden');
        reqInfoText.textContent = lineData.request_documentno + ' Line No - ' + (lineData.request_line || '-');
    } else if (lineData.dpk_pettycash_requestline_id) {
        reqInfoContainer.classList.remove('hidden');
        reqInfoText.textContent = 'Linked to Request Line ID: ' + lineData.dpk_pettycash_requestline_id;
    } else {
        reqInfoContainer.classList.add('hidden');
        reqInfoText.textContent = '';
    }
    
    // Update form title and button text
    document.getElementById('form-title').textContent = 'Edit Line';
    document.getElementById('saveLineBtnText').textContent = 'Update Line';
    
    // Show delete button when editing
    var deleteBtn = document.getElementById('deleteLineBtn');
    if (deleteBtn) {
        deleteBtn.style.display = 'flex';
    }
}

/**
 * Clear/reset the line form
 */
function clearLineForm(keepVisible) {
    var formCard = document.getElementById('line-form-card');
    var tableCard = document.getElementById('lines-table-card');
    if (formCard && !keepVisible) {
        formCard.classList.add('hidden');
    }
    if (tableCard && !keepVisible) {
        tableCard.classList.remove('hidden');
    }
    document.getElementById('lineForm').reset();
    document.getElementById('line_id').value = '';
    
    // Hide request info container
    var reqInfoContainer = document.getElementById('requisition_info_container');
    var reqInfoText = document.getElementById('requisition_info_text');
    if (reqInfoContainer) reqInfoContainer.classList.add('hidden');
    if (reqInfoText) reqInfoText.textContent = '';
    
    document.getElementById('form-title').textContent = 'Add New Line';
    document.getElementById('saveLineBtnText').textContent = 'Add Line';
    
    // Hide delete button
    var deleteBtn = document.getElementById('deleteLineBtn');
    if (deleteBtn) {
        deleteBtn.style.display = 'none';
    }
}

/**
 * Toggle select all checkboxes
 */
function toggleSelectAllLines(checkbox) {
    const checkboxes = document.querySelectorAll('.line-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateDeleteButtonState();
}

/**
 * Update delete button visibility based on selected checkboxes
 */
function updateDeleteButtonState() {
    const checkboxes = document.querySelectorAll('.line-checkbox:checked');
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    const deleteText = document.getElementById('deleteSelectedText');
    
    if (checkboxes.length > 0) {
        deleteBtn.style.display = 'inline-flex';
        deleteText.textContent = 'Delete Selected (' + checkboxes.length + ')';
    } else {
        deleteBtn.style.display = 'none';
    }
}

/**
 * Delete current line from form (when in edit mode)
 */
function deleteCurrentLine() {
    var lineIdEl = document.getElementById('line_id');
    if (!lineIdEl || !lineIdEl.value) {
        showToast('Warning', 'No line selected', 'warning');
        return;
    }
    
    var lineId = lineIdEl.value;
    deleteLine(lineId);
}

/**
 * Delete a single line
 */
function deleteLine(lineId) {
    Swal.fire({
        title: 'Delete this line?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then(function (result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Deleting...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('{{ route("petty-cash-closing.line.bulkDelete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ line_ids: [lineId] })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                Swal.close();
                if (data && (data.success === true || data.message)) {
                    showToast('Success', data.message || 'Line deleted successfully', 'success');
                    setTimeout(function () {
                        if (typeof loadTabContent === 'function') {
                            loadTabContent('lines');
                        }
                    }, 600);
                } else {
                    showToast('Error', (data && data.message) ? data.message : 'Failed to delete line', 'error');
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                Swal.close();
                showToast('Error', 'An error occurred while deleting line', 'error');
            });
    });
}

/**
 * Delete selected lines (bulk delete)
 */
function deleteSelectedLines() {
    const checkboxes = document.querySelectorAll('.line-checkbox:checked');
    const lineIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (lineIds.length === 0) {
        showToast('Warning', 'Please select at least one line to delete', 'warning');
        return;
    }

    Swal.fire({
        title: 'Delete ' + lineIds.length + ' line(s)?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then(function (result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Deleting...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('{{ route("petty-cash-closing.line.bulkDelete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ line_ids: lineIds })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                Swal.close();
                if (data && (data.success === true || data.message)) {
                    showToast('Success', data.message || 'Lines deleted successfully', 'success');
                    setTimeout(function () {
                        if (typeof loadTabContent === 'function') {
                            loadTabContent('lines');
                        }
                    }, 600);
                } else {
                    showToast('Error', (data && data.message) ? data.message : 'Failed to delete lines', 'error');
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                Swal.close();
                showToast('Error', 'An error occurred while deleting lines', 'error');
            });
    });
}

/**
 * Handle per page change
 */
function handlePerPageLines(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page_lines', perPage);
    window.location.href = url.toString();
}

function handleSearchLines() {
    var input = document.getElementById('q_lines');
    var value = input ? input.value : '';
    var url = new URL(window.location.href);
    url.searchParams.set('q_lines', value);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

function showLineForm() {
    var formCard = document.getElementById('line-form-card');
    var tableCard = document.getElementById('lines-table-card');
    if (formCard) {
        formCard.classList.remove('hidden');
    }
    if (tableCard) {
        tableCard.classList.add('hidden');
    }
    clearLineForm(true);
    if (formCard) {
        formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * Simple toast notification function
 */
function showToast(title, message, type) {
    var icon = 'info';
    if (type === 'success') {
        icon = 'success';
    } else if (type === 'error') {
        icon = 'error';
    } else if (type === 'warning') {
        icon = 'warning';
    }

    var swalOptions = {
        icon: icon,
        title: title,
        text: message,
        confirmButtonColor: '#4f46e5'
    };

    if (type === 'success') {
        swalOptions.timer = 1500;
        swalOptions.showConfirmButton = false;
    }

    Swal.fire(swalOptions);
}

function formatNumber(input) {
    var value = input.value.replace(/,/g, '');

    if (/[^0-9.]/.test(value)) {
        value = value.replace(/[^0-9.]/g, '');
    }

    var parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
        parts = value.split('.');
    }

    if (!value) {
        input.value = '';
        return;
    }

    var integerPart = parts[0];
    var decimalPart = parts.length > 1 ? '.' + parts[1] : '';
    var formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    input.value = formattedInteger + decimalPart;
}

let reqSearchTimeout = null;
let reqCurrentPage = 1;
let reqSelectedLines = new Map();

function openRequestModal() {
    document.getElementById('requestSelectionModal').classList.remove('hidden');
    document.getElementById('req_search_input').value = '';
    reqSelectedLines.clear();
    updateReqSelectedCount();
    loadRequestLines(1);
}

function closeRequestModal() {
    document.getElementById('requestSelectionModal').classList.add('hidden');
}

function debounceReqSearch(val) {
    if (reqSearchTimeout) clearTimeout(reqSearchTimeout);
    reqSearchTimeout = setTimeout(() => {
        loadRequestLines(1, val);
    }, 500);
}

function loadRequestLines(page, search = null) {
    reqCurrentPage = page;
    let query = search !== null ? search : document.getElementById('req_search_input').value;
    let docId = '{{ $docIdParam }}';
    
    document.getElementById('req_loading').classList.remove('hidden');
    document.getElementById('req_empty').classList.add('hidden');
    document.getElementById('req_table').classList.add('hidden');
    
    fetch(`/petty-cash-closing/api/request-lines?document_id=${docId}&page=${page}&q=${encodeURIComponent(query)}`, {
        headers:{ 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('req_loading').classList.add('hidden');
        if (data.results && data.results.length > 0) {
            document.getElementById('req_table').classList.remove('hidden');
            renderRequestTable(data.results);
            renderRequestPagination(data);
        } else {
            document.getElementById('req_empty').classList.remove('hidden');
            document.getElementById('req_table_body').innerHTML = '';
            document.getElementById('req_pagination_btns').innerHTML = '';
            document.getElementById('req_pagination_info').innerHTML = '';
        }
    })
    .catch(e => {
        console.error(e);
        document.getElementById('req_loading').classList.add('hidden');
    });
}

function renderRequestTable(lines) {
    let html = '';
    lines.forEach(l => {
        let isChecked = reqSelectedLines.has(l.dpk_pettycash_requestline_id) ? 'checked' : '';
        let lineData = encodeURIComponent(JSON.stringify(l));
        let qtyStr = l.qty ? l.qty : '-';
        html += `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="px-4 py-3"><input type="checkbox" class="req-line-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600" value="${l.dpk_pettycash_requestline_id}" ${isChecked} onchange="toggleReqLineSelection(this, '${lineData}')"></td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">${l.value || l.name || '-'}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate" title="${l.description || ''}">${l.description || '-'}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white font-mono">${formatAmountModal(l.amount)}</td>
            </tr>
        `;
    });
    document.getElementById('req_table_body').innerHTML = html;
    
    let cbs = document.querySelectorAll('.req-line-checkbox');
    let allChecked = cbs.length > 0 && Array.from(cbs).every(cb => cb.checked);
    document.getElementById('selectAllReqLines').checked = allChecked;
}

function formatAmountModal(amt) {
    if(!amt) return '0';
    return parseFloat(amt).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function toggleReqLineSelection(cb, lineDataStr) {
    let id = parseInt(cb.value);
    if (cb.checked) {
        reqSelectedLines.set(id, JSON.parse(decodeURIComponent(lineDataStr)));
    } else {
        reqSelectedLines.delete(id);
    }
    updateReqSelectedCount();
    
    let cbs = document.querySelectorAll('.req-line-checkbox');
    let allChecked = cbs.length > 0 && Array.from(cbs).every(cb => cb.checked);
    document.getElementById('selectAllReqLines').checked = allChecked;
}

function toggleSelectAllReqLines(masterCb) {
    let cbs = document.querySelectorAll('.req-line-checkbox');
    cbs.forEach(cb => {
        if (cb.checked !== masterCb.checked) {
            cb.checked = masterCb.checked;
            cb.dispatchEvent(new Event('change'));
        }
    });
}

function updateReqSelectedCount() {
    let count = reqSelectedLines.size;
    let text = count === 0 ? 'No line selected' : `${count} line${count > 1 ? 's' : ''} selected`;
    document.getElementById('req_selected_count').textContent = text;
    document.getElementById('req_confirm_btn').disabled = count === 0;
}

function renderRequestPagination(data) {
    let total = data.total;
    let page = data.page;
    let perPage = data.per_page;
    let start = ((page - 1) * perPage) + 1;
    let end = Math.min(page * perPage, total);
    
    document.getElementById('req_pagination_info').innerHTML = `Showing <span class="font-medium text-gray-900 dark:text-white">${start}</span> to <span class="font-medium text-gray-900 dark:text-white">${end}</span> of <span class="font-medium text-gray-900 dark:text-white">${total}</span> results`;
    
    let btnsHtml = '';
    if (page > 1) {
        btnsHtml += `<button type="button" onclick="loadRequestLines(${page - 1})" class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700">Prev</button>`;
    }
    if (end < total) {
        btnsHtml += `<button type="button" onclick="loadRequestLines(${page + 1})" class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700">Next</button>`;
    }
    document.getElementById('req_pagination_btns').innerHTML = btnsHtml;
}

function confirmRequestAdd() {
    if (reqSelectedLines.size === 0) return;
    
    let lines = Array.from(reqSelectedLines.values());
    let docId = '{{ $docIdParam }}';
    
    Swal.fire({
        title: 'Adding lines...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    fetch('{{ route("petty-cash-closing.line.bulkStore") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            document_id: docId,
            request_lines: lines
        })
    })
    .then(r => r.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            closeRequestModal();
            showToast('Success', data.message, 'success');
            setTimeout(() => {
                if (typeof loadTabContent === 'function') {
                    loadTabContent('lines');
                }
            }, 500);
        } else {
            showToast('Error', data.message || 'Failed to add lines', 'error');
        }
    })
    .catch(e => {
        console.error(e);
        Swal.close();
        showToast('Error', 'An error occurred while adding lines', 'error');
    });
}
</script>

<!-- Request Modal -->
<div id="requestSelectionModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" aria-hidden="true" onclick="closeRequestModal()"></div>
        <!-- Modal Panel -->
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col z-10" style="max-height: 85vh;">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Select from Request</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Lines from the linked Petty Cash Request</p>
                    </div>
                </div>
                <button type="button" onclick="closeRequestModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Search & Filter Bar -->
            <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="req_search_input" placeholder="Search by details..." class="block w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" oninput="debounceReqSearch(this.value)">
                    </div>
                    <button type="button" onclick="loadRequestLines(1)" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 rounded-lg border border-blue-200 dark:border-blue-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh
                    </button>
                </div>
            </div>
            <!-- Table Body -->
            <div class="flex-1 overflow-y-auto" style="min-height: 200px;">
                <div id="req_loading" class="hidden py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-3">
                        <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-sm text-gray-500">Loading request lines...</span>
                    </div>
                </div>
                <!-- Empty State -->
                <div id="req_empty" class="hidden py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-3">
                        <div class="w-14 h-14 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No unclosed lines found.</p>
                    </div>
                </div>
                <!-- Table -->
                <table class="w-full text-sm" id="req_table">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 z-10">
                        <tr>
                            <th class="w-10 px-4 py-3"><input type="checkbox" id="selectAllReqLines" onclick="toggleSelectAllReqLines(this)" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="req_table_body" class="divide-y divide-gray-100 dark:divide-gray-800">
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500 dark:text-gray-400" id="req_pagination_info">&nbsp;</div>
                    <div class="flex items-center gap-1" id="req_pagination_btns"></div>
                </div>
            </div>
            <!-- Action Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                <span id="req_selected_count" class="text-sm text-gray-500 dark:text-gray-400">No line selected</span>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRequestModal()" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                    <button type="button" onclick="confirmRequestAdd()" id="req_confirm_btn" disabled class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
