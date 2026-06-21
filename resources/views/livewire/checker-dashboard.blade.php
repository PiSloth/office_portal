<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Checker Dashboard</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Start a session, scan products, and review mismatches from one place.</p>
            </div>
            <a href="{{ route('scanner') }}" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-400">
                Open Scanner
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-3xl bg-slate-900 p-5 text-white shadow-lg">
                <p class="text-sm text-slate-300">Active Sessions</p>
                <p class="mt-3 text-3xl font-bold">{{ $sessionCount }}</p>
            </div>
            <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Scan Configs</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $scanConfigCount }}</p>
            </div>
            <div class="rounded-3xl bg-emerald-50 p-5 shadow-lg dark:bg-emerald-950/40">
                <p class="text-sm text-emerald-700 dark:text-emerald-300">Checks Today</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $todayChecks }}</p>
            </div>
            <div class="rounded-3xl bg-amber-50 p-5 shadow-lg dark:bg-amber-950/40">
                <p class="text-sm text-amber-700 dark:text-amber-300">Failed Checks</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $failedChecks }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent sessions</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($recentSessions as $session)
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $session->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->status }} by {{ $session->startedBy?->name ?? 'System' }}</p>
                                </div>
                                <span class="text-xs uppercase tracking-[0.25em] text-gray-400">{{ optional($session->started_at)->format('M d') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No sessions yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Open decisions</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($openDecisions as $decision)
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $decision->decisionType?->name ?? 'Decision' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $decision->productCheck?->product?->name ?? 'Unknown product' }} · {{ $decision->action_status }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No open decisions.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
