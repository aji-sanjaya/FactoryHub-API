@php
    $customerShipmentConfig = config('idempiere.customer-shipment');
    $currentBPartnerId = $shipment->c_bpartner_id ?? null;
    $currentBPartnerLocationId = $shipment->c_bpartner_location_id ?? null;

    $movementDate = $isNew ? now()->format('m-d-Y') : \Carbon\Carbon::parse($shipment->movementdate)->format('m-d-Y');

    // BPartner Locations for Customer
    $bpartnerLocations = [];
    if ($currentBPartnerId) {
        $bpartnerLocations = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner_location as bpl')
            ->join('c_location as l', 'bpl.c_location_id', '=', 'l.c_location_id')
            ->where('bpl.c_bpartner_id', $currentBPartnerId)
            ->where('bpl.isactive', 'Y')
            ->get(['bpl.c_bpartner_location_id as id', 'l.address1 as text'])
            ->toArray();
    }

    // Current BPartner name info
    $currentBPartnerName = '';
    if ($currentBPartnerId) {
        $bp = \Illuminate\Support\Facades\DB::connection('idempiere')
            ->table('c_bpartner')->where('c_bpartner_id', $currentBPartnerId)->first();
        $currentBPartnerName = $bp ? $bp->name : '';
    }

    $shipmentReference = $shipment->poreference ?? '';

    // Delivery Via / Freight / Shipper
    $deliveryViaRule = $shipment->deliveryviarule ?? $customerShipmentConfig['defaults']['delivery_via_rule'];
    $freightCostRule = $shipment->freightcostrule ?? $customerShipmentConfig['defaults']['freight_cost_rule'];
    $currentShipperId = $shipment->m_shipper_id ?? null;
    $shipperDeliveryViaRule = $customerShipmentConfig['rules']['shipper_delivery_via'];

    $deliveryViaOptions = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
        SELECT rl.value AS id, rl.name AS text
        FROM ad_ref_list rl
        WHERE rl.ad_reference_id = ? AND rl.isactive = 'Y'
        ORDER BY rl.name
    ", [$customerShipmentConfig['references']['delivery_via_rule']]);

    $freightCostOptions = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
        SELECT rl.value AS id, rl.name AS text
        FROM ad_ref_list rl
        WHERE rl.ad_reference_id = ? AND rl.isactive = 'Y'
        ORDER BY rl.name
    ", [$customerShipmentConfig['references']['freight_cost_rule']]);

    $clientIdForShipper = \Illuminate\Support\Facades\Session::get('idempiere_client');
    $shippers = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
        SELECT m_shipper_id AS id, name AS text
        FROM m_shipper
        WHERE isactive = 'Y' AND ad_client_id = ?
        ORDER BY name
    ", [$clientIdForShipper]);

    $defaultWarehouseId = null;
    if (isset($warehouses) && count($warehouses) > 0) {
        $sortedWh = collect($warehouses)->sortBy('id');
        $defaultWarehouseId = $sortedWh->first()->id;
    }
    $warehouseId = $isNew ? $defaultWarehouseId : ($shipment->m_warehouse_id ?? null);
    $defaultOrgId = null;
    if (isset($organizations) && count($organizations) > 0) {
        $sortedOrgs = collect($organizations)->sortBy('id');
        $defaultOrgId = $sortedOrgs->first()->id;
    }
    $currentOrgId = $isNew ? $defaultOrgId : ($shipment->ad_org_id ?? null);

    // Checked By and Approved By users
    $checkedById = $shipment->salesrep_id ?? null;
    $approvedById = $shipment->ad_user_id ?? null;

    // Driver Name (maps to SalesRep_ID on M_InOut)
    $currentDriverId = $shipment->salesrep_id ?? null;
    $driverRoleId = config('idempiere.roles.driver');
    $drivers = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
        SELECT DISTINCT u.ad_user_id AS id, u.name AS text
        FROM ad_user u
        JOIN ad_user_roles ur ON u.ad_user_id = ur.ad_user_id
        WHERE ur.ad_role_id = ? AND u.isactive = 'Y'
        ORDER BY u.name
    ", [$driverRoleId]);

    // A_Asset
    $currentAssetId = $shipment->a_asset_id ?? null;
    $clientIdForAsset = \Illuminate\Support\Facades\Session::get('idempiere_client');
    $assets = \Illuminate\Support\Facades\DB::connection('idempiere')->select("
        SELECT a_asset_id AS id, value AS text
        FROM a_asset
        WHERE isactive = 'Y' AND ad_client_id = ?
        ORDER BY value
    ", [$clientIdForAsset]);
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

                {{-- Movement Date --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Movement Date <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="movement_date" name="movement_date" placeholder="Select Date"
                                defaultDate="{{ $movementDate }}" dateFormat="m-d-Y" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>

                {{-- Shipment Reference --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Shipment Reference <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <input type="text" id="shipment_reference" name="shipment_reference" value="{{ $shipmentReference }}"
                            {{ $isReadOnly ? 'readonly' : '' }}
                            placeholder="Enter reference..."
                            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
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
                            $currentDocTypeId = $isNew
                                ? $customerShipmentConfig['doc_types']['shipment']
                                : ($shipment->c_doctype_id ?? $customerShipmentConfig['doc_types']['shipment']);
                        @endphp
                        <select id="c_doctype_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="{{ $customerShipmentConfig['doc_types']['shipment'] }}" {{ $currentDocTypeId == $customerShipmentConfig['doc_types']['shipment'] ? 'selected' : '' }}>Shipment</option> 
                        </select>
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

    {{-- ─── Shipment Information ─── --}}
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4 mt-8">
        <span class="w-1 h-6 bg-brand-500 rounded mr-3"></span>
        Shipment Information
    </h3>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">

            {{-- LEFT COLUMN --}}
            <div class="space-y-4">

                {{-- Delivery Via --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Delivery Via <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <select id="deliveryviarule" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @foreach($deliveryViaOptions as $dv)
                                <option value="{{ $dv->id }}" {{ $deliveryViaRule == $dv->id ? 'selected' : '' }}>{{ $dv->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- A Asset --}}
                <div id="asset_row" class="grid grid-cols-3 items-center gap-3" style="{{ $deliveryViaRule === $shipperDeliveryViaRule ? 'display:none' : '' }}">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Unit</label>
                    <div class="col-span-2">
                        <select id="a_asset_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Asset -</option>
                            @foreach($assets as $asset)
                                <option value="{{ $asset->id }}" {{ $currentAssetId == $asset->id ? 'selected' : '' }}>{{ $asset->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Shipper (conditional, shown when Delivery Via = S) --}}
                <div id="shipper_row" class="grid grid-cols-3 items-center gap-3" style="{{ $deliveryViaRule !== $shipperDeliveryViaRule ? 'display:none' : '' }}">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Shipper <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <select id="m_shipper_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Shipper -</option>
                            @foreach($shippers as $sh)
                                <option value="{{ $sh->id }}" {{ $currentShipperId == $sh->id ? 'selected' : '' }}>{{ $sh->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>


            </div>

            {{-- RIGHT COLUMN --}}
            <div class="space-y-4">

                {{-- Freight Cost Rule --}}
                <div class="grid grid-cols-3 items-center gap-3">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Freight Cost Rule <span class="text-red-500">*</span></label>
                    <div class="col-span-2">
                        <select id="freightcostrule" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @foreach($freightCostOptions as $fc)
                                <option value="{{ $fc->id }}" {{ $freightCostRule == $fc->id ? 'selected' : '' }}>{{ $fc->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Driver Name (SalesRep_ID) --}}
                <div id="driver_row" class="grid grid-cols-3 items-center gap-3" style="{{ $deliveryViaRule === $shipperDeliveryViaRule ? 'display:none' : '' }}">
                    <label class="text-right text-sm font-medium text-gray-600 dark:text-gray-400">Driver Name</label>
                    <div class="col-span-2">
                        <select id="salesrep_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Driver -</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ $currentDriverId == $driver->id ? 'selected' : '' }}>{{ $driver->text }}</option>
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
                <button type="button" onclick="deleteCustomerShipment()"
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
