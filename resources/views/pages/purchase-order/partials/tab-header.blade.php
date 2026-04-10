<style>
    .select2-results__option {
        font-size: 14px !important;
    }

    .select2-selection__clear {
        margin-right: 30px !important;
    }
</style>
@php
    // Check if there are any order lines
    $hasLines = isset($lines) && $lines->total() > 0;
    // Tax field is readonly if document is readonly OR if there are existing lines
    $taxIsReadOnly = $isReadOnly || $hasLines;
@endphp
<div class="px-8 py-6 max-w-7xl mx-auto">
    <!-- Section: General Information -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
            <span class="w-1 h-6 bg-brand-500 rounded mr-3"></span>
            General Information
        </h3>
        <div
            class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <!-- Left Column -->
            <div class="space-y-5">
                <!-- Client -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Client
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <input type="text" value="{{ $clientName ?? 'Dharmamulia Prima Karya' }}" readonly
                            class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                    </div>
                </div>

                <!-- Document No -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Document
                        No</label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative">
                            <input type="text" value="{{ $docNo }}" readonly
                                class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-800 font-semibold focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-200">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doc Type -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Target
                        Doc Type
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        {{-- Debug: docTypeId = {{ $docTypeId ?? 'NOT SET' }} --}}
                        <select id="doc_type_id" name="doc_type_id" class="w-full text-base" {{ $isReadOnly ? 'disabled' : '' }}>
                            <option value="">- Select Doc Type -</option>
                            @if(isset($docTypes))
                                @foreach($docTypes as $dt)
                                    <option value="{{ $dt->id }}" {{ (isset($docTypeId) && $docTypeId == $dt->id) ? 'selected' : '' }}>
                                        {{ $dt->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Project -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Project</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_project_id" name="c_project_id" class="w-full text-base" {{ $isReadOnly ? 'disabled' : '' }}>
                            <option value="">- Select Project -</option>
                            @if(isset($projects))
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}" {{ (isset($order) && $order->c_project_id == $proj->id) ? 'selected' : '' }}>
                                        {{ $proj->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-start gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Description</label>
                    <div class="col-span-1 sm:col-span-2">
                        <textarea id="description" rows="3" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                            placeholder="Enter purchase order description...">{{ $order ? $order->description : '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-5">
                <!-- Organization -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Organization
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="org_id" {{ $isReadOnly ? 'disabled' : '' }}
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

                <!-- Vendor -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Vendor
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_bpartner_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Vendor -</option>
                            @if(isset($vendors))
                                @foreach($vendors as $bp)
                                    <option value="{{ $bp->id }}" {{ (isset($order) && $order->c_bpartner_id == $bp->id) ? 'selected' : '' }}>
                                        {{ $bp->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Date Ordered -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Date
                        Ordered <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_ordered" name="date_ordered" placeholder="Select Date"
                                defaultDate="{{ $dateOrdered }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>

                <!-- Date Promised -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Date
                        Promised</label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_promised" name="date_promised" placeholder="Select Date"
                                defaultDate="{{ $datePromised }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>
                <!-- Priority -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Priority</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="priority_rule" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            @if(isset($priorities))
                                @foreach($priorities as $p)
                                    <option value="{{ $p->id }}" {{ (isset($order) && $order->priorityrule == $p->id) ? 'selected' : ($p->id == '5' ? 'selected' : '') }}>
                                        {{ $p->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div> 

            </div>
        </div>
    </div>

    <!-- Section: Order Details -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
            <span class="w-1 h-6 bg-blue-500 rounded mr-3"></span>
            Order Details
        </h3>
        <div
            class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <!-- Left Column -->
            <div class="space-y-5">

                <!-- Warehouse -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Warehouse
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="warehouse_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Warehouse -</option>
                            @if(isset($warehouses))
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ (isset($order) && $order->m_warehouse_id == $wh->id) || (!isset($order) && $warehouseId == $wh->id) ? 'selected' : '' }}>
                                        {{ $wh->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                @php
                    $defaultPricelistId = null;
                    if (isset($pricelists) && count($pricelists) > 0) {
                        $sortedPl = collect($pricelists)->sortBy('id');
                        $defaultPricelistId = $sortedPl->first()->id;
                    }
                    $currentPricelistId = $isNew ? $defaultPricelistId : ($order ? $order->m_pricelist_id : null);
                @endphp

                <!-- Price List -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Price
                        List <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="pricelist_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Price List -</option>
                            @if(isset($pricelists))
                                @foreach($pricelists as $pl)
                                    <option value="{{ $pl->id }}" {{ $currentPricelistId == $pl->id ? 'selected' : '' }}>
                                        {{ $pl->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <!-- Payment Term -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Payment
                        Term</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_paymentterm_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @if(isset($paymentTerms))
                                @foreach($paymentTerms as $pt)
                                    <option value="{{ $pt->id }}" {{ (isset($order) && $order->c_paymentterm_id == $pt->id) || (!isset($order) && $paymentTermId == $pt->id) ? 'selected' : '' }}>
                                        {{ $pt->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Checked By -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                        <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Checked
                            By <span class="text-red-500">*</span></label>
                        <div class="col-span-1 sm:col-span-2">
                            <select id="dpk_ad_user_checked_id" {{ $isReadOnly ? 'disabled' : '' }}
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                                <option value="">- Select -</option>
                                @if(isset($users))
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ (isset($order) && $order->dpk_ad_user_checked_id == $u->id) ? 'selected' : '' }}>
                                            {{ $u->text }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <!-- Approved By -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                        <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Approved
                            By <span class="text-red-500">*</span></label>
                        <div class="col-span-1 sm:col-span-2">
                            <select id="dpk_ad_user_approved_id" {{ $isReadOnly ? 'disabled' : '' }}
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                                <option value="">- Select -</option>
                                @if(isset($users))
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ (isset($order) && $order->dpk_ad_user_approved_id == $u->id) ? 'selected' : '' }}>
                                            {{ $u->text }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-5"> 


            <!-- Purchase Tax -->
            <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                <label
                    class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Purchase Tax
                    <span class="text-red-500">*</span></label>
                <div class="col-span-1 sm:col-span-2"> 
                    <!-- Editable select when no lines exist -->
                    <select id="c_tax_id" name="c_tax_id" {{ $isReadOnly ? 'disabled' : '' }}
                        class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                        <option value="">- Select Purchase Tax -</option>
                        @if(isset($taxesList))
                            @foreach($taxesList as $tax)
                                <option value="{{ $tax->id }}" {{ (isset($order) && $order->c_tax_id == $tax->id) ? 'selected' : '' }}>
                                    {{ $tax->text }}
                                </option>
                            @endforeach
                        @endif
                    </select> 
                </div>
            </div>
            
            <!-- Total Tax Amount -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Total Tax Amount</label>
                    <div class="col-span-1 sm:col-span-2">
                        <input type="text" id="txt_tax_amount" value="{{ isset($order) ? number_format($order->grandtotal - $order->totallines, 2) : '0.00' }}" readonly
                            class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors text-right font-medium">
                    </div>
                </div>

                <!-- Grand Total Amount -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-bold text-gray-900 dark:text-gray-100">Grand Total Amount</label>
                    <div class="col-span-1 sm:col-span-2">
                            <input type="text" id="txt_grand_total" value="{{ isset($order) ? number_format($order->grandtotal, 2) : '0.00' }}" readonly
                            class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-900 font-bold cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-white transition-colors text-right">
                    </div>
                </div> 

                

                    <!-- Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                        <label
                            class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                        <div class="col-span-1 sm:col-span-2">
                            @php
                                $statusColorParams = match($order ? $order->docstatus : 'DR') {
                                    'NA', 'VO', 'RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'CO', 'CL', 'AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'IP' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'DR' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold {{ $statusColorParams }}">
                                {{ $status }}
                            </span>
                        </div>
                    </div> 
            
        </div>
    </div>
    </div>

    <!-- Action Bar Bottom -->
    @if(!$isNew)
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            @php
                $currentStatus = isset($order) ? $order->docstatus : 'DR';
            @endphp
            
            @if(!in_array($currentStatus, ['VO']) && !($currentStatus == 'IP' && isset($hasActiveWorkflow) && $hasActiveWorkflow))
                <button type="button" onclick="openDocumentActionModal()"
                    class="inline-flex items-center px-6 py-3 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 active:bg-brand-900 focus:outline-none focus:border-brand-900 focus:ring focus:ring-brand-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Document Action
                </button>
            @endif
            @if($isDraft)
                <button type="button" onclick="deleteOrder()"
                    class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring focus:ring-red-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Delete
                </button>
            @endif
        </div>
    @endif
</div>