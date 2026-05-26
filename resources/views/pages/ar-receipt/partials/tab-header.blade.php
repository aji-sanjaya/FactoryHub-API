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
        $arReceiptConfig = config('idempiere.ar-receipt');
        $readOnlyStatuses = $arReceiptConfig['statuses']['read_only'] ?? ['CO', 'CL', 'VO', 'RE'];
        $isNew = is_null($payment);
        $isReadOnly = !$isNew && in_array($payment->docstatus, $readOnlyStatuses);
        $docNo = $isNew ? '** New **' : $payment->documentno;
    @endphp

    {{-- ── Section 1: General Information ─────────────────────────────────── --}}
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
            <span class="w-1 h-6 bg-brand-500 rounded mr-3"></span>
            General Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">

            {{-- Left Column --}}
            <div class="space-y-5">
                {{-- Client --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Client <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <input type="text" value="{{ $clientName }}" readonly
                            class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-300">
                    </div>
                </div>

                {{-- Document No --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Document No
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative">
                            <input type="text" value="{{ $docNo }}" readonly
                                class="w-full pl-10 pr-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-800 font-semibold focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-200">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Customer --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Customer <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_bpartner_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Customer -</option>
                            @foreach($customers as $v)
                                <option value="{{ $v->id }}" {{ (!$isNew && $payment->c_bpartner_id == $v->id) ? 'selected' : '' }}>
                                    {{ $v->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Description --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-start gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400 mt-2">
                        Description
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <textarea id="description" rows="3" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-shadow dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 placeholder-gray-400 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}"
                            placeholder="Enter payment description...">{{ $payment->description ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-5">
                {{-- Organization --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Organization <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="org_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Organization -</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ ($currentOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                    {{ $org->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Document Type --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Document Type <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="doc_type_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Doc Type -</option>
                            @foreach($docTypes as $dt)
                                <option value="{{ $dt->id }}" {{ ($docTypeId ?? '') == $dt->id ? 'selected' : '' }}>
                                    {{ $dt->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Payment Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Payment Date <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="payment_date" name="payment_date" placeholder="Select Date"
                                defaultDate="{{ $paymentDate }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>

                {{-- Accounting Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4 mt-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Acct. Date
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative {{ $isReadOnly ? 'pointer-events-none opacity-70' : '' }}">
                            <x-form.date-picker id="date_acct" name="date_acct" placeholder="Select Date"
                                defaultDate="{{ $dateAcct }}" dateFormat="Y-m-d" disabled="{{ $isReadOnly }}" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Section 2: Payment Details ─────────────────────────────────────── --}}
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center mb-4">
            <span class="w-1 h-6 bg-blue-500 rounded mr-3"></span>
            Payment Details
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">

            {{-- Left Column --}}
            <div class="space-y-5">
                {{-- Currency --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Currency <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_currency_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Currency -</option>
                            @foreach($currencies as $cur)
                                <option value="{{ $cur->id }}" {{ ($currencyId ?? '') == $cur->id ? 'selected' : '' }}>
                                    {{ $cur->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Payment Rule --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Payment Rule <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="payment_rule" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Payment Rule -</option>
                            @foreach($paymentRules as $pr)
                                <option value="{{ $pr['id'] }}" {{ ($paymentRule ?? '') == $pr['id'] ? 'selected' : '' }}>
                                    {{ $pr['text'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Bank Account --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Bank Account
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <select id="c_bankaccount_id" {{ $isReadOnly ? 'disabled' : '' }}
                            class="w-full text-sm rounded-lg border-gray-300 focus:border-brand-500 focus:ring-brand-500 dark:bg-gray-900 dark:border-gray-600 {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed' : '' }}">
                            <option value="">- Select Bank Account -</option>
                            @foreach($bankAccounts as $ba)
                                <option value="{{ $ba->id }}" {{ ($bankAccountId ?? '') == $ba->id ? 'selected' : '' }}>
                                    {{ $ba->text }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div> 
            </div>

            {{-- Right Column --}}
            <div class="space-y-5">
                {{-- Status --}}
                @if(!$isNew)
                    <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                        <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Status</label>
                        <div class="col-span-1 sm:col-span-2">
                            @php
                                $badgeClass = match ($payment->docstatus) {
                                    'CO', 'AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'VO', 'RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'IP'       => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'CL'       => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    default    => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold {{ $badgeClass }}">
                                {{ $status }}
                            </span>
                        </div>
                    </div>
                @endif 

                {{-- Payment Amount --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                    <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">
                        Pay Amount <span class="text-red-500">*</span>
                    </label>
                    <div class="col-span-1 sm:col-span-2">
                        <div class="relative">
                            <input type="text" id="pay_amt" {{ $isReadOnly ? 'disabled' : '' }}
                                value="{{ number_format($payAmt ?? 0, 2, '.', ',') }}"
                                oninput="formatNumber(this)"
                                placeholder="0.00"
                                class="w-full pl-10 pr-3.5 py-2.5 text-sm font-mono bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 dark:bg-gray-900 dark:border-gray-600 dark:text-gray-200 text-right {{ $isReadOnly ? 'bg-gray-50 cursor-not-allowed text-gray-500' : '' }}">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-400 text-sm font-medium">
                                    @foreach($currencies as $cur)
                                        @if(($currencyId ?? '') == $cur->id) {{ $cur->text }} @endif
                                    @endforeach
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pay Amount (Read-only summary for existing) --}}
                @if(!$isNew)
                    <div class="grid grid-cols-1 sm:grid-cols-3 sm:items-center gap-2 sm:gap-4">
                        <label class="text-left sm:text-right text-sm font-medium text-gray-600 dark:text-gray-400">Payment Amount</label>
                        <div class="col-span-1 sm:col-span-2">
                            <input type="text" value="{{ number_format($payAmt ?? 0, 2, '.', ',') }}" readonly
                                class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-800 font-semibold font-mono cursor-not-allowed focus:ring-0 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-200 text-right">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Bottom Action Bar ───────────────────────────────────────────────── --}}
    @if(!$isNew)
        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            @if(!$isReadOnly)
                <button type="button" onclick="openDocumentActionModal()"
                    class="inline-flex items-center px-6 py-3 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 focus:outline-none focus:ring focus:ring-brand-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Document Action
                </button>
                <button type="button" onclick="deletePayment()"
                    class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring focus:ring-red-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            @else
                <button type="button" onclick="openDocumentActionModal()"
                    class="inline-flex items-center px-6 py-3 bg-brand-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-brand-700 focus:outline-none focus:ring focus:ring-brand-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Document Action
                </button>
            @endif
        </div>
    @endif
</div>
