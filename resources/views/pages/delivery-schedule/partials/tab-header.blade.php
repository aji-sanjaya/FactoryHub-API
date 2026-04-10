@php
    $currentBPartnerId = $deliverySchedule->c_bpartner_id ?? null;
    $currentBPartnerLocationId = $deliverySchedule->c_bpartner_location_id ?? null;
    $currentBillBPartnerId = $deliverySchedule->bill_bpartner_id ?? $currentBPartnerId;
    $currentBillLocationId = $deliverySchedule->bill_location_id ?? $currentBPartnerLocationId;

    $dateOrdered = $isNew ? now()->format('m-d-Y') : \Carbon\Carbon::parse($deliverySchedule->dateordered)->format('m-d-Y');
    $datePromised = $isNew ? now()->format('m-d-Y') : \Carbon\Carbon::parse($deliverySchedule->datepromised)->format('m-d-Y');

    // BPartner Locations for Customer
    $bpartnerLocations = [];
    if ($currentBPartnerId) {
        $bpartnerLocations = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner_location')
            ->where('c_bpartner_id', $currentBPartnerId)
            ->where('isactive', 'Y')
            ->get(['c_bpartner_location_id as id', 'name as text'])
            ->toArray();
    }

    // Bill-To Locations
    $billLocations = [];
    if ($currentBillBPartnerId) {
        $billLocations = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner_location')
            ->where('c_bpartner_id', $currentBillBPartnerId)
            ->where('isactive', 'Y')
            ->get(['c_bpartner_location_id as id', 'name as text'])
            ->toArray();
    }

    $grandTotal = $deliverySchedule->grandtotal ?? 0;
    $totalLines = $deliverySchedule->totallines ?? 0;

    // Current BPartner name info
    $currentBPartnerName = '';
    if ($currentBPartnerId) {
        $bp = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner')->where('c_bpartner_id', $currentBPartnerId)->first();
        $currentBPartnerName = $bp ? $bp->name : '';
    }

    $currentBillBPartnerName = '';
    if ($currentBillBPartnerId && $currentBillBPartnerId != $currentBPartnerId) {
        $bpBill = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner')->where('c_bpartner_id', $currentBillBPartnerId)->first();
        $currentBillBPartnerName = $bpBill ? $bpBill->name : '';
    } else {
        $currentBillBPartnerName = $currentBPartnerName;
    }

    $orderReference = $deliverySchedule->poreference ?? '';

    $defaultPricelistId = null;
    if (isset($pricelists) && count($pricelists) > 0) {
        $sortedPl = collect($pricelists)->sortBy('id');
        $defaultPricelistId = $sortedPl->first()->id;
    }
    $currentPricelistId = $isNew ? $defaultPricelistId : ($deliverySchedule->m_pricelist_id ?? null);
    $defaultWarehouseId = null;
    if (isset($warehouses) && count($warehouses) > 0) {
        $sortedWh = collect($warehouses)->sortBy('id');
        $defaultWarehouseId = $sortedWh->first()->id;
    }
    $warehouseId = $isNew ? $defaultWarehouseId : ($deliverySchedule->m_warehouse_id ?? null);
    $defaultOrgId = null;
    if (isset($organizations) && count($organizations) > 0) {
        $sortedOrgs = collect($organizations)->sortBy('id');
        $defaultOrgId = $sortedOrgs->first()->id;
    }
    $currentOrgId = $isNew ? $defaultOrgId : ($deliverySchedule->ad_org_id ?? null);
    
    $defaultTaxId = null;
    if (isset($taxes) && count($taxes) > 0) {
        $sortedTaxes = collect($taxes)->sortBy('id');
        $defaultTaxId = $sortedTaxes->first()->id;
    }
    $currentTaxId = $isNew ? $defaultTaxId : ($deliverySchedule->c_tax_id ?? null);
    
    // Check if order has lines - tax cannot be changed if lines exist
    $hasOrderLines = false;
    if (!$isNew && $deliverySchedule->c_order_id) {
        $lineCount = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_orderline')
            ->where('c_order_id', $deliverySchedule->c_order_id)
            ->count();
        $hasOrderLines = $lineCount > 0;
    }
@endphp

<style>
    .select2-results__option { font-size: 14px !important; }
    .select2-selection__clear { margin-right: 30px !important; }
</style>

