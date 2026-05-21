<style>
    .select2-results__option {
        font-size: 14px !important;
    }

    .select2-selection__clear {
        margin-right: 30px !important;
    }
</style>
<div class="px-2 py-6 max-w-7xl mb-8 mx-auto"> 
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
            <span class="w-1 h-6 bg-brand-500 rounded mr-3"></span>
            General Information
        </h3>
        <div
            class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <!-- Left Column -->
            <div class="space-y-5">
                <!-- Organization -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Organization
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="org_id" name="org_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Organization -</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ $currentOrgId == $org->id ? 'selected' : '' }}>
                                    {{ $org->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div> 

                <!-- Currency -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Currency
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_currency_id" name="c_currency_id" {{ $isReadOnly ? 'disabled' : '' }}
                            onchange="updateCurrencySymbol()"
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Currency -</option>
                            @if(isset($currencies))
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" 
                                        data-symbol="{{ $currency->cursymbol ?? $currency->iso_code }}"
                                        {{ (isset($request) && $request->c_currency_id == $currency->id) || (!isset($request) && isset($defaultCurrencyId) && $defaultCurrencyId == $currency->id) ? 'selected' : '' }}>
                                        {{ $currency->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Document Type -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Document Type
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_doctype_id" name="c_doctype_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="1000049">Petty Cash Request</option> 
                        </select>
                    </div>
                </div>

                <!-- Transaction Date -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Transaction Date
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_trx" name="date_trx" placeholder="Select Date"
                                defaultDate="{{ $dateTrx }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>

                <!-- Accounting Date -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Accounting Date
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_acct" name="date_acct" placeholder="Select Date"
                                defaultDate="{{ $dateAcct }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div> 
            </div>

            <!-- Right Column -->
            <div class="space-y-5">

                <!-- Business Partner -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Business Partner</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_bpartner_id" name="c_bpartner_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Business Partner -</option>
                            @if(isset($businessPartners))
                                @foreach($businessPartners as $bp)
                                    <option value="{{ $bp->id }}" {{ (isset($request) && $request->c_bpartner_id == $bp->id) ? 'selected' : '' }}>
                                        {{ $bp->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- User/Requestor -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Requestor
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="ad_user_id" name="ad_user_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Requestor -</option>
                            @if(isset($users))
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (isset($request) && $request->ad_user_id == $user->id) ? 'selected' : '' }}>
                                        {{ $user->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div> 

                <!-- Cost Center -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Cost Center</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_costcenter_id" name="c_costcenter_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Cost Center -</option>
                            @if(isset($costCenters))
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}" {{ (isset($request) && $request->c_costcenter_id == $cc->id) ? 'selected' : '' }}>
                                        {{ $cc->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Name -->
                {{-- <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Name</label>
                    <div class="col-span-1 sm:col-span-2">
                        <input type="text" id="name" {{ $isReadOnly ? 'disabled' : '' }}
                            value="{{ $request ? $request->name : '' }}"
                            class="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                            placeholder="Enter name...">
                    </div>
                </div> --}}

                <!-- Value -->
                {{-- <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Key</label>
                    <div class="col-span-1 sm:col-span-2">
                        <input type="text" id="value" {{ $isReadOnly ? 'disabled' : '' }}
                            value="{{ $request ? $request->value : '' }}"
                            class="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                            placeholder="Enter value...">
                    </div>
                </div> --}}

                <!-- Description -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-start gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Description</label>
                    <div class="col-span-1 sm:col-span-2">
                        <textarea id="description" rows="3" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                            placeholder="Enter description...">{{ $request ? $request->description : '' }}</textarea>
                    </div>
                </div>
            </div>
        </div> 
</div>

@if(!$isNew)
    <div class="flex justify-end gap-3 p-4 border-t border-gray-200 dark:border-gray-700">
        @if(!$isReadOnly)
            <button type="button" onclick="openDocumentActionModal()"
                class="inline-flex items-center px-6 py-3 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 focus:outline-none focus:ring focus:ring-brand-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Document Action
            </button>
            <button type="button" onclick="deleteRequest()"
                class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring focus:ring-red-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete
            </button>
        @else
            <button type="button" onclick="openDocumentActionModal()"
                class="inline-flex items-center px-6 py-3 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 focus:outline-none focus:ring focus:ring-brand-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Document Action
            </button>
        @endif
    </div>
@endif

<script>
// Force initialize select2 on this tab
(function() {
    // Wait for jQuery and Select2 to be available
    var initAttempts = 0;
    var maxAttempts = 50; // 5 seconds max
    
    function tryInitSelect2() {
        initAttempts++;
        
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            console.log('Select2 initialization on tab-header - jQuery and Select2 available');
            
            // Initialize all select elements
            $('#org_id, #c_bpartner_id, #ad_user_id, #c_currency_id, #c_doctype_id, #c_costcenter_id').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    console.log('Initializing select2 for: ' + this.id);
                    $(this).select2({
                        width: '100%',
                        placeholder: $(this).find('option:first').text(),
                        allowClear: false
                    });
                }
            });
            
            // Initialize currency symbol on page load
            updateCurrencySymbol();
        } else if (initAttempts < maxAttempts) {
            console.log('Waiting for jQuery/Select2... attempt ' + initAttempts);
            setTimeout(tryInitSelect2, 100);
        } else {
            console.error('jQuery or Select2 not loaded after 5 seconds');
        }
    }
    
    // Start trying to initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInitSelect2);
    } else {
        tryInitSelect2();
    }
})();

function updateCurrencySymbol() {
    var select = document.getElementById('c_currency_id');
    if (!select) return;
    
    var selectedOption = select.options[select.selectedIndex];
    var symbol = selectedOption.getAttribute('data-symbol') || 'Rp';
    
    // Update currency symbol in line form if it exists
    var currencySymbolEl = document.getElementById('currency_symbol');
    if (currencySymbolEl) {
        currencySymbolEl.textContent = symbol;
    }
}
</script>
