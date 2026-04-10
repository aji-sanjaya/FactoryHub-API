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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Value --}}
                        <div>
                            <label for="line_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Item <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="line_value" 
                                name="value"
                                {{ $isReadOnly ? 'readonly' : '' }}
                                class="pl-3 block w-full rounded-lg text-sm bg-white border border-gray-300  focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 sm:text-sm h-11 {{ $isReadOnly ? 'bg-gray-50 dark:bg-gray-700/50 cursor-not-allowed' : '' }}"
                                placeholder="Enter value">
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
                                            value="{{ $line->dpk_pettycash_requestline_id }}"
                                            onchange="updateDeleteButtonState()">
                                    </td>
                                @endif
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 font-medium">
                                    {{ $lines->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 font-mono">
                                    {{ $line->value ?? '-' }}
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
                                                onclick="deleteLine({{ $line->dpk_pettycash_requestline_id }})"
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
        ? '{{ route("petty-cash-request.line.update") }}'
        : '{{ route("petty-cash-request.line.store") }}';

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
    var lineValueEl = document.getElementById('line_value');

    if (lineIdEl) lineIdEl.value = lineData.dpk_pettycash_requestline_id;
    if (lineDescEl) lineDescEl.value = lineData.description || '';
    if (lineAmountEl) {
        lineAmountEl.value = lineData.amount || '';
        if (lineData.amount && parseFloat(lineData.amount) > 0) {
            formatNumber(lineAmountEl);
        }
    }
    if (lineNameEl) lineNameEl.value = lineData.name || '';
    if (lineValueEl) lineValueEl.value = lineData.value || '';
    
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

        fetch('{{ route("petty-cash-request.line.bulkDelete") }}', {
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

        fetch('{{ route("petty-cash-request.line.bulkDelete") }}', {
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
</script>
