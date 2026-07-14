<div class="min-h-screen overflow-x-hidden bg-slate-50 py-3 sm:py-8" x-data="{
    showScannerModal: @entangle('showScannerModal'),
    showMatchedModal: @entangle('showMatchedModal'),
    showCreateLocationModal: @entangle('showCreateLocationModal'),
    showCreateProductModal: @entangle('showCreateProductModal'),
    showRemarkModal: @entangle('showRemarkModal'),
    showRowActionsModal: false,
    activeRowCheck: { id: null, barcode: '', status: '' },
    visibleColumns: {
        barcode: true,
        product_name: true,
        pickedup_qty: true,
        record_qty: true,
        closing_stock: true,
        actions: false,
        @foreach ($productTypeDynamicFields as $field)
        '{{ $field['field_name'] }}': {{ $field['show_in_table_by_default'] ? 'true' : 'false' }}, @endforeach
    },
    countdown: 0,
    delaySeconds: parseInt(localStorage.getItem('scannerDelaySeconds')) || 3,
    init() {
        this.$watch('showScannerModal', value => {
            window.dispatchEvent(new CustomEvent(value ? 'mobile-scanner-start' : 'mobile-scanner-stop'));
        });

        // hydrate visible columns from localStorage
        try {
            const stored = localStorage.getItem('scannerVisibleColumns');
            if (stored) this.visibleColumns = { ...this.visibleColumns, ...JSON.parse(stored) };
        } catch (e) {
            // ignore
        }

        // enforce required fields
        @foreach($productTypeDynamicFields as $field)
        @php
        $isRequired = collect($scanConfigFields) 
		->where('field', $field['field_name']) 
		->where('required', true) 
		->isNotEmpty();
        @endphp
        @if($isRequired)
        this.visibleColumns['{{ $field['field_name'] }}'] = true;
        @endif
        @endforeach

        // persist visibleColumns when they change
        this.$watch(() => JSON.stringify(this.visibleColumns), (val) => {
            try { localStorage.setItem('scannerVisibleColumns', val); } catch (e) {}
        });
    },
    startCountdown() {
        this.countdown = this.delaySeconds;
        if (this.countdown <= 0) {
            this.showScannerModal = true;
            return;
        }
        let timer = setInterval(() => {
            this.countdown--;
            if (this.countdown <= 0) {
                clearInterval(timer);
                this.showScannerModal = true;
            }
        }, 1000);
    }
}" x-init="init()"
    x-effect="document.body.style.overflow = (showMatchedModal || showScannerModal || showCreateLocationModal || showCreateProductModal || showRemarkModal) ? 'hidden' : ''"
    @check-saved.window="startCountdown()"
    @keydown.window.space="if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA' && !showMatchedModal && !showScannerModal && !showCreateLocationModal && !showCreateProductModal && !showRemarkModal) { $event.preventDefault(); $refs.scanCodeInput.focus(); }">
    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes scan-line {
            0% {
                top: 0%;
            }

            50% {
                top: 100%;
            }

            100% {
                top: 0%;
            }
        }

        .animate-scan {
            animation: scan-line 3s linear infinite;
        }

        #qr-reader {
            border: none !important;
        }

        #qr-reader__scan_region {
            height: 100% !important;
        }

        #qr-reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        #qr-reader__dashboard,
        #qr-reader__status_span {
            display: none !important;
        }
    </style>


    <div class="mx-auto w-full max-w-7xl space-y-4 overflow-x-hidden px-3 sm:space-y-6 sm:px-6 lg:px-8">
        <!-- Header -->

        <div class="grid gap-2 sm:flex sm:items-center sm:gap-3">
            <select wire:model.live="selectedLocationId"
                class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:w-auto">
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
            <button type="button" @click="showCreateLocationModal = true"
                class="inline-flex min-h-10 items-center justify-center rounded-xl bg-amber-50 px-3 text-sm font-semibold text-amber-700 hover:bg-amber-100 sm:bg-transparent sm:px-0 sm:text-amber-600 sm:hover:bg-transparent sm:hover:text-amber-500">
                + New Location
            </button>
            <div class="relative w-full sm:w-auto" x-data="{ openCols: false }" @click.outside="openCols = false">
                <button type="button" @click="openCols = !openCols"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50 dark:bg-gray-900 dark:text-white dark:ring-gray-700 sm:w-auto">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Columns
                </button>
                <!-- Columns Dropdown Menu -->
                <div x-cloak x-show="openCols" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute left-0 sm:left-auto sm:right-0 z-50 mt-2 w-56 origin-top-right rounded-2xl bg-white p-3 shadow-xl ring-1 ring-black/5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700">
                    <p
                        class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-2">
                        Show/Hide Columns</p>
                    <div class="space-y-1">
                        <label
                            class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="visibleColumns.barcode"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                            <span>Barcode</span>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="visibleColumns.product_name"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                            <span>Product Name</span>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="visibleColumns.pickedup_qty"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                            <span>Pickedup</span>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="visibleColumns.record_qty"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                            <span>Record Qty</span>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="visibleColumns.closing_stock"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                            <span>Closing Stock</span>
                        </label>
                        <label
                            class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="visibleColumns.actions"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800">
                            <span>Actions</span>
                        </label>
                        @foreach ($productTypeDynamicFields as $field)
                            @php $isRequired = collect($scanConfigFields)->where('field', $field['field_name'])->where('required', true)->isNotEmpty(); @endphp
                            <label
                                class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                                <input type="checkbox" x-model="visibleColumns.{{ $field['field_name'] }}"
                                    @if ($isRequired) disabled checked class="rounded border-gray-300 text-amber-500 opacity-50 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800"
                                    @else class="rounded border-gray-300 text-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800" @endif>
                                <span>{{ $field['field_label'] }} @if ($isRequired)
                                        *
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400">
            Product: <span
                class="font-semibold text-gray-700 dark:text-gray-300">{{ $productTypes->firstWhere('id', $productTypeId)?->name ?? 'None' }}</span>
            <span class="mx-2">|</span>
            Scan Config: <span
                class="font-semibold text-gray-700 dark:text-gray-300">{{ $selectedScanConfig?->name ?? 'None' }}</span>
        </div>

        @if ($flashMessage)
            <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 2800)" x-show="open" x-transition
                class="fixed left-1/2 top-6 z-[60] w-[calc(100%-2rem)] max-w-md -translate-x-1/2">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-200 bg-white px-4 py-3 shadow-2xl dark:border-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-900/40 dark:bg-gray-900">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-100 text-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-600 dark:bg-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-950/60 dark:text-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-300">
                        @if ($flashTone === 'success')
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @else
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path
                                    d="M12 9v4m0 4h.01M10.3 3.7l-8.6 15A2 2 0 0 0 3.4 22h17.2a2 2 0 0 0 1.7-3.3l-8.6-15a2 2 0 0 0-3.4 0Z"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $flashTone === 'success' ? 'Success' : 'Warning' }}</p>
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $flashMessage }}</p>
                    </div>
                    <button type="button" @click="open = false"
                        class="rounded-full p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if ($deletedCheckIdToRestore)
            <div
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm dark:border-amber-900/30 dark:bg-amber-950/10 dark:text-amber-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="leading-6">Check deleted. Restore it if this was a mistake.</p>
                    <button type="button" wire:click="restoreCheck({{ $deletedCheckIdToRestore }})"
                        class="inline-flex items-center justify-center rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-400">
                        Restore
                    </button>
                </div>
            </div>
        @endif

        <div class="grid min-w-0 gap-6">
            <div class="space-y-6 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900 sm:rounded-3xl sm:p-6">
                <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scanned
                            code</label>
                        <div class="relative">
                            <!-- Scanner Button on Left -->
                            <button type="button" @click="showScannerModal = true"
                                class="absolute left-1 top-1 bottom-1 px-3 flex items-center justify-center rounded-xl bg-amber-500 text-white hover:bg-amber-600 transition shadow-sm"
                                title="Open Camera Scanner">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path
                                        d="M4 7V5a1 1 0 0 1 1-1h2M20 7V5a1 1 0 0 0-1-1h-2M4 17v2a1 1 0 0 0 1 1h2M20 17v2a1 1 0 0 1-1 1h-2M8 12h8"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>

                            <!-- Barcode Input field -->
                            <input x-ref="scanCodeInput" wire:model.defer="scanCode" wire:keydown.enter="processScannedCode" type="text"
                                class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white pl-14 pr-14 py-3 text-base"
                                placeholder="Scan or type a code, then press enter">

                            <!-- Rocket Button on Right -->
                            <button type="button" wire:click="processScannedCode"
                                class="absolute right-1 top-1 bottom-1 px-3.5 flex items-center justify-center rounded-xl bg-slate-950 text-white hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 transition shadow-sm"
                                title="Launch scan/submit">
                                <span class="text-base leading-none">🚀</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Color Legend -->
            <div class="flex flex-wrap items-center gap-4 bg-white px-4 py-3 rounded-2xl shadow-sm border border-gray-100 text-xs dark:bg-gray-900 dark:border-gray-800">
                <span class="font-bold text-gray-400 uppercase tracking-wider text-[10px]">Legend:</span>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md bg-emerald-50 border border-emerald-300 text-emerald-800 shrink-0"></span>
                    <span class="font-medium text-gray-600 dark:text-gray-300">Pass (Matches system expectations)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md bg-rose-50 border border-rose-200 text-rose-800 shrink-0"></span>
                    <span class="font-medium text-gray-600 dark:text-gray-300">Fail (Mismatch / tolerance exceeded)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md bg-amber-50 border border-amber-200 text-amber-800 shrink-0"></span>
                    <span class="font-medium text-gray-600 dark:text-gray-300">Pending (Awaiting scan / verification)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md bg-violet-50 border border-violet-200 text-violet-800 dark:bg-violet-950/20 dark:border-violet-900/40 shrink-0"></span>
                    <span class="font-medium text-gray-600 dark:text-gray-300">Unmatched (Barcode not found in master data)</span>
                </div>
            </div>

            <!-- Checks Table Grouped by Location -->
            <div class="min-w-0 space-y-8">
                @forelse($recentChecksGrouped as $locationName => $checks)
                    <div
                        class="min-w-0 overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-900 sm:rounded-3xl">
                        <div
                            class="bg-slate-100 dark:bg-slate-800 px-4 sm:px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $locationName }}</h3>
                        </div>
                        <div class="w-full max-w-full overflow-x-auto overscroll-x-contain">
                            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300 min-w-[600px]">
                                <thead>
                                    <tr
                                        class="border-b border-gray-200 dark:border-gray-700 uppercase tracking-wider text-xs whitespace-nowrap">
                                        <th x-show="visibleColumns.barcode"
                                            class="px-4 sm:px-6 py-4 font-semibold whitespace-nowrap">Barcode</th>
                                        <th x-show="visibleColumns.product_name"
                                            class="px-4 sm:px-6 py-4 font-semibold whitespace-nowrap min-w-[150px]">
                                            Product Name</th>
                                        <th x-show="visibleColumns.pickedup_qty"
                                            class="px-4 sm:px-6 py-4 font-semibold whitespace-nowrap">Pickedup</th>
                                        <th x-show="visibleColumns.record_qty"
                                            class="px-4 sm:px-6 py-4 font-semibold whitespace-nowrap">Record Qty</th>
                                        <th x-show="visibleColumns.closing_stock"
                                            class="px-4 sm:px-6 py-4 font-semibold whitespace-nowrap">Closing Stock
                                        </th>
                                        @foreach ($productTypeDynamicFields as $field)
                                            <th x-show="visibleColumns.{{ $field['field_name'] }}"
                                                class="px-4 sm:px-6 py-4 font-semibold whitespace-nowrap">
                                                {{ $field['field_label'] }}</th>
                                        @endforeach
                                        <th x-show="visibleColumns.actions"
                                            class="px-4 sm:px-6 py-4 font-semibold whitespace-nowrap">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($checks as $check)
                                        @php
                                            $status = strtoupper($check->result_status ?? '');
                                            $rowBgClass = match ($status) {
                                                'PENDING' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/20 dark:text-amber-300 border-amber-100',
                                                'PASS' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-300 border-emerald-100',
                                                'FAIL' => 'bg-rose-50 text-rose-800 dark:bg-rose-950/20 dark:text-rose-300 border-rose-100',
                                                'UNMATCHED' => 'bg-violet-50 text-violet-800 dark:bg-violet-950/20 dark:text-violet-300 border-violet-100',
                                                default => 'text-gray-900 dark:text-white',
                                            };
                                            $barCodeTextClass = 'font-semibold';
                                        @endphp
                                        <tr wire:key="check-row-{{ $check->id }}"
                                            class="border-b last:border-0 {{ $rowBgClass }}">
                                            <td wire:key="barcode-td-{{ $check->id }}"
                                                x-show="visibleColumns.barcode"
                                                class="px-4 sm:px-6 py-4 font-medium whitespace-nowrap {{ $barCodeTextClass }}">
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="font-semibold">{{ $check->barcode }}</span>
                                                    <div class="flex items-center gap-1.5">
                                                        @if ($status === 'UNMATCHED')
                                                            <span
                                                                class="inline-flex items-center rounded-md bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Unmatched</span>
                                                        @endif
                                                        <button type="button"
                                                            @click="activeRowCheck = { id: {{ $check->id }}, barcode: '{{ $check->barcode }}', status: '{{ $status }}' }; showRowActionsModal = true"
                                                            class="p-1 rounded-lg text-slate-500 hover:bg-slate-200/60 dark:hover:bg-slate-800 transition"
                                                            title="Quick Actions">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Editable Product Name -->
                                            <td wire:key="name-td-{{ $check->id }}-{{ $check->product?->updated_at?->timestamp ?? 0 }}"
                                                x-show="visibleColumns.product_name"
                                                class="px-4 sm:px-6 py-4 whitespace-nowrap" x-data="{ editing: false, name: '{{ addslashes($check->product?->name ?? 'N/A') }}' }">
                                                @if ($check->product)
                                                    <div x-show="!editing" @click="editing = true"
                                                        class="cursor-pointer border-b border-dashed border-gray-400 hover:text-amber-600 transition">
                                                        <span x-text="name"></span>
                                                    </div>
                                                    <input x-cloak x-show="editing" x-model="name" type="text"
                                                        @click.outside="editing = false; $wire.updateProductName({{ $check->product_id }}, name)"
                                                        @keydown.enter="editing = false; $wire.updateProductName({{ $check->product_id }}, name)"
                                                        class="w-full rounded border-gray-300 py-1 text-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-800 dark:border-gray-700">
                                                @else
                                                    <span class="text-gray-400 italic">No product linked</span>
                                                @endif
                                            </td>

                                            <!-- Editable Pickedup Qty -->
                                            <td wire:key="pickedup-qty-td-{{ $check->id }}-{{ $check->updated_at?->timestamp ?? 0 }}"
                                                x-show="visibleColumns.pickedup_qty"
                                                class="px-4 sm:px-6 py-4 whitespace-nowrap" x-data="{ editing: false, qty: {{ $check->quantity ?? 1 }} }">
                                                <div x-show="!editing" @click="editing = true"
                                                    class="cursor-pointer border-b border-dashed border-gray-400 hover:text-amber-600 transition">
                                                    <span x-text="qty"></span>
                                                </div>
                                                <input x-cloak x-show="editing" x-model="qty" type="number"
                                                    min="1"
                                                    @click.outside="editing = false; $wire.updateCheckQuantity({{ $check->id }}, qty)"
                                                    @keydown.enter="editing = false; $wire.updateCheckQuantity({{ $check->id }}, qty)"
                                                    class="w-20 rounded border-gray-300 py-1 text-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-800 dark:border-gray-700">
                                            </td>

                                            <td wire:key="record-qty-td-{{ $check->id }}"
                                                x-show="visibleColumns.record_qty"
                                                class="px-4 sm:px-6 py-4 whitespace-nowrap">{{ $check->record_qty }}
                                            </td>
                                            <td wire:key="closing-stock-td-{{ $check->id }}"
                                                x-show="visibleColumns.closing_stock"
                                                class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $closingStock = $check->product
                                                        ? (int) $check->product->quantity
                                                        : 0;
                                                    $isMatch = $closingStock === (int) $check->quantity;
                                                @endphp
                                                <div class="flex items-center gap-2">
                                                    <span class="font-semibold">{{ $closingStock }}</span>
                                                    @if (!$isMatch)
                                                        <svg class="h-5 w-5 text-amber-500" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2" title="Mismatch with picked up quantity">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-5 w-5 text-emerald-500" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor"
                                                            stroke-width="2" title="Matches picked up quantity">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    @endif
                                                </div>
                                            </td>

                                            @foreach ($productTypeDynamicFields as $field)
                                                <td wire:key="dyn-td-{{ $check->id }}-{{ $field['field_name'] }}-{{ $check->updated_at?->timestamp ?? 0 }}"
                                                    x-show="visibleColumns['{{ $field['field_name'] }}']"
                                                    class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                    @php
                                                        $scf = collect($scanConfigFields)->firstWhere(
                                                            'field',
                                                            $field['field_name'],
                                                        );
                                                        $isEditable = $scf && ($scf['is_editable_in_table'] ?? false);
                                                        $isCompare = $scf && ($scf['compare'] ?? false);

                                                        $actualVal = null;
                                                        $fieldStatus = null;
                                                        if ($check->checkValues) {
                                                            $checkVal = $check->checkValues->firstWhere(
                                                                'field_name',
                                                                $field['field_name'],
                                                            );
                                                            if ($checkVal) {
                                                                $actualVal = $checkVal->actual_value;
                                                                $fieldStatus = $checkVal->status;
                                                            }
                                                        }

                                                        $productVal = null;
                                                        if ($check->product) {
                                                            $attr = $check->product->attributeValues->firstWhere(
                                                                'field_name',
                                                                $field['field_name'],
                                                            );
                                                            $productVal = $attr ? $attr->value : null;
                                                        }

                                                        if ($productVal === null || trim($productVal) === '') {
                                                            $productVal = match ($field['field_type'] ?? 'text') {
                                                                'number', 'decimal' => '0',
                                                                default => '',
                                                            };
                                                        }

                                                        $displayVal =
                                                            $actualVal !== null && $actualVal !== ''
                                                                ? $actualVal
                                                                : ($productVal !== ''
                                                                    ? $productVal
                                                                    : '---');

                                                        // Calculate match status on the fly if not evaluated yet, but only if it's supposed to be compared
                                                        $showIcon = false;
                                                        $isMatch = true;
                                                        if ($isCompare) {
                                                            $showIcon = true;
                                                            if ($fieldStatus !== null) {
                                                                $isMatch = $fieldStatus === 'PASS';
                                                            } else {
                                                                $expectedStr = trim((string) $productVal);
                                                                $actualStr = trim((string) $displayVal);
                                                                $tolerance = $scf['tolerance'] ?? null;
                                                                if (
                                                                    is_numeric($expectedStr) &&
                                                                    is_numeric($actualStr)
                                                                ) {
                                                                    $diff = (float) $actualStr - (float) $expectedStr;
                                                                    if ($tolerance !== null) {
                                                                        $isMatch = abs($diff) <= (float) $tolerance;
                                                                    } else {
                                                                        $isMatch = abs($diff) <= 0.00001;
                                                                    }
                                                                } else {
                                                                    $isMatch =
                                                                        strcasecmp($actualStr, $expectedStr) === 0;
                                                                }
                                                            }
                                                        } elseif ($fieldStatus === 'FAIL') {
                                                            $showIcon = true;
                                                            $isMatch = false;
                                                        }
                                                    @endphp
                                            <div class="flex flex-col items-start gap-1">
                                                        <div class="flex items-center gap-2">
                                                            @if ($isEditable)
                                                                <div class="flex items-center gap-1.5" wire:key="inline-edit-{{ $check->id }}-{{ $field['field_name'] }}" x-data="{ editing: false, val: '{{ addslashes($actualVal ?? '') }}' }" x-effect="val = '{{ addslashes($actualVal ?? '') }}'">
                                                                    <div x-show="!editing" @click="editing = true"
                                                                        class="cursor-pointer border-b border-dashed border-gray-400 font-semibold hover:text-amber-600 transition">
                                                                        @if (($field['field_type'] ?? '') === 'boolean')
                                                                            @if ($actualVal === '1' || $actualVal === 1)
                                                                                Yes
                                                                            @elseif ($actualVal === '0' || $actualVal === 0)
                                                                                No
                                                                            @elseif ($actualVal !== '' && $actualVal !== null)
                                                                                {{ $actualVal }}
                                                                            @else
                                                                                {{ $productVal === '1' || $productVal === 1 ? 'Yes' : ($productVal === '0' || $productVal === 0 ? 'No' : '---') }}
                                                                            @endif
                                                                        @else
                                                                            {{ ($actualVal !== '' && $actualVal !== null) ? $actualVal : ($productVal ?: '---') }}
                                                                        @endif
                                                                    </div>
                                                                    @php
                                                                        $inputType = match (
                                                                            $field['field_type'] ?? 'text'
                                                                        ) {
                                                                            'number' => 'number',
                                                                            'decimal' => 'number',
                                                                            'date' => 'date',
                                                                            default => 'text',
                                                                        };
                                                                        $stepAttr =
                                                                            $field['field_type'] === 'decimal'
                                                                                ? 'any'
                                                                                : ($field['field_type'] === 'number'
                                                                                    ? '1'
                                                                                    : null);
                                                                    @endphp
                                                                    @if (($field['field_type'] ?? '') === 'boolean')
                                                                        <select x-cloak x-show="editing" x-model="val"
                                                                            @change="editing = false; $wire.updateInlineCheckValue({{ $check->id }}, '{{ $field['field_name'] }}', val);"
                                                                            @click.outside="editing = false;"
                                                                            class="w-24 rounded border-gray-300 py-1 text-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-800 dark:border-gray-700">
                                                                            <option value="">Select...</option>
                                                                            <option value="1">Yes</option>
                                                                            <option value="0">No</option>
                                                                        </select>
                                                                    @else
                                                                        <input x-cloak x-show="editing" x-model="val"
                                                                            type="{{ $inputType }}"
                                                                            @if ($stepAttr) step="{{ $stepAttr }}" @endif
                                                                            placeholder="{{ addslashes($productVal) }}"
                                                                            @click.outside="if(editing) { editing = false; $wire.updateInlineCheckValue({{ $check->id }}, '{{ $field['field_name'] }}', val !== '' ? val : '{{ addslashes($productVal) }}'); }"
                                                                            @keydown.enter="if(editing) { editing = false; $wire.updateInlineCheckValue({{ $check->id }}, '{{ $field['field_name'] }}', val !== '' ? val : '{{ addslashes($productVal) }}'); }"
                                                                            class="w-24 rounded border-gray-300 py-1 text-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-800 dark:border-gray-700">
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <span>{{ $displayVal }}</span>
                                                            @endif

                                                            @if ($showIcon)
                                                                @if (!$isMatch)
                                                                    <svg class="h-5 w-5 text-amber-500 shrink-0"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        title="Mismatch with master product data">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    </svg>
                                                                @else
                                                                    <svg class="h-5 w-5 text-emerald-500 shrink-0"
                                                                        fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        title="Matches master product data">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                @endif
                                                            @endif
                                                        </div>

                                                        @if (filter_var($scf['is_quickcheck'] ?? false, FILTER_VALIDATE_BOOLEAN) && blank($actualVal))
                                                            <button type="button"
                                                                wire:click="updateInlineCheckValue({{ $check->id }}, '{{ $field['field_name'] }}', '{{ addslashes($productVal) }}')"
                                                                class="mt-1 inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 hover:bg-amber-200 transition shrink-0"
                                                                title="Quick copy expected value">
                                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                Quick Check
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach

                                            <td wire:key="actions-td-{{ $check->id }}"
                                                x-show="visibleColumns.actions"
                                                class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <!-- Finding / Validation button -->
                                                    <button type="button"
                                                        wire:click="openComparison({{ $check->id }})"
                                                        title="Put actual facts"
                                                        class="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-white transition">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>

                                                    <!-- Remark / Decision button -->
                                                    <button type="button"
                                                        wire:click="openRemarkModal({{ $check->id }})"
                                                        title="Add Remark"
                                                        class="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-white transition">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                        </svg>
                                                    </button>

                                                    <!-- Delete check button -->
                                                    <button type="button"
                                                        wire:click="deleteCheck({{ $check->id }})"
                                                        onclick="if (!confirm('Delete this scanned check? It can be restored if clicked by mistake.')) { event.stopImmediatePropagation(); event.preventDefault(); }"
                                                        title="Delete Check"
                                                        class="rounded p-1.5 text-rose-500 hover:bg-rose-100 hover:text-rose-900 dark:hover:bg-rose-900/30 dark:hover:text-white transition">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>

                                                    <!-- Create Product (If Unmatched) -->
                                                    @if ($check->result_status === 'UNMATCHED')
                                                        <button type="button"
                                                            wire:click="openCreateProduct({{ $check->id }})"
                                                            title="Create Product"
                                                            class="rounded p-1.5 text-amber-600 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 transition">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M12 4v16m8-8H4" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-2xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700 sm:rounded-3xl sm:p-12">
                        <p class="text-gray-500 dark:text-gray-400">No scanned items yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Scanner Modal -->
        <div x-cloak x-show="showScannerModal" x-transition.opacity
            class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-950/80 p-0 backdrop-blur-md sm:items-center sm:px-4 sm:py-6">
            <div class="relative flex max-h-[100dvh] w-full flex-col overflow-hidden rounded-t-3xl bg-white p-4 shadow-2xl dark:bg-gray-900 sm:max-w-md sm:rounded-3xl sm:p-6"
                @click.outside="showScannerModal = false">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Camera Scanner</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Position the QR or Barcode inside the box
                        </p>
                    </div>
                    <button type="button" @click="showScannerModal = false"
                        class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div
                    class="relative mt-4 aspect-[3/4] max-h-[72dvh] overflow-hidden rounded-2xl bg-black sm:aspect-square sm:max-h-none">
                    <div wire:ignore id="qr-reader" class="w-full h-full"></div>
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                        <div
                            class="w-2/3 h-2/3 border-2 border-dashed border-amber-500 rounded-2xl relative shadow-[0_0_0_9999px_rgba(0,0,0,0.5)]">
                            <div
                                class="absolute -top-1.5 -left-1.5 w-6 h-6 border-t-4 border-l-4 border-amber-500 rounded-tl-md">
                            </div>
                            <div
                                class="absolute -top-1.5 -right-1.5 w-6 h-6 border-t-4 border-r-4 border-amber-500 rounded-tr-md">
                            </div>
                            <div
                                class="absolute -bottom-1.5 -left-1.5 w-6 h-6 border-b-4 border-l-4 border-amber-500 rounded-bl-md">
                            </div>
                            <div
                                class="absolute -bottom-1.5 -right-1.5 w-6 h-6 border-b-4 border-r-4 border-amber-500 rounded-br-md">
                            </div>
                            <div
                                class="absolute left-0 right-0 h-0.5 bg-amber-500 shadow-[0_0_10px_#f59e0b] animate-scan">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Create Location Modal -->
        <div x-cloak x-show="showCreateLocationModal" x-transition.opacity
            class="fixed inset-0 z-[150] flex items-end justify-center bg-slate-950/80 p-0 backdrop-blur-md sm:items-center sm:px-4 sm:py-6">
            <div class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-4 shadow-2xl dark:bg-gray-900 sm:max-w-md sm:rounded-3xl sm:p-6"
                @click.outside="showCreateLocationModal = false">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Create New Location</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company /
                            Location Name</label>
                        <input wire:model="newLocationName" type="text"
                            class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="newLocationDescription" rows="3"
                            class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-3 sm:flex sm:justify-end">
                    <button type="button" @click="showCreateLocationModal = false"
                        class="min-h-11 rounded-full bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-white">Cancel</button>
                    <button type="button" wire:click="createLocation"
                        class="min-h-11 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-400">Create</button>
                </div>
            </div>
        </div>

        <!-- Create Product Modal -->
        <div x-cloak x-show="showCreateProductModal" x-transition.opacity
            class="fixed inset-0 z-[150] flex items-end justify-center bg-slate-950/80 p-0 backdrop-blur-md sm:items-center sm:px-4 sm:py-6">
            <div class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-4 shadow-2xl dark:bg-gray-900 sm:max-w-md sm:rounded-3xl sm:p-6"
                @click.outside="showCreateProductModal = false">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Create Unmatched Product</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Code /
                            Barcode</label>
                        <input wire:model="createProductCode" type="text"
                            class="w-full rounded-xl border-amber-300 bg-amber-50 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-semibold">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product
                            Name</label>
                        <input wire:model="createProductName" type="text"
                            class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product
                            Type</label>
                        <div
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 font-medium">
                            {{ $productTypes->firstWhere('id', $createProductTypeId)?->name ?? 'None' }}
                        </div>
                        <input type="hidden" wire:model="createProductTypeId">
                        @error('createProductTypeId')
                            <span class="text-xs text-rose-500">{{ $message }}</span>
                        @enderror
                    </div>

                    @foreach ($createProductDynamicFields as $field)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field['field_label'] }}
                                @if (isset($field['required']) && $field['required'])
                                    <span class="text-rose-500">*</span>
                                @endif
                            </label>

                            @if ($field['field_type'] === 'textarea')
                                <textarea wire:model="createProductDynamicValues.{{ $field['field_name'] }}" rows="2"
                                    class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                            @elseif ($field['field_type'] === 'boolean')
                                <select wire:model="createProductDynamicValues.{{ $field['field_name'] }}"
                                    class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="">Select...</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            @elseif ($field['field_type'] === 'select')
                                <select wire:model="createProductDynamicValues.{{ $field['field_name'] }}"
                                    class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="">Select option</option>
                                    <!-- Options would be populated here if available -->
                                </select>
                            @else
                                @php
                                    $inputType = match ($field['field_type'] ?? 'text') {
                                        'number' => 'number',
                                        'decimal' => 'number',
                                        'date' => 'date',
                                        default => 'text',
                                    };
                                    $stepAttr =
                                        $field['field_type'] === 'decimal'
                                            ? 'any'
                                            : ($field['field_type'] === 'number'
                                                ? '1'
                                                : null);
                                @endphp
                                <input wire:model="createProductDynamicValues.{{ $field['field_name'] }}"
                                    type="{{ $inputType }}"
                                    @if ($stepAttr) step="{{ $stepAttr }}" @endif
                                    class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @endif

                            @error('createProductDynamicValues.' . $field['field_name'])
                                <span class="text-xs text-rose-500">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 grid grid-cols-2 gap-3 sm:flex sm:justify-end">
                    <button type="button" @click="showCreateProductModal = false"
                        class="min-h-11 rounded-full bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-white">Cancel</button>
                    <button type="button" wire:click="saveCreatedProduct"
                        class="min-h-11 rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-400">Save
                        Product</button>
                </div>
            </div>
        </div>

        <!-- Remark / Decision Modal -->
        <div x-cloak x-show="showRemarkModal" x-transition.opacity
            class="fixed inset-0 z-[150] flex items-end justify-center bg-slate-950/80 p-0 backdrop-blur-md sm:items-center sm:px-4 sm:py-6">
            <div class="max-h-[92dvh] w-full overflow-y-auto rounded-t-3xl bg-white p-4 shadow-2xl dark:bg-gray-900 sm:max-w-md sm:rounded-3xl sm:p-6"
                @click.outside="showRemarkModal = false">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Add Remark & Decision</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remark /
                            Comment</label>
                        <textarea wire:model="remarkText" rows="3"
                            class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="Enter remark..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Decision Type
                            (Optional)</label>
                        <select wire:model="decisionTypeId"
                            class="w-full rounded-xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">None</option>
                            @foreach ($decisionTypes as $dt)
                                <option value="{{ $dt->id }}">{{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-3 sm:flex sm:justify-end">
                    <button type="button" @click="showRemarkModal = false"
                        class="min-h-11 rounded-full bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-white">Cancel</button>
                    <button type="button" wire:click="saveRemark"
                        class="min-h-11 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-400">Save</button>
                </div>
            </div>
        </div>

        <!-- Matched/Validation Modal (Finding) -->
        <div x-cloak x-show="showMatchedModal" x-transition.opacity
            class="fixed inset-0 z-[120] flex items-end justify-center overflow-hidden bg-slate-950/80 p-0 backdrop-blur-md sm:items-start sm:px-4 sm:py-6"
            @touchmove.prevent>
            <div class="flex h-[94vh] w-full max-w-[100vw] flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl dark:bg-gray-900 sm:h-auto sm:max-h-[calc(100vh-4rem)] sm:max-w-5xl sm:rounded-3xl"
                @click.outside="showMatchedModal = false">
                <div
                    class="shrink-0 flex items-start justify-between border-b border-gray-100 p-4 dark:border-gray-800 sm:p-6">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Validation (Finding)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Put actual facts to validate this product
                            check.</p>
                    </div>
                    <button type="button" @click="showMatchedModal = false"
                        class="rounded-full p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        aria-label="Close validation"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" />
                        </svg></button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto overflow-x-auto overscroll-contain p-4 [-webkit-overflow-scrolling:touch] sm:p-6"
                    @touchmove.stop @wheel.stop>
                    @if ($selectedProduct)
                        <div class="grid gap-6 lg:grid-cols-2">
                            <div class="space-y-4 rounded-2xl bg-slate-950 p-4 text-white sm:rounded-3xl sm:p-5">
                                <div class="rounded-2xl bg-white/10 p-4">
                                    <p class="text-sm text-slate-300">Code</p>
                                    <p class="break-words text-xl font-semibold">{{ $selectedProduct->code }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-4">
                                    <p class="text-sm text-slate-300">Name</p>
                                    <p class="break-words text-lg font-semibold">{{ $selectedProduct->name }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-4">
                                    <p class="text-sm text-slate-300">Dynamic values</p>
                                    <div class="mt-3 space-y-2 text-sm">
                                        @forelse ($selectedProduct->attributeValues as $attributeValue)
                                            <div
                                                class="grid gap-1 sm:flex sm:items-center sm:justify-between sm:gap-3">
                                                <span class="text-slate-300">{{ $attributeValue->field_name }}</span>
                                                <span
                                                    class="break-words font-medium">{{ $attributeValue->value }}</span>
                                            </div>
                                        @empty
                                            <p class="text-slate-300">No dynamic values stored.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 sm:rounded-3xl sm:p-5">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Comparison form</h4>
                                <div class="mt-4 grid gap-4">
                                    @forelse ($scanConfigFields as $fieldConfig)
                                        @php
                                            $fieldName = $fieldConfig['field'] ?? '';
                                            $fieldLabel =
                                                $fieldConfig['field_name'] ?? ($fieldConfig['field'] ?? 'Field');
                                            $expectedValue = $selectedProduct
                                                ? match ($fieldName) {
                                                    'location_id',
                                                    'category_id',
                                                    'sub_category_id'
                                                        => $selectedProduct->{$fieldName},
                                                    'code',
                                                    'barcode',
                                                    'qr_code',
                                                    'name',
                                                    'description',
                                                    'status'
                                                        => $selectedProduct->{$fieldName},
                                                    default => $selectedProduct->attributeValues->firstWhere(
                                                        'field_name',
                                                        $fieldName,
                                                    )?->value,
                                                }
                                                : null;
                                        @endphp
                                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="break-words font-semibold text-gray-900 dark:text-white">
                                                    {{ $fieldLabel }}</p>
                                                <span
                                                    class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                                    {{ data_get($fieldConfig, 'compare', false) ? 'Compare' : 'Note' }}
                                                </span>
                                            </div>
                                            <div class="mt-4 grid gap-3">
                                                <div
                                                    class="rounded-xl bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                    <span
                                                        class="block text-xs uppercase tracking-[0.25em] text-gray-400">Expected</span>
                                                    <span
                                                        class="mt-1 block break-words">
                                                        @if (($fieldConfig['field_type'] ?? '') === 'boolean')
                                                            {{ $expectedValue === '1' || $expectedValue === 1 ? 'Yes' : ($expectedValue === '0' || $expectedValue === 0 ? 'No' : 'N/A') }}
                                                        @else
                                                            {{ $expectedValue ?? 'N/A' }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <div>
                                                     <div class="flex items-center justify-between mb-2">
                                                         <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Actual</label>
                                                         @if (filter_var($fieldConfig['is_quickcheck'] ?? false, FILTER_VALIDATE_BOOLEAN) && blank(data_get($actualValues, $fieldName)))
                                                             <button type="button" wire:click="quickCheck('{{ $fieldName }}')"
                                                                 class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition"
                                                                 title="Quick copy expected value">
                                                                 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                     <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                 </svg>
                                                                 Quick Check
                                                             </button>
                                                         @endif
                                                     </div>
                                                    @if (($fieldConfig['field_type'] ?? '') === 'boolean')
                                                        <select wire:model.live="actualValues.{{ $fieldName }}"
                                                            class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                            <option value="">Select...</option>
                                                            <option value="1">Yes</option>
                                                            <option value="0">No</option>
                                                        </select>
                                                    @else
                                                        <input wire:model.live="actualValues.{{ $fieldName }}"
                                                            type="text"
                                                            class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                    @endif
                                                    @error("actualValues.{$fieldName}")
                                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No compare fields in
                                            config.</p>
                                    @endforelse
                                </div>

                                <div class="mt-6 grid gap-3 sm:flex sm:flex-wrap sm:gap-4">
                                    <label
                                        class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                                        <input x-ref="photoInput" type="file" wire:model="attachments" multiple
                                            accept="image/*" class="sr-only">
                                        <svg class="w-6 h-6 mr-2" viewBox="0 0 48 48" version="1"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill="#E65100"
                                                d="M41,42H13c-2.2,0-4-1.8-4-4V18c0-2.2,1.8-4,4-4h28c2.2,0,4,1.8,4,4v20C45,40.2,43.2,42,41,42z" />
                                            <path fill="#F57C00"
                                                d="M35,36H7c-2.2,0-4-1.8-4-4V12c0-2.2,1.8-4,4-4h28c2.2,0,4,1.8,4,4v20C39,34.2,37.2,36,35,36z" />
                                            <circle fill="#FFF9C4" cx="30" cy="16" r="3" />
                                            <polygon fill="#942A09" points="17,17.9 8,31 26,31" />
                                            <polygon fill="#BF360C" points="28,23.5 22,31 34,31" />
                                        </svg>
                                        Gallery
                                    </label>
                                    <label
                                        class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                                        <input x-ref="cameraInput" type="file" wire:model="cameraAttachments"
                                            multiple accept="image/*" capture="environment" class="sr-only">
                                        Camera
                                    </label>
                                    <button type="button" wire:click="saveValidation"
                                        class="min-h-11 rounded-full bg-emerald-500 px-6 py-2 text-sm font-semibold text-white transition hover:bg-emerald-400 sm:ml-auto">
                                        Save Facts
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Row Actions Quick Modal -->
        <div x-cloak x-show="showRowActionsModal" x-transition.opacity
            class="fixed inset-0 z-[140] flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-md">
            <div class="relative w-full max-w-sm rounded-3xl bg-white/90 p-6 shadow-2xl backdrop-blur-lg border border-white/20 dark:bg-gray-900/90 dark:border-gray-800/40"
                @click.outside="showRowActionsModal = false">
                <!-- Close button -->
                <button type="button" @click="showRowActionsModal = false"
                    class="absolute right-4 top-4 rounded-full p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Title -->
                <div class="text-center mb-6">
                    <p class="text-xs uppercase tracking-widest text-slate-400 font-semibold mb-1">Scanned Barcode</p>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white break-all"
                        x-text="activeRowCheck.barcode"></h3>
                </div>

                <!-- Actions List -->
                <div class="space-y-3">
                    <!-- Validation/Facts -->
                    <button type="button"
                        @click="$wire.openComparison(activeRowCheck.id); showRowActionsModal = false"
                        class="flex w-full items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:bg-slate-800/50 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-bold text-slate-950 dark:text-white">Validation Facts</p>
                            <p class="text-xs text-slate-400">Put actual comparison details</p>
                        </div>
                    </button>

                    <!-- Remark/Decision -->
                    <button type="button"
                        @click="$wire.openRemarkModal(activeRowCheck.id); showRowActionsModal = false"
                        class="flex w-full items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:bg-slate-800/50 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-bold text-slate-950 dark:text-white">Add Remark</p>
                            <p class="text-xs text-slate-400">Add observation & decision</p>
                        </div>
                    </button>

                    <!-- Create Product (Conditional: only shown if Unmatched) -->
                    <template x-if="activeRowCheck.status === 'UNMATCHED'">
                        <button type="button"
                            @click="$wire.openCreateProduct(activeRowCheck.id); showRowActionsModal = false"
                            class="flex w-full items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 dark:bg-slate-800/50 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-slate-950 dark:text-white">Create Product</p>
                                <p class="text-xs text-slate-400">Create new linked product</p>
                            </div>
                        </button>
                    </template>

                    <!-- Delete Check -->
                    <button type="button"
                        @click="if (confirm('Delete this scanned check? It can be restored if clicked by mistake.')) { $wire.deleteCheck(activeRowCheck.id); showRowActionsModal = false; }"
                        class="flex w-full items-center gap-3 rounded-2xl bg-rose-50/60 px-4 py-3.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 dark:bg-rose-950/10 dark:text-rose-400 dark:hover:bg-rose-950/20">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-bold text-rose-800 dark:text-rose-300">Delete Check</p>
                            <p class="text-xs text-rose-400/80">Remove scanned validation check</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            if (typeof Html5Qrcode === 'undefined') {
                return;
            }

            const readerId = 'qr-reader';
            let html5QrCode = null;
            let scannerRunning = false;

            const stopScanner = async () => {
                if (html5QrCode && html5QrCode.isScanning) {
                    try {
                        await html5QrCode.stop();
                    } catch (error) {
                        console.error('Error stopping scanner:', error);
                    }
                }
                html5QrCode = null;
                scannerRunning = false;
                const container = document.getElementById(readerId);
                if (container) {
                    container.innerHTML = '';
                }
            };

            const startScanner = async () => {
                if (scannerRunning) {
                    return;
                }

                const container = document.getElementById(readerId);
                if (!container) {
                    return;
                }

                html5QrCode = new Html5Qrcode(readerId);
                scannerRunning = true;

                try {
                    await html5QrCode.start({
                            facingMode: "environment"
                        }, {
                            fps: 10,
                            qrbox: (width, height) => {
                                const size = Math.min(width, height) * 0.7;
                                return {
                                    width: Math.floor(size),
                                    height: Math.floor(size),
                                };
                            },
                            aspectRatio: 1.0
                        },
                        (decodedText) => {
                            const input = document.getElementById('scan-code-main');
                            if (input) {
                                input.value = decodedText;
                                input.dispatchEvent(new Event('input', {
                                    bubbles: true
                                }));
                            }

                            @this.set('scanCode', decodedText);
                            @this.set('showScannerModal', false);
                            @this.call('processScannedCode');
                            window.dispatchEvent(new CustomEvent('mobile-scanner-stop'));
                        },
                        (errorMessage) => {
                            // ignore parse error messages
                        }
                    );
                } catch (error) {
                    console.error('Failed to start scanner:', error);
                    scannerRunning = false;
                    html5QrCode = null;
                }
            };

            window.addEventListener('mobile-scanner-start', startScanner);
            window.addEventListener('mobile-scanner-stop', stopScanner);
            window.addEventListener('mobile-scanner-restart', async () => {
                window.setTimeout(() => {
                    startScanner();
                }, 150);
            });
        });
    </script>
</div>
