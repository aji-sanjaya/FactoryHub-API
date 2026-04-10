@props(['countAll' => 0, 'countDraft' => 0, 'countInProgress' => 0, 'countCompleted' => 0])

<div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:gap-6">
    <div
        class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
        <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">{{ number_format($countAll) }}</h4>
        <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">ALL DOCUMENTS</p>
    </div>
    <div
        class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
        <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">{{ number_format($countDraft) }}
        </h4>
        <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">DRAFT</p>
    </div>
    <div
        class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
        <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">
            {{ number_format($countInProgress) }}</h4>
        <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">IN PROGRESS</p>
    </div>
    <div
        class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 text-center">
        <h4 class="mb-1.5 text-title-md font-bold text-gray-800 dark:text-white/90">{{ number_format($countCompleted) }}
        </h4>
        <p class="font-medium text-gray-500 text-theme-sm dark:text-gray-400">COMPLETED</p>
    </div>
</div>