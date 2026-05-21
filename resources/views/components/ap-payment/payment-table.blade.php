@props(['payments'])

<div class="overflow-hidden rounded-b-2xl border border-t-0 border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                <tr class="bg-gray-50 text-left dark:bg-white/[0.03]">
                    <th class="min-w-[50px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">NO</th>
                    <th class="min-w-[80px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">VIEW</th>
                    <th class="min-w-[160px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">DOC NUMBER</th>
                    <th class="min-w-[160px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">VENDOR</th>
                    <th class="min-w-[160px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">PAYMENT DATE</th>
                    <th class="min-w-[130px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">PAYMENT RULE</th>
                    <th class="min-w-[160px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">AMOUNT</th>
                    <th class="min-w-[120px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $index => $payment)
                    @php
                        $apPaymentConfig = config('idempiere.ap-payment');
                        $paymentRuleOptions = collect($apPaymentConfig['payment_rules']);
                        $statusLabels = $apPaymentConfig['statuses']['labels'];
                        $statusBadgeClasses = $apPaymentConfig['statuses']['badge_classes'];
                        $paymentRuleCode = $payment->tendertype ?? $payment->paymentrule ?? '';
                        $paymentRuleOption = $paymentRuleOptions->firstWhere('id', $paymentRuleCode);
                        $vendorName = \Illuminate\Support\Facades\DB::connection('idempiere')
                            ->table('c_bpartner')
                            ->where('c_bpartner_id', $payment->c_bpartner_id)
                            ->value('name');

                        $paymentRuleLabel = $paymentRuleOption['text'] ?? ($paymentRuleCode ?: '-');

                        $statusLabel = $statusLabels[$payment->docstatus] ?? $payment->docstatus;
                        $statusClass = $statusBadgeClasses[$statusLabel] ?? 'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-400';
                    @endphp
                    <tr class="border-t border-gray-200 dark:border-gray-800 hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $index + 1 + ($payments->currentPage() - 1) * $payments->perPage() }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('ap-payment.index', ['document_id' => \Illuminate\Support\Facades\Crypt::encryptString($payment->c_payment_id)]) }}"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500" title="View">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-800 text-theme-sm dark:text-white/90 font-medium font-mono">
                            {{ $payment->documentno }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $vendorName ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $payment->datetrx ? date('d M Y', strtotime($payment->datetrx)) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $paymentRuleLabel }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 text-theme-sm dark:text-gray-300 font-mono text-right">
                            {{ number_format($payment->payamt ?? 0, 2, '.', ',') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-base font-medium">No payments found</p>
                                <p class="text-sm text-gray-400 mt-1">Try adjusting your search or filters</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing <span class="font-medium text-gray-900 dark:text-white">{{ $payments->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-900 dark:text-white">{{ $payments->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-900 dark:text-white">{{ $payments->total() }}</span> results
        </div>
        <div class="w-full sm:w-auto">
            {{ $payments->onEachSide(1)->links('pagination::tailwind') }}
        </div>
    </div>
</div>
