<div
    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-sm">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        View</th>
                    <th
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        Document No</th>
                    <th
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        Desc</th>
                    <th
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        Supplier</th>
                    <th
                        class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        Total Amount</th>
                    <th
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        Date</th>
                    <th
                        class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                        Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($orders as $order)
                    <tr
                        class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors {{ isset($order->is_my_approval) && $order->is_my_approval > 0 ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                        <td class="px-6 py-2 whitespace-nowrap text-xs">
                            <a href="{{ route('approval-po.show', \Illuminate\Support\Facades\Crypt::encryptString($order->c_order_id)) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20 transition-colors">
                                View
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                    </path>
                                </svg>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $order->documentno }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate"
                            title="{{ $order->description }}">{{ $order->description ?: '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $order->bpartner_name ?? $order->c_bpartner_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            {{ number_format($order->grandtotal, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ date('d M Y', strtotime($order->dateordered)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @php
                                $statusConfig = config('idempiere.approval-po.statuses');
                                $statusLabel = $statusConfig['labels'][$order->docstatus] ?? ($order->status_label ?? $order->docstatus);
                                $statusClass = $statusConfig['badge_classes'][$statusLabel] ?? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
                            @endphp
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 text-gray-300 dark:text-gray-600" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <p class="text-base font-medium">No results found</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
        {{ $orders->appends(request()->query())->links('vendor.pagination.simple-tailwind') }}
    </div>
</div>