<div class="px-8 py-6 max-w-7xl mx-auto">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
        <span class="w-1 h-6 bg-brand-500 rounded mr-3"></span>
        General Information
    </h3>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">

            {{-- LEFT COLUMN --}}
            <div class="space-y-4">

                {{-- Client --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Client <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <input type="text" value="{{ $clientName }}" readonly
                            class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>

                {{-- Document No --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Document No</label>
                    <div class="col-span-2">
                        <div class="relative">
                            <input type="text" value="{{ $docNo }}" readonly
                                class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-800 font-semibold focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-200">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div> 

                {{-- Date Ordered --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Date Ordered <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_ordered" name="date_ordered" placeholder="Select Date"
                                defaultDate="{{ $dateOrdered }}" dateFormat="m-d-Y" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>

                {{-- Order Reference --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Order Reference <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <input type="text" id="order_reference" name="order_reference" value="{{ $orderReference }}"
                            {{ $isReadOnly ? 'readonly' : '' }}
                            placeholder="Enter reference..."
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                    </div>
                </div>  

                {{-- Tax --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Tax</label>
                    <div class="col-span-2">
                        <select id="c_tax_id" {{ ($isReadOnly || $hasOrderLines) ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ ($isReadOnly || $hasOrderLines) ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Tax -</option>
                            @foreach($taxes as $tax)
                                <option value="{{ $tax->id }}" {{ $currentTaxId == $tax->id ? 'selected' : '' }}>{{ $tax->text }}</option>
                            @endforeach
                        </select>
                        @if($hasOrderLines)
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                <svg class="inline-block w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Tax cannot be changed. Delete all order lines first to modify.
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                <div class="grid grid-cols-3 items-start gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Description</label>
                    <div class="col-span-2">
                        <textarea id="description" rows="4" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                            placeholder="Enter description...">{{ $desc }}</textarea>
                    </div>
                </div> 
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="space-y-4">

                {{-- Organization --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Organization <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <select id="org_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ $currentOrgId == $org->id ? 'selected' : '' }}>{{ $org->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Document Type --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Document Type <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        @php
                            $currentDocTypeId = $isNew ? 1000051 : ($deliverySchedule->c_doctypetarget_id ?? 1000051);
                        @endphp
                        <select id="c_doctypetarget_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="1000051" selected>Schedule (DN)</option>
                        </select>
                    </div> 
                </div>  

                {{-- Date Promised --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Date Promised <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_required" name="date_required" placeholder="Select Date"
                                defaultDate="{{ $datePromised }}" dateFormat="m-d-Y" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div> 

                {{-- Warehouse --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Warehouse <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <select id="warehouse_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Warehouse -</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Total Lines --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Total Lines <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <input type="text" value="{{ number_format($totalLines, 2) }}" readonly
                            class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-800 text-right font-semibold focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-200">
                    </div>
                </div> 

                {{-- Grand Total --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Grand Total <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <input type="text" value="{{ number_format($grandTotal, 2) }}" readonly
                            class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-800 text-right font-semibold focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-200">
                    </div>
                </div>

                {{-- Document Status --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Document Status <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <input type="text" value="{{ $status }}" readonly
                            class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>

            </div>
        </div>{{-- end grid --}}
    </div>{{-- end card --}} 

    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mt-6 mb-4">
        <span class="w-1 h-6 bg-brand-500 rounded mr-3"></span>
        Customer Information
    </h3>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
             {{-- LEFT COLUMN --}}
            <div class="space-y-4">
            
            {{-- Customer (BPartner) --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Customer <span class="text-red-500">*</span></label>
                    <div class="col-span-2 flex gap-2 items-center">
                        <select id="c_bpartner_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="flex-1 text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Customer -</option>
                            @foreach($bpartners as $bp)
                                <option value="{{ $bp->id }}" {{ $currentBPartnerId == $bp->id ? 'selected' : '' }}>{{ $bp->text }}</option>
                            @endforeach
                        </select>
                        <button type="button" title="Customer Info" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Customer Location --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Customer Location <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <select id="c_bpartner_location_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @foreach($bpartnerLocations as $loc)
                                <option value="{{ $loc->id }}" {{ $currentBPartnerLocationId == $loc->id ? 'selected' : '' }}>{{ $loc->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> 

                {{-- Price List --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Price List <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <select id="pricelist_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Price List -</option>
                            @foreach($pricelists as $pl)
                                <option value="{{ $pl->id }}" {{ $currentPricelistId == $pl->id ? 'selected' : '' }}>{{ $pl->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                            
            </div>  
            {{-- RIGHT COLUMN --}}
            <div class="space-y-4">
            {{-- Invoice To (Bill BPartner) --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Invoice To</label>
                    <div class="col-span-2 flex gap-2 items-center">
                        <select id="bill_bpartner_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="flex-1 text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @foreach($bpartners as $bp)
                                <option value="{{ $bp->id }}" {{ $currentBillBPartnerId == $bp->id ? 'selected' : '' }}>{{ $bp->text }}</option>
                            @endforeach
                        </select>
                        <button type="button" title="Invoice To Info" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Invoice Location --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Invoice Location</label>
                    <div class="col-span-2">
                        <select id="bill_location_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @foreach($billLocations as $loc)
                                <option value="{{ $loc->id }}" {{ $currentBillLocationId == $loc->id ? 'selected' : '' }}>{{ $loc->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                
            

            </div>  
        </div>{{-- end grid --}}
    </div>{{-- end card --}} 
 


    {{-- Action Bar Bottom --}}
    @if(!$isNew)
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
            <button type="button" onclick="openDocumentActionModal()"
                class="inline-flex items-center px-6 py-3 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 active:bg-brand-900 focus:outline-none focus:border-brand-900 focus:ring focus:ring-brand-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Document Action
            </button>
            @if(!$isReadOnly)
                <button type="button" onclick="deleteDeliverySchedule()"
                    class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring focus:ring-red-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            @endif
        </div>
    @endif

</div>