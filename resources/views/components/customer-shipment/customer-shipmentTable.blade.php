@props(['shipments'])

<div class="overflow-hidden rounded-b-2xl border border-t-0 border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                <tr class="bg-gray-50 text-left dark:bg-white/[0.03]">
                    <th class="min-w-[50px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                        NO
                    </th>
                    <th class="min-w-[80px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                        VIEW
                    </th>
                    <th class="min-w-[150px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                        SHIPMENT NO
                    </th>

                    <th class="min-w-[150px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                        MOVEMENT DATE
                    </th>
                    <th class="min-w-[100px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-center">
                        DOC. BACK
                    </th>
                    <th class="min-w-[100px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-center">
                        CGR
                    </th>
                    <th class="min-w-[120px] px-4 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">
                        STATUS
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shipments as $index => $req)
                    <tr class="border-t border-gray-200 dark:border-gray-800">
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $index + 1 + ($shipments->currentPage() - 1) * $shipments->perPage() }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('customer-shipment.index', ['document_id' => \Illuminate\Support\Facades\Crypt::encryptString($req->m_inout_id)]) }}" class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <button type="button"
                                    onclick="openPrintModal('{{ route('customer-shipment.print', ['id' => \Illuminate\Support\Facades\Crypt::encryptString($req->m_inout_id)]) }}', '{{ \Illuminate\Support\Facades\Crypt::encryptString($req->m_inout_id) }}')"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500"
                                    title="Print">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </button>
                                {{-- Actions --}}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-800 text-theme-sm dark:text-white/90">
                            {{ $req->documentno }}
                        </td>

                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400">
                            {{ $req->movementdate ? date('d M Y', strtotime($req->movementdate)) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400 text-center">
                            <div class="flex items-center justify-center">
                                <input type="checkbox"
                                    class="w-4 h-4 text-brand-500 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                                    {{ (isset($req->isdocumentback) && $req->isdocumentback === 'Y') ? 'checked' : '' }}
                                    onchange="toggleTracking(this, '{{ \Illuminate\Support\Facades\Crypt::encryptString($req->m_inout_id) }}', 'isdocumentback')">
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-theme-sm dark:text-gray-400 text-center">
                            <div class="flex items-center justify-center">
                                <input type="checkbox"
                                    class="w-4 h-4 text-brand-500 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
                                    {{ (isset($req->iscustomergr) && $req->iscustomergr === 'Y') ? 'checked' : '' }}
                                    onchange="toggleTracking(this, '{{ \Illuminate\Support\Facades\Crypt::encryptString($req->m_inout_id) }}', 'iscustomergr')">
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php
                                $statusLabel = $req->status_label;
                                $statusClass = match($statusLabel) {
                                    'Completed', 'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                    'In Progress', 'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                                    default => 'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-400'
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p class="text-base font-medium">No results found</p>
                                <p class="text-sm mt-1">Try adjusting your search terms.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Pagination & Info -->
    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing <span class="font-medium text-gray-900 dark:text-white">{{ $shipments->firstItem() ?? 0 }}</span> 
            to <span class="font-medium text-gray-900 dark:text-white">{{ $shipments->lastItem() ?? 0 }}</span> 
            of <span class="font-medium text-gray-900 dark:text-white">{{ $shipments->total() }}</span> results
        </div>
        <div class="w-full sm:w-auto"> 
                {{ $shipments->onEachSide(1)->links('pagination::tailwind') }}
        </div>
    </div>
</div>
