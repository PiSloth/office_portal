<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Supervisor Dashboard</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Keep investigations moving and monitor the health of inspections.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Open Decisions</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $openDecisionCount }}</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">In Progress</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $inProgressDecisions }}</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Failed Checks</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $failedCheckCount }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Warnings: {{ $warningCheckCount }}</p>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Open decision queue</h3>
            <div class="mt-4 space-y-4">
                @forelse ($openDecisions as $decision)
                    <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $decision->decisionType?->name ?? 'Decision' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $decision->productCheck?->product?->code ?? 'Unknown code' }} · {{ $decision->productCheck?->product?->name ?? 'Unknown product' }}</p>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Assigned to {{ $decision->assignedTo?->name ?? 'Unassigned' }}
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $decision->remark }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No pending decisions right now.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
