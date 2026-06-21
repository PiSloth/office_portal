<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Pending Decisions</p>
            <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pendingDecisions) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">In Progress</p>
            <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($inProgressDecisions) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Failed Checks</p>
            <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($failedChecks) }}</p>
        </div>
    </div>
</x-filament-widgets::widget>
