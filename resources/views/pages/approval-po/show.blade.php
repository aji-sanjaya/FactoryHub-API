@extends('layouts.app')

@push('styles')
    <style>
        /* Minimal styles for tab active state */
        .tab-active {
            color: #1a56db;
            border-bottom-color: #1a56db;
        }

        .dark .tab-active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        .tab-inactive {
            color: #6b7280;
            border-bottom-color: transparent;
        }

        .dark .tab-inactive {
            color: #9ca3af;
        }

        .tab-inactive:hover {
            color: #374151;
            border-bottom-color: #d1d5db;
        }

        .dark .tab-inactive:hover {
            color: #d1d5db;
            border-bottom-color: #4b5563;
        }
    </style>
@endpush

@section('content')
    @php
        $approvalConfig = config('idempiere.approval-po');
        $statusConfig = $approvalConfig['statuses'];
        $workflowConfig = $approvalConfig['workflow'];
        $statusLabel = $statusConfig['labels'][$order->docstatus] ?? ($order->status_label ?? $order->docstatus);
        $statusClass = $statusConfig['badge_classes'][$statusLabel] ?? 'bg-gray-100 text-gray-800';
        $canTakeAction = in_array($order->docstatus, $statusConfig['actionable']) && $isMyApproval;
    @endphp
    <div class="main-content group-data-[sidebar-size=lg]:xl:ml-[322px]">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('approval-po.index') }}"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h2 class="text-title-md2 font-bold text-black dark:text-white">
                        PO: {{ $order->documentno }}
                    </h2>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400 ml-7">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                    <span>{{ date('d M Y', strtotime($order->dateordered)) }}</span>
                    <span>{{ $order->description }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3 ml-7 sm:ml-0">
                @if($canTakeAction)
                    <button onclick="handleApproval('REJECT')"
                        class="px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg hover:bg-red-50 hover:border-red-300 transition-colors flex items-center gap-2 shadow-sm font-medium text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                        {{ $workflowConfig['action_labels']['REJECT'] }}
                    </button>
                    <button onclick="handleApproval('APPROVE')"
                        class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors flex items-center gap-2 shadow-sm font-medium text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $workflowConfig['action_labels']['APPROVE'] }}
                    </button>
                @endif
            </div>
        </div>

        <!-- Main Card with Tabs -->
        <div
            class="bg-white rounded-2xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 overflow-hidden">

            <!-- Tabs Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 px-6 sm:px-8">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="#" onclick="switchTab('document'); return false;" id="document-tab"
                        class="tab-active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Document Preview
                    </a>
                    <a href="#" onclick="switchTab('attachments'); return false;" id="attachments-tab"
                        class="tab-inactive whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                            </path>
                        </svg>
                        Attachments
                    </a>
                </nav>
            </div>

            <!-- Tab Content -->
            <div id="tab-content">
                <!-- Document Tab (PDF) -->
                <div id="document-panel" class="block">
                    <div class="p-0 overflow-hidden bg-gray-100 dark:bg-gray-800" style="height: calc(100vh - 250px);">
                        <iframe src="{{ route('purchase-order.print', ['id' => $encryptedId]) }}"
                            class="w-full h-full border-0" title="PDF Preview"></iframe>
                    </div>
                </div>

                <!-- Attachments Tab -->
                <div id="attachments-panel" class="hidden">
                    <div class="p-6 min-h-[400px]">
                        <div id="attachments-container"
                            class="flex flex-col items-center justify-center h-64 text-gray-500">
                            <svg class="animate-spin h-8 w-8 text-brand-600 mb-4" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <p>Loading attachments...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    </div>

    <!-- Attachment Preview Modal -->
    <div id="attachmentPreviewModal"
        class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-5xl h-[90vh] flex flex-col relative">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate pr-4" id="attachmentPreviewTitle">
                    Preview</h3>
                <button onclick="closeAttachmentPreview()"
                    class="text-gray-500 hover:text-gray-700 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-hidden relative bg-gray-100 dark:bg-gray-900 flex items-center justify-center"
                id="attachmentPreviewBody"></div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let attachmentsLoaded = false;

        function switchTab(tab) {
            const docTab = document.getElementById('document-tab');
            const attTab = document.getElementById('attachments-tab');
            const docPanel = document.getElementById('document-panel');
            const attPanel = document.getElementById('attachments-panel');

            if (tab === 'document') {
                docTab.classList.add('tab-active');
                docTab.classList.remove('tab-inactive');
                attTab.classList.add('tab-inactive');
                attTab.classList.remove('tab-active');

                docPanel.classList.remove('hidden');
                attPanel.classList.add('hidden');
            } else {
                attTab.classList.add('tab-active');
                attTab.classList.remove('tab-inactive');
                docTab.classList.add('tab-inactive');
                docTab.classList.remove('tab-active');

                attPanel.classList.remove('hidden');
                docPanel.classList.add('hidden');

                if (!attachmentsLoaded) {
                    loadAttachments();
                }
            }
        }

        function loadAttachments() {
            const container = document.getElementById('attachments-container');

            axios.get('{{ route("approval-po.show", $encryptedId) }}', {
                params: { ajax_tab: 'attachments' }
            })
                .then(res => {
                    container.innerHTML = res.data;
                    attachmentsLoaded = true;
                    container.classList.remove('flex', 'flex-col', 'items-center', 'justify-center', 'h-64');
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = '<div class="text-center text-red-500 py-10">Failed to load attachments. <button onclick="loadAttachments()" class="text-brand-600 underline">Retry</button></div>';
                });
        }

        // Attachment Preview Logic
        window.openAttachmentPreview = function (url, filename) {
            const modal = document.getElementById('attachmentPreviewModal');
            const bodyContainer = document.getElementById('attachmentPreviewBody');
            const title = document.getElementById('attachmentPreviewTitle');

            title.textContent = filename || 'Preview';
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            let content = '';
            const match = filename.match(/\.([0-9a-z]+)(?:[\?#]|$)/i);
            const ext = match ? match[1].toLowerCase() : '';

            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) {
                content = `<img src="${url}" class="max-w-full max-h-full object-contain mx-auto" alt="Preview">`;
            } else if (ext === 'pdf') {
                content = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
            } else {
                content = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
            }
            bodyContainer.innerHTML = content;
        }

        window.closeAttachmentPreview = function () {
            const modal = document.getElementById('attachmentPreviewModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('attachmentPreviewBody').innerHTML = '';
        }

        function handleApproval(action) {
            const confirmationTitles = @json($workflowConfig['confirmation_titles']);
            const confirmButtonTexts = @json($workflowConfig['confirm_button_text']);
            const actionLabels = @json($workflowConfig['action_labels']);

            Swal.fire({
                title: confirmationTitles[action] || `${actionLabels[action] || action} Request?`,
                text: 'Please provide a comment (optional):',
                input: 'textarea',
                inputPlaceholder: 'Enter your comment here...',
                icon: action === 'APPROVE' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'APPROVE' ? '#16a34a' : '#dc2626',
                confirmButtonText: confirmButtonTexts[action] || `Yes, ${actionLabels[action] || action}`,
                showLoaderOnConfirm: true,
                preConfirm: (comment) => {
                    return axios.post('{{ route("approval-po.process", $encryptedId) }}', {
                        action: action,
                        comment: comment
                    })
                        .then(response => {
                            return response.data;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error.response ? error.response.data.message : error.message}`
                            )
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: result.value.message, // Message from backend
                        icon: 'success'
                    }).then(() => {
                        window.location.href = "{{ route('approval-po.index') }}";
                    });
                }
            });
        }
    </script>
@endpush