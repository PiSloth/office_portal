<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl bg-slate-900 p-5 text-white shadow-lg">
            <p class="text-sm text-slate-300">Products</p>
            <p class="mt-3 text-3xl font-bold">{{ number_format($productCount) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-lg dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Scan Configs</p>
            <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($scanConfigCount) }}</p>
        </div>
        <div class="rounded-3xl bg-amber-50 p-5 shadow-lg dark:bg-amber-950/40">
            <p class="text-sm text-amber-700 dark:text-amber-300">Open Decisions</p>
            <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($openDecisionCount) }}</p>
        </div>
        <div class="rounded-3xl bg-emerald-50 p-5 shadow-lg dark:bg-emerald-950/40">
            <p class="text-sm text-emerald-700 dark:text-emerald-300">Active Sessions</p>
            <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($activeSessionCount) }}</p>
        </div>
    </div>
</x-filament-widgets::widget>
