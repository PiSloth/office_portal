<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ __('Operations Dashboard') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Jump straight into inventory review, mobile scanning, or decision triage.
                </p>
            </div>
            <a href="{{ url('/admin') }}" class="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-400">
                Open Admin Panel
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('checker.dashboard') }}" class="rounded-3xl border border-white/10 bg-slate-900/90 p-6 text-white shadow-xl transition hover:-translate-y-1 hover:bg-slate-800">
                    <p class="text-sm uppercase tracking-[0.35em] text-amber-300">Checker</p>
                    <h3 class="mt-4 text-2xl font-semibold">Mobile Scan Flow</h3>
                    <p class="mt-2 text-sm text-slate-300">Select a session, scan a product, compare values, and save the check.</p>
                </a>
                <a href="{{ route('supervisor.dashboard') }}" class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-xl transition hover:-translate-y-1 dark:border-amber-900/40 dark:bg-amber-950/40">
                    <p class="text-sm uppercase tracking-[0.35em] text-amber-700 dark:text-amber-300">Supervisor</p>
                    <h3 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">Decision Queue</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Review open investigations and keep mismatches moving.</p>
                </a>
                <a href="{{ route('scanner') }}" class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-xl transition hover:-translate-y-1 dark:border-emerald-900/40 dark:bg-emerald-950/40">
                    <p class="text-sm uppercase tracking-[0.35em] text-emerald-700 dark:text-emerald-300">Scanner</p>
                    <h3 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">Launch Camera</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Use the QR and barcode scanner directly from the browser.</p>
                </a>
                <a href="{{ route('import.guide') }}" class="rounded-3xl border border-sky-200 bg-sky-50 p-6 shadow-xl transition hover:-translate-y-1 dark:border-sky-900/40 dark:bg-sky-950/40">
                    <p class="text-sm uppercase tracking-[0.35em] text-sky-700 dark:text-sky-300">Inventory</p>
                    <h3 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">Import Setup Guide</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">See the CSV template, required columns, and configuration checklist.</p>
                </a>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Project status</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">The backend, admin catalog, and scanner workflow are now wired together.</p>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Review the remaining reports from the admin panel and dashboards.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
