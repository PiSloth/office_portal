<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::card class="bg-slate-900 text-white">
            <div>
                <p class="text-sm text-slate-300">Products</p>
                <p class="mt-3 text-3xl font-bold">{{ number_format($productCount) }}</p>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Scan Configs</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($scanConfigCount) }}</p>
            </div>
        </x-filament::card>

        <x-filament::card class="bg-amber-50">
            <div>
                <p class="text-sm text-amber-700 dark:text-amber-300">Open Decisions</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($openDecisionCount) }}</p>
            </div>
        </x-filament::card>

        <x-filament::card class="bg-emerald-50">
            <div>
                <p class="text-sm text-emerald-700 dark:text-emerald-300">Active Sessions</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($activeSessionCount) }}</p>
            </div>
        </x-filament::card>
    </div>
</x-filament-widgets::widget>
