<div class="mt-5 space-y-6">
    <!-- Upload Area -->
    @if(!$isReadOnly)
    <div class="p-6 border-b ">
        <div 
            ondragover="event.preventDefault(); this.classList.add('border-brand-500', 'bg-brand-50');"
            ondragleave="this.classList.remove('border-brand-500', 'bg-brand-50');"
            ondrop="event.preventDefault(); this.classList.remove('border-brand-500', 'bg-brand-50'); handleFileUpload(event.dataTransfer.files);"
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 flex flex-col items-center justify-center text-center hover:bg-gray-50 transition-colors cursor-pointer dark:border-gray-700 dark:hover:bg-white/5"
            onclick="document.getElementById('fileInput').click()">
            
            <input type="file" id="fileInput" class="hidden" multiple accept=".jpg,.jpeg,.png,.pdf" onchange="handleFileUpload(this.files)">
            
            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Click to upload or drag and drop</p>
            <p class="text-xs text-gray-500 mt-1">SVG, PNG, JPG or PDF (MAX. 10MB)</p>
        </div>
    </div>
    @endif

    <!-- Toolkit (Select All & Delete) -->
    @if(!$isReadOnly && count($attachments) > 0)
    <div class="flex items-center justify-between px-4">
        <div class="flex items-center space-x-2 select-none cursor-pointer" onclick="document.getElementById('selectAllAttachments').click()">
             <input type="checkbox" id="selectAllAttachments" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer" onclick="event.stopPropagation(); toggleSelectAll(this)">
             <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Select All</span>
        </div>
        <button id="btnDeleteSelected" onclick="deleteSelectedAttachments()" class="hidden px-3 py-1.5 bg-red-600 text-white rounded-md text-sm hover:bg-red-700 transition-colors shadow-sm flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            Delete Selected
        </button>
    </div>
    @endif

    <!-- Attachments Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 p-4 pt-2" id="attachments-grid">
        @forelse($attachments as $att)
            @php 
                $attName = $att->name ?? $att->Name ?? $att->filename ?? $att->fileName ?? 'Unknown';
                $attId = $att->id ?? $att->ID ?? $att->ad_attachment_id ?? null;
                $deleteId = $attId ?? $attName; // Use Name if ID missing
                $ext = pathinfo($attName, PATHINFO_EXTENSION); 
                $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                
                // Construct Preview URL using FILENAME
                $previewUrl = ( isset($docIdParam) && $attName !== 'Unknown' ) 
                    ? route('customer-shipment.attachment.view', ['document_id' => $docIdParam, 'file_name' => $attName]) 
                    : '';
            @endphp

            <div class="{{ $previewUrl ? 'cursor-pointer hover:shadow-md' : '' }} group relative border border-gray-200 rounded-lg p-3 transition-shadow dark:border-gray-700 dark:bg-gray-800"
                 data-url="{{ $previewUrl }}"
                 data-name="{{ $attName }}"
                 onclick="if(this.dataset.url) openAttachmentPreview(this.dataset.url, this.dataset.name);">
                
                <!-- Checkbox for Deletion -->
                @if(!$isReadOnly)
                <div class="absolute top-2 left-2 z-10" onclick="event.stopPropagation()">
                    <input type="checkbox" class="attachment-checkbox w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 cursor-pointer shadow-sm bg-white" value="{{ $attId ?? $attName }}" onchange="updateDeleteButtonState()">
                </div>
                @endif
                
                <!-- Icon/Preview -->
                <div class="aspect-square bg-gray-100 rounded-md mb-2 flex items-center justify-center overflow-hidden dark:bg-gray-700 pointer-events-none">
                    @if($isImage)
                         <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                         </svg>
                    @elseif(strtolower($ext) == 'pdf')
                         <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                         </svg>
                    @else
                         <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                         </svg>
                    @endif
                </div>
                
                <!-- Name -->
                <p class="text-xs font-medium text-gray-700 truncate dark:text-gray-300 pointer-events-none" title="{{ $attName }}">
                    {{ $attName }}
                </p>
                
                <!-- Individual Delete Action -->
                @if(!$isReadOnly)
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                     <button onclick="event.stopPropagation(); deleteAttachment('{{ $attId ?? addslashes($attName) }}')" class="p-1 bg-white rounded-full shadow text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors cursor-pointer" title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                     </button>
                </div>
                @endif
            </div>
        @empty
            <div class="col-span-full text-center py-8">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No attachments found.</p>
            </div>
        @endforelse
    </div>
</div>
