<style>
    .select2-results__option {
        font-size: 14px !important;
    }

    .select2-selection__clear {
        margin-right: 30px !important;
    }
</style>
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
                        <input type="text" value="{{ $clientName }}" readonly
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

                <!-- Description -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-start gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">Description</label>
                    <div class="col-span-1 sm:col-span-2">
                        <textarea id="description" rows="3" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                            placeholder="Enter document description...">{{ $desc }}</textarea>
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

                <!-- Priority -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Priority
                        <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="priority_rule" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            @foreach($priorities as $p)
                                <option value="{{ $p->id }}" {{ (isset($requisition) && $requisition->priorityrule == $p->id) ? 'selected' : ($p->id == '5' ? 'selected' : '') }}>{{ $p->text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Date Required -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Date
                        Required <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_required" name="date_required" placeholder="Select Date"
                                defaultDate="{{ $dateRequired }}" dateFormat="m-d-Y" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>

                <!-- Date Doc -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Date
                        Doc <span class="text-red-500">*</span></label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="datedoc" name="datedoc" placeholder="Select Date"
                                defaultDate="{{ $dateDocValue ?? now()->format('m-d-Y') }}" dateFormat="m-d-Y" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Request Details -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
            <span class="w-1 h-6 bg-blue-500 rounded mr-3"></span>
            Request Details
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
                                    <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
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
                    $currentPricelistId = $isNew ? $defaultPricelistId : $requisition->m_pricelist_id;
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

                <!-- Department -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Department</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_department_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Department -</option>
                            @if(isset($departments))
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ (isset($requisition) && $requisition->c_department_id == $dept->id) ? 'selected' : '' }}>
                                        {{ $dept->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Cost Center -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Cost
                        Center</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="cost_center_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Cost Center -</option>
                            @if(isset($costCenters))
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}" {{ (isset($requisition) && $requisition->c_costcenter_id == $cc->id) ? 'selected' : '' }}>
                                        {{ $cc->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
            </div>

            <!-- Right Column: Status & Approval -->
            <div class="space-y-5">
                <!-- Status -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label
                        class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                    <div class="col-span-1 sm:col-span-2">
                        <span
                            class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $status }}
                        </span>
                    </div>
                </div>

                <!-- Checked By -->
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Checked
                        By</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="adw_ad_user_checked_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @if(isset($users))
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ (isset($requisition) && $requisition->adw_ad_user_checked_id == $u->id) ? 'selected' : '' }}>
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
                        By</label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="adw_ad_user_approved_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select -</option>
                            @if(isset($users))
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ (isset($requisition) && $requisition->adw_ad_user_approved_id == $u->id) ? 'selected' : '' }}>
                                        {{ $u->text }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar Bottom -->
    @if(!$isNew)
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
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
            @if(!$isReadOnly)
                <button type="button" onclick="deleteRequisition()"
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