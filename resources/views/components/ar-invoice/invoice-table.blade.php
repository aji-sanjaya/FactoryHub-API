@props(['invoices'])

<div class="overflow-hidden rounded-b-2xl border border-t-0 border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                <tr class="bg-gray-50 text-left dark:bg-white/[0.03]">
                    <th class="min-w-[50px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">NO</th>
                    <th class="min-w-[80px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">VIEW</th>
                    <th class="min-w-[160px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">DOC NUMBER</th>
                    <th class="min-w-[160px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">INVOICE DATE</th>
                    <th class="min-w-[160px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">GRAND TOTAL</th>
                    <th class="min-w-[120px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $index => $invoice)
                    <tr class="border-t border-gray-200 dark:border-gray-800 hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $index + 1 + ($invoices->currentPage() - 1) * $invoices->perPage() }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                
                                <a href="{{ route('ar-invoice.index', ['document_id' => \Illuminate\Support\Facades\Crypt::encryptString($invoice->c_invoice_id)]) }}"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500" title="View">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>

                                @if($invoice->docstatus !== 'VO')
                                    <button type="button"
                                        onclick="openPrintModal('{{ route('ar-invoice.print', ['id' => \Illuminate\Support\Facades\Crypt::encryptString($invoice->c_invoice_id)]) }}')"
                                        class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500"
                                        title="Print">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                        </svg>
                                    </button>
                                @endif
                                
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-800 text-theme-sm dark:text-white/90 font-medium">
                            {{ $invoice->documentno }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $invoice->dateinvoiced ? date('d M Y', strtotime($invoice->dateinvoiced)) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 text-theme-sm dark:text-gray-300 font-mono text-right">
                            {{ number_format($invoice->grandtotal ?? 0, 2, '.', ',') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $statusLabel = $invoice->status_label ?? $invoice->docstatus;
                                $statusClass = match($statusLabel) {
                                    'Completed', 'Closed'        => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                    'In Progress', 'Approved'    => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                                    'Voided', 'Reversed',
                                    'Not Approved'               => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                                    default                      => 'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-400',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-base font-medium">No invoices found</p>
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
            Showing <span class="font-medium text-gray-900 dark:text-white">{{ $invoices->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-900 dark:text-white">{{ $invoices->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-900 dark:text-white">{{ $invoices->total() }}</span> results
        </div>
        <div class="w-full sm:w-auto">
            {{ $invoices->onEachSide(1)->links('pagination::tailwind') }}
        </div>
    </div>
</div>