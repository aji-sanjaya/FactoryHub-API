<style>
    .select2-results__option {
        font-size: 14px !important;
    }

    .select2-selection__clear {
        margin-right: 30px !important;
    }
</style>
<div class="px-8 py-6 max-w-7xl mx-auto">
    @php
        $materialReceiptConfig = $materialReceiptConfig ?? config('idempiere.create-gr');
        $isNew      = is_null($receipt);
        $isReadOnly = !$isNew && in_array($receipt->docstatus, $materialReceiptConfig['statuses']['read_only'] ?? [], true);
        $isDraft    = !$isNew && in_array($receipt->docstatus, $materialReceiptConfig['statuses']['draft'] ?? ['DR'], true);
        $cs         = $receipt->docstatus ?? 'DR';

        // Resolve display names from collections
        $resolveText = fn($collection, $id) =>
            collect($collection)->firstWhere('id', $id)->text ?? '-';

        $displayDocType  = !$isNew ? $resolveText($docTypes,      $receipt->c_doctype_id)             : '';
        $displayOrg      = !$isNew ? $resolveText($organizations,  $receipt->ad_org_id)                : '';
        $displayVendor   = !$isNew ? $resolveText($vendors,        $receipt->c_bpartner_id)            : '';
        $displayWarehouse= !$isNew ? $resolveText($warehouses,     $receipt->m_warehouse_id)           : '';
        $displayProject  = !$isNew && $receipt->c_project_id ? $resolveText($projects, $receipt->c_project_id) : '-';

        $defaultDocTypeId   = $materialReceiptConfig['doc_types']['material_receipt'];
        $defaultWarehouseId = collect($warehouses)->sortBy(fn($w) => (int)$w->id)->first()?->id;

        // Vendor is locked when receipt already has lines
        $hasLines = !$isNew && isset($lines) && $lines->total() > 0;
    @endphp

    <!-- Section: General Information -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
            <span class="w-1 h-6 bg-brand-500 rounded mr-3"></span>
            General Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
            <!-- Left Column -->
            <div class="space-y-5">
                <!-- Client -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Client
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <input type="text" value="{{ session('user_data.client_name') ?? 'Dharmamulia Prima Karya' }}" readonly
                            class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                    </div>
                </div>

                <!-- Document No -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Document
                        No</label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative">
                            <input type="text" value="{{ $isNew ? '** New **' : $receipt->documentno }}" readonly
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
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Document
                        Type
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <input type="text" value="{{ $displayDocType }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                        @else
                            <select id="doc_type_id" name="doc_type_id" class="w-full text-base" {{ $isReadOnly ? 'disabled' : '' }}>
                                <option value="{{ $materialReceiptConfig['doc_types']['material_receipt'] }}" selected>Material Receipt</option>
                            </select>
                        @endif
                    </div>
                </div>

                <!-- Project -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Project</label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <input type="text" value="{{ $displayProject }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                        @else
                            <select id="c_project_id" name="c_project_id" class="w-full text-base" {{ $isReadOnly ? 'disabled' : '' }}>
                                <option value="">- Select Project -</option>
                                @if(isset($projects))
                                    @foreach($projects as $proj)
                                        <option value="{{ $proj->id }}" {{ (!$isNew && $receipt->c_project_id == $proj->id) ? 'selected' : '' }}>
                                            {{ $proj->text }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        @endif
                    </div>
                </div>

                <!-- Description -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-start gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Description</label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <div class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 min-h-[72px]">
                                {{ $receipt->description ?? '-' }}
                            </div>
                        @else
                            <textarea id="description" rows="3" {{ $isReadOnly ? 'disabled' : '' }}
                                class="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                                placeholder="Enter receipt description...">{{ $receipt ? $receipt->description : '' }}</textarea>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-5">
                <!-- Organization -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Organization
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <input type="text" value="{{ $displayOrg }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                        @else
                            <select id="org_id" {{ $isReadOnly ? 'disabled' : '' }}
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}"
                                onchange="updateWarehouses(this.value)">
                                <option value="">- Select Organization -</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ $currentOrgId == $org->id ? 'selected' : '' }}>
                                        {{ $org->text }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>

                <!-- Vendor -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Vendor
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <input type="text" value="{{ $displayVendor }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                        @elseif($hasLines)
                            {{-- Locked: receipt already has lines, vendor cannot be changed --}}
                            <div class="w-full px-3.5 py-2.5 text-sm bg-gray-100 border border-gray-200 rounded-lg text-gray-700 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 flex items-center justify-between">
                                <span>{{ $displayVendor }}</span>
                                <span class="ml-2 inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-100 border border-amber-200 rounded-full px-2 py-0.5 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700 whitespace-nowrap">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    Locked
                                </span>
                            </div>
                            <input type="hidden" name="c_bpartner_id" value="{{ $receipt->c_bpartner_id }}">
                        @else
                            <select id="c_bpartner_id" {{ $isReadOnly ? 'disabled' : '' }}
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                                <option value="">- Select Vendor -</option>
                                @if(isset($vendors))
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ (!$isNew && $receipt->c_bpartner_id == $vendor->id) ? 'selected' : '' }}>
                                            {{ $vendor->text }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        @endif
                    </div>
                </div>

                <!-- Movement Date -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Movement
                        Date <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <input type="text" value="{{ $movementDate }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                        @else
                            <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                                <x-form.date-picker id="movement_date" name="movement_date" placeholder="Select Date"
                                    defaultDate="{{ $movementDate }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Accounting Date -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Accounting
                        Date <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <input type="text" value="{{ $dateAcct }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                        @else
                            <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                                <x-form.date-picker id="date_acct" name="date_acct" placeholder="Select Date"
                                    defaultDate="{{ $dateAcct }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Warehouse -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Warehouse
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        @if($isReadOnly)
                            <input type="text" value="{{ $displayWarehouse }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300 transition-colors">
                        @else
                            <select id="warehouse_id" {{ $isReadOnly ? 'disabled' : '' }}
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                                <option value="">- Select Warehouse -</option>
                                @if(isset($warehouses))
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ ((!$isNew && $receipt->m_warehouse_id == $wh->id) || ($isNew && (int)$wh->id === (int)$defaultWarehouseId)) ? 'selected' : '' }}>
                                            {{ $wh->text }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar Bottom -->
    @if(!$isNew)
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            @if(!in_array($cs, ['VO','RE','CL']))
                <button type="button" onclick="openDocumentActionModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm hover:shadow focus:ring-4 focus:ring-brand-500/30 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Document Action
                </button>
            @endif
            @if($isDraft)
                <button type="button" onclick="deleteOrder()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 hover:bg-red-100 rounded-lg transition-all dark:bg-red-900/20 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/40">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Receipt
                </button>
            @endif
        </div>
    @endif

    <script>
    function updateWarehouses(orgId) {
        if (!orgId) return;
        axios.get('{{ route("material-receipt.api.warehouses") }}', { params: { org_id: orgId } })
            .then(res => {
                const data = Array.isArray(res.data) ? res.data : (res.data.data || []);
                const sel  = document.getElementById('warehouse_id');
                if (!sel) return;
                sel.innerHTML = '<option value="">- Select warehouse -</option>';
                data.forEach(wh => {
                    const opt   = document.createElement('option');
                    opt.value   = wh.id;
                    opt.text    = wh.text || wh.name;
                    sel.appendChild(opt);
                });
                if (typeof $ !== 'undefined') $('#warehouse_id').trigger('change');
            });
    }
    </script>
</div>
