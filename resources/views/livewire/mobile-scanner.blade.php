<div class="min-h-screen bg-slate-50 py-4 sm:py-8" x-data="{
    showSelectionModal: @entangle('showSelectionModal'),
    showScannerModal: @entangle('showScannerModal'),
    showMatchedModal: @entangle('showMatchedModal'),
    init() {
        this.$watch('showScannerModal', value => {
            window.dispatchEvent(new CustomEvent(value ? 'mobile-scanner-start' : 'mobile-scanner-stop'));
        });
    },
}" x-init="init()">
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

    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900 dark:text-white">Mobile Scanner</h2>

            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="showSelectionModal = true"
                    class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50 dark:bg-gray-900 dark:text-white dark:ring-gray-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h10M4 18h16" stroke-linecap="round" />
                    </svg>
                    Filters
                </button>

                <button type="button" @click="showScannerModal = true"
                    class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M4 7V5a1 1 0 0 1 1-1h2M20 7V5a1 1 0 0 0-1-1h-2M4 17v2a1 1 0 0 0 1 1h2M20 17v2a1 1 0 0 1-1 1h-2M8 12h8"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Start scanning
                </button>
            </div>
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
                            {{ $flashTone === 'success' ? 'Success' : 'Warning' }}
                        </p>
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

        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="space-y-6 rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                {{-- <div
                    class="rounded-3xl border border-dashed border-amber-300 bg-amber-50/60 p-6 dark:border-amber-900/60 dark:bg-amber-950/30">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Camera Scanner</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Click the button to scan barcodes or QR
                                codes with your device camera.</p>
                        </div>
                        <button type="button" @click="showScannerModal = true"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-gray-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M4 7V5a1 1 0 0 1 1-1h2M20 7V5a1 1 0 0 0-1-1h-2M4 17v2a1 1 0 0 0 1 1h2M20 17v2a1 1 0 0 1-1 1h-2M8 12h8"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Start Scanner
                        </button>
                    </div>
                </div> --}}

                <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scanned
                            code</label>
                        <input id="scan-code-main" wire:model.live.debounce.5000ms="scanCode" type="text"
                            class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            placeholder="Scan or type a code">
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="matchProductFromCode"
                            class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            Match now
                        </button>
                    </div>
                </div>
            </div>

            {{-- <div class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm">
                <h3 class="text-lg font-semibold">Matched product</h3>

                @if ($selectedProduct)
                    <div class="mt-4 space-y-3">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-sm text-slate-300">Code</p>
                            <p class="text-xl font-semibold">{{ $selectedProduct->code }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-sm text-slate-300">Name</p>
                            <p class="text-lg font-semibold">{{ $selectedProduct->name }}</p>
                            <p class="mt-1 text-sm text-slate-300">{{ $selectedProduct->category?->name }} /
                                {{ $selectedProduct->subCategory?->name }}</p>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-sm text-slate-300">Location</p>
                                <p class="font-semibold">{{ $selectedProduct->location?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-sm text-slate-300">Type</p>
                                <p class="font-semibold">{{ $selectedProduct->productType?->name }}</p>
                            </div>
                        </div>
                        <button type="button" @click="showMatchedModal = true"
                            class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200">
                            Open comparison
                        </button>
                    </div>
                @else
                    <div class="mt-4 rounded-2xl border border-dashed border-white/20 p-6 text-sm text-slate-300">
                        Scan a barcode or QR code to load the product details here.
                    </div>
                @endif
            </div> --}}
        </div>

        @if ($lastResult)
            <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Validation result</h3>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400 space-y-1">
                    <p>Overall status: <span
                            class="font-medium text-gray-900 dark:text-white">{{ $lastResult['result_status'] }}</span>
                    </p>
                    @if (!empty($lastResult['checked_by_name']))
                        <p>Checked by: <span
                                class="font-medium text-gray-900 dark:text-white">{{ $lastResult['checked_by_name'] }}</span>
                        </p>
                    @elseif(!empty($lastResult['checked_by_id']))
                        <p>Checked by: <span class="font-medium text-gray-900 dark:text-white">User
                                #{{ $lastResult['checked_by_id'] }}</span></p>
                    @endif
                    @if (!empty($lastResult['scanned_code']))
                        <p>Scanned code: <span
                                class="font-medium text-gray-900 dark:text-white">{{ $lastResult['scanned_code'] }}</span>
                        </p>
                    @endif
                    @if (!empty($lastResult['checked_at']))
                        <p>Checked at: <span
                                class="font-medium text-gray-900 dark:text-white">{{ $lastResult['checked_at'] }}</span>
                        </p>
                    @endif
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($lastResult['values'] as $result)
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $result['field_name'] }}</p>
                                <span
                                    class="text-xs font-semibold uppercase tracking-[0.25em] {{ $result['status'] === 'PASS' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $result['status'] }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Expected:
                                {{ $result['expected_value'] ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Actual:
                                {{ $result['actual_value'] ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Difference:
                                {{ $result['difference_value'] ?? 'N/A' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Scanner Modal Overlay -->
    <div x-cloak x-show="showScannerModal" x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6 backdrop-blur-md">
        <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900"
            @click.outside="showScannerModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Camera Scanner</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Position the QR or Barcode inside the box</p>
                </div>
                <button type="button" @click="showScannerModal = false"
                    class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scanner Frame -->
            <div class="mt-4 relative overflow-hidden rounded-2xl bg-black aspect-square">
                <!-- HTML5 QR Reader element -->
                <div wire:ignore id="qr-reader" class="w-full h-full"></div>

                <!-- Guided Scan Overlay (Square Finder Box) -->
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                    <div
                        class="w-2/3 h-2/3 border-2 border-dashed border-amber-500 rounded-2xl relative shadow-[0_0_0_9999px_rgba(0,0,0,0.5)]">
                        <!-- Corner guides -->
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

                        <!-- Scanning line animation -->
                        <div class="absolute left-0 right-0 h-0.5 bg-amber-500 shadow-[0_0_10px_#f59e0b] animate-scan">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Scan will start automatically once camera access is
                    granted.</p>
            </div>
        </div>
    </div>

    <div x-cloak x-show="showSelectionModal" x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6 backdrop-blur-md">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900"
            @click.outside="showSelectionModal = false">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Selection filters</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pick the session, product type, and scan
                        configuration here.</p>
                </div>
                <button type="button" @click="showSelectionModal = false"
                    class="text-gray-400 transition hover:text-gray-700 dark:hover:text-gray-200">×</button>
            </div>

            <div class="mt-6 grid gap-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Check
                        session</label>
                    <select wire:model.live="checkSessionId"
                        class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Choose session</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }} ({{ $session->status }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Product type</label>
                    <select wire:model.live="productTypeId"
                        class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Choose type</option>
                        @foreach ($productTypes as $productType)
                            <option value="{{ $productType->id }}">{{ $productType->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div wire:key="scan-config-select-{{ $productTypeId ?? 'none' }}">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scan config</label>
                    <select wire:model.live="scanConfigId"
                        class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Choose config</option>
                        @foreach ($scanConfigs as $config)
                            <option value="{{ $config->id }}">{{ $config->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="showSelectionModal = false"
                    class="rounded-full bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-900 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                    Done
                </button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="showMatchedModal" x-transition.opacity
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/80 px-4 py-6 backdrop-blur-md">
        <div class="w-full max-w-5xl max-h-[calc(100vh-4rem)] overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900"
            @click.outside="showMatchedModal = false">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Matched product</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $selectedProduct ? 'Review values, attach evidence, and save the check.' : 'တူညီသော ပစ္စည်းမရှိပါ၊ Code ပြန်လည် စစ်ဆေးပါ (သို့) ပုံနှင့်တကွ သိမ်းဆည်းပါ' }}
                    </p>
                    @if ($selectedScanConfig)
                        <div
                            class="mt-3 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 dark:border-amber-700/40 dark:bg-amber-950/40 dark:text-amber-300">
                            Scan config: {{ $selectedScanConfig->name }}
                        </div>
                    @endif
                </div>
                <button type="button" @click="showMatchedModal = false"
                    class="text-gray-400 transition hover:text-gray-700 dark:hover:text-gray-200">×</button>
            </div>

            @if ($selectedProduct)
                <div class="mt-6 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="space-y-4 rounded-3xl bg-slate-950 p-5 text-white">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-sm text-slate-300">Code</p>
                            <p class="text-xl font-semibold">{{ $selectedProduct->code }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-sm text-slate-300">Name</p>
                            <p class="text-lg font-semibold">{{ $selectedProduct->name }}</p>
                            <p class="mt-1 text-sm text-slate-300">{{ $selectedProduct->category?->name }} /
                                {{ $selectedProduct->subCategory?->name }}</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-sm text-slate-300">Location</p>
                                <p class="font-semibold">{{ $selectedProduct->location?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-sm text-slate-300">Type</p>
                                <p class="font-semibold">{{ $selectedProduct->productType?->name }}</p>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-sm text-slate-300">Dynamic values</p>
                            <div class="mt-3 space-y-2 text-sm">
                                @forelse ($selectedProduct->attributeValues as $attributeValue)
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-slate-300">{{ $attributeValue->field_name }}</span>
                                        <span class="font-medium">{{ $attributeValue->value }}</span>
                                    </div>
                                @empty
                                    <p class="text-slate-300">No dynamic values stored.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Comparison form</h4>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            @forelse ($scanConfigFields as $fieldConfig)
                                @php
                                    $fieldName = $fieldConfig['field'] ?? '';
                                    $fieldSource = $fieldConfig['source'] ?? 'product';
                                    $fieldLabel = $fieldConfig['field_name'] ?? ($fieldConfig['field'] ?? 'Field');
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
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $fieldLabel }}
                                            </p>
                                            <p class="text-xs uppercase tracking-[0.25em] text-gray-400">
                                                {{ $fieldSource }}</p>
                                        </div>
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
                                            <span class="mt-1 block break-words">{{ $expectedValue ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Actual</label>
                                            <input wire:model.live="actualValues.{{ $fieldName }}" type="text"
                                                class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            @error("actualValues.{$fieldName}")
                                                <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">
                                                    {{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Pick a scan config to generate the
                                    comparison form.</p>
                            @endforelse
                        </div>

                        @php
                            $uploadedAttachments = array_merge($attachments ?? [], $cameraAttachments ?? []);
                        @endphp

                        <div class="mt-6 space-y-4">
                            <div class="flex flex-wrap gap-5">
                                <input x-ref="photoInput" type="file" wire:model="attachments" multiple
                                    accept="image/*" class="hidden">
                                <input x-ref="cameraInput" type="file" wire:model="cameraAttachments" multiple
                                    accept="image/*" capture="environment" class="hidden">

                                <button type="button" @click="$refs.photoInput.click()" {{-- class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" --}}>
                                    <svg class="w-8 h-8" viewBox="0 0 48 48" version="1"
                                        xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 48 48">
                                        <path fill="#E65100"
                                            d="M41,42H13c-2.2,0-4-1.8-4-4V18c0-2.2,1.8-4,4-4h28c2.2,0,4,1.8,4,4v20C45,40.2,43.2,42,41,42z" />
                                        <path fill="#F57C00"
                                            d="M35,36H7c-2.2,0-4-1.8-4-4V12c0-2.2,1.8-4,4-4h28c2.2,0,4,1.8,4,4v20C39,34.2,37.2,36,35,36z" />
                                        <circle fill="#FFF9C4" cx="30" cy="16" r="3" />
                                        <polygon fill="#942A09" points="17,17.9 8,31 26,31" />
                                        <polygon fill="#BF360C" points="28,23.5 22,31 34,31" />
                                    </svg>
                                </button>
                                <button type="button" @click="$refs.cameraInput.click()">
                                    <svg class="w-8 h-8" viewBox="0 0 1024 1024" class="icon" version="1.1"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M732.1 399.3C534.6 356 696.5 82.1 425.9 104.8s-527.2 645.8-46.8 791.7 728-415 353-497.2z"
                                            fill="#464BD8" />
                                        <path
                                            d="M695.5 779.5c-5.7 0-11.3-0.8-16.9-2.3l-402-112.4c-16.1-4.5-29.4-15-37.6-29.5a62 62 0 0 1-5.7-47.5l65-232.6a62.64 62.64 0 0 1 60.1-45.4c5.7 0 11.3 0.8 16.9 2.3L508 349.2c0.3 0.1 0.6 0.1 1 0.1 1.2 0 2.3-0.6 2.9-1.6l21.3-31.5c7.5-11.1 20-17.7 33.4-17.7 3.7 0 7.4 0.5 11 1.5L695 332.9a40.03 40.03 0 0 1 29.5 36.8l1.9 37.6c0.1 1.6 1.1 2.9 2.6 3.3l48.2 13.5c16.1 4.5 29.4 15 37.6 29.5a62 62 0 0 1 5.7 47.5l-65 232.6c-7.4 27-32.1 45.8-60 45.8z"
                                            fill="#FFFFFF" />
                                        <path
                                            d="M566.5 306c3 0 6 0.4 9 1.3L693 340.1a32.87 32.87 0 0 1 24.1 30l1.9 37.7a11 11 0 0 0 8 10.1l48.2 13.5a55.03 55.03 0 0 1 38.2 67.8l-65.1 232.6a55.06 55.06 0 0 1-53 40.2c-4.9 0-9.9-0.7-14.9-2l-402-112.4a55.03 55.03 0 0 1-38.2-67.8l65.1-232.6c6.9-24.2 28.9-40 52.9-40 4.9 0 9.9 0.7 14.9 2l132.7 37.1c1 0.3 2 0.4 3 0.4 3.6 0 7.1-1.8 9.1-4.9l21.3-31.4c6.3-9.2 16.5-14.4 27.3-14.4m0-14.9c-15.9 0-30.6 7.8-39.5 21l-19.7 29.2L377.4 305c-6.2-1.7-12.5-2.6-18.9-2.6a70.4 70.4 0 0 0-41.8 13.9c-12.4 9.2-21.2 22-25.5 36.9v0.1l-65.1 232.6c-10.4 37.1 11.4 75.8 48.5 86.2l402 112.4c6.2 1.7 12.5 2.6 18.9 2.6 31.2 0 58.9-21 67.3-51.1l65-232.6c5-18 2.8-36.9-6.4-53.1-9.2-16.3-24.1-28-42.1-33l-45.5-12.7-1.8-34.9a47.6 47.6 0 0 0-35-43.6l-117.5-32.9a39.3 39.3 0 0 0-13-2.1z"
                                            fill="#151B28" />
                                        <path
                                            d="M686 365.2c2.9 0.8 4.9 3.3 5 6.3l1.9 37.6c0.4 16.2 11.3 30.2 26.9 34.6l48.2 13.5a28.98 28.98 0 0 1 20.1 35.7l-65 232.6c-4.6 15-20.4 23.6-35.5 19.3l-402-112.4a28.98 28.98 0 0 1-20.1-35.7l65.1-232.6a28.87 28.87 0 0 1 35.6-19.8l132.7 37.1c15.6 4.3 32.2-2 40.9-15.6l21-30.7c1.6-2.5 4.7-3.6 7.5-2.8L686 365.2"
                                            fill="#2AEFC8" />
                                        <path
                                            d="M597.6 454.5c56.2 15.7 89 74 73.3 130.2-15.7 56.2-74 89-130.2 73.3-56.2-15.7-89-74-73.3-130.2 15.9-56.1 74-88.8 130.2-73.3m7-25.1c-70.1-19.6-142.8 21.3-162.3 91.4-19.6 70.1 21.3 142.8 91.4 162.3 70.1 19.6 142.8-21.3 162.3-91.4 19.5-70-21.4-142.6-91.4-162.3z m0 0"
                                            fill="" />
                                        <path
                                            d="M580.1 513.2a50.39 50.39 0 0 1-27 97.1 50.44 50.44 0 0 1-35.2-61.9 50.5 50.5 0 0 1 62.2-35.2"
                                            fill="#514DDF" />
                                        <path
                                            d="M568.1 635.9c-28.9 0-55.7-15.6-70-41.4a79.69 79.69 0 0 1 7.6-88.8c20.4-25.4 53.8-36 85-26.8 42 12.3 66.5 56.6 54.6 98.7-8.9 31.4-35.5 54-67.9 57.8-3.1 0.3-6.2 0.5-9.3 0.5z m0-136c-16.7 0-32.7 7.5-43.5 21a55.6 55.6 0 0 0-5.3 61.9c11 19.9 32.7 31.1 55.3 28.5a55.45 55.45 0 0 0 47.3-40.3c8.3-29.4-8.8-60.2-38-68.8-5.2-1.6-10.5-2.3-15.8-2.3zM441.2 310.6L391 296.5c-6.9-1.9-11-9.1-9-16.1 1.9-6.9 9.1-11 16.1-9l50.2 14.1c6.9 1.9 11 9.1 9 16.1-1.9 6.9-9.1 10.9-16.1 9z m0 0M413.5 409.8l-50.2-14.1c-6.9-1.9-11-9.1-9-16.1 1.9-6.9 9.1-11 16.1-9.1l50.2 14.1c6.9 1.9 11 9.1 9 16.1-2 7-9.2 11.1-16.1 9.1z m0 0"
                                            fill="" />
                                    </svg>
                                </button>
                                {{-- <button type="button" 
                                    class="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-400">
                                    Upload camera photo
                                </button> --}}
                                <button type="button" wire:click="save"
                                    class="rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-400">
                                    Save check
                                </button>
                            </div>

                            <div class="rounded-2xl border border-dashed border-gray-200 p-4 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Attachment preview</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    @forelse ($uploadedAttachments as $attachment)
                                        <div
                                            class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                                            @if (str_starts_with((string) ($attachment->getMimeType() ?? ''), 'image/'))
                                                <img src="{{ $attachment->temporaryUrl() }}" alt="Preview"
                                                    class="h-40 w-full object-cover">
                                            @else
                                                <div
                                                    class="flex h-40 items-center justify-center bg-gray-50 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                                    {{ $attachment->getClientOriginalName() }}
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Uploaded files will appear
                                            here.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-6 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="rounded-3xl bg-slate-950 p-6 text-white">
                        <p class="text-sm text-slate-300">No product matched yet.</p>
                        <p class="mt-2 text-lg font-semibold">Upload a photo for manual review, then save it as a
                            caution item.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-5">
                            <input x-ref="unmatchedPhotoInput" type="file" wire:model="attachments" multiple
                                accept="image/*" class="hidden">
                            <input x-ref="unmatchedCameraInput" type="file" wire:model="cameraAttachments"
                                multiple accept="image/*" capture="environment" class="hidden">
                            <button type="button" @click="$refs.unmatchedPhotoInput.click()">
                                <div>
                                    <svg class="w-6 h-6" viewBox="0 0 48 48" version="1"
                                        xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 48 48">
                                        <path fill="#e0e0e0"
                                            d="M41,42H13c-2.2,0-4-1.8-4-4V18c0-2.2,1.8-4,4-4h28c2.2,0,4,1.8,4,4v20C45,40.2,43.2,42,41,42z" />
                                        <path fill="#c7c7c7"
                                            d="M35,36H7c-2.2,0-4-1.8-4-4V12c0-2.2,1.8-4,4-4h28c2.2,0,4,1.8,4,4v20C39,34.2,37.2,36,35,36z" />
                                        <circle fill="#FFF9C4" cx="30" cy="16" r="3" />
                                        <polygon fill="#942A09" points="17,17.9 8,31 26,31" />
                                        <polygon fill="#BF360C" points="28,23.5 22,31 34,31" />
                                    </svg>
                                </div>
                            </button>

                            <button type="button" @click="$refs.unmatchedCameraInput.click()">
                                <div>
                                    <svg class="w-8 h-8" viewBox="0 0 1024 1024" class="icon" version="1.1"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M732.1 399.3C534.6 356 696.5 82.1 425.9 104.8s-527.2 645.8-46.8 791.7 728-415 353-497.2z"
                                            fill="#464BD8" />
                                        <path
                                            d="M695.5 779.5c-5.7 0-11.3-0.8-16.9-2.3l-402-112.4c-16.1-4.5-29.4-15-37.6-29.5a62 62 0 0 1-5.7-47.5l65-232.6a62.64 62.64 0 0 1 60.1-45.4c5.7 0 11.3 0.8 16.9 2.3L508 349.2c0.3 0.1 0.6 0.1 1 0.1 1.2 0 2.3-0.6 2.9-1.6l21.3-31.5c7.5-11.1 20-17.7 33.4-17.7 3.7 0 7.4 0.5 11 1.5L695 332.9a40.03 40.03 0 0 1 29.5 36.8l1.9 37.6c0.1 1.6 1.1 2.9 2.6 3.3l48.2 13.5c16.1 4.5 29.4 15 37.6 29.5a62 62 0 0 1 5.7 47.5l-65 232.6c-7.4 27-32.1 45.8-60 45.8z"
                                            fill="#FFFFFF" />
                                        <path
                                            d="M566.5 306c3 0 6 0.4 9 1.3L693 340.1a32.87 32.87 0 0 1 24.1 30l1.9 37.7a11 11 0 0 0 8 10.1l48.2 13.5a55.03 55.03 0 0 1 38.2 67.8l-65.1 232.6a55.06 55.06 0 0 1-53 40.2c-4.9 0-9.9-0.7-14.9-2l-402-112.4a55.03 55.03 0 0 1-38.2-67.8l65.1-232.6c6.9-24.2 28.9-40 52.9-40 4.9 0 9.9 0.7 14.9 2l132.7 37.1c1 0.3 2 0.4 3 0.4 3.6 0 7.1-1.8 9.1-4.9l21.3-31.4c6.3-9.2 16.5-14.4 27.3-14.4m0-14.9c-15.9 0-30.6 7.8-39.5 21l-19.7 29.2L377.4 305c-6.2-1.7-12.5-2.6-18.9-2.6a70.4 70.4 0 0 0-41.8 13.9c-12.4 9.2-21.2 22-25.5 36.9v0.1l-65.1 232.6c-10.4 37.1 11.4 75.8 48.5 86.2l402 112.4c6.2 1.7 12.5 2.6 18.9 2.6 31.2 0 58.9-21 67.3-51.1l65-232.6c5-18 2.8-36.9-6.4-53.1-9.2-16.3-24.1-28-42.1-33l-45.5-12.7-1.8-34.9a47.6 47.6 0 0 0-35-43.6l-117.5-32.9a39.3 39.3 0 0 0-13-2.1z"
                                            fill="#151B28" />
                                        <path
                                            d="M686 365.2c2.9 0.8 4.9 3.3 5 6.3l1.9 37.6c0.4 16.2 11.3 30.2 26.9 34.6l48.2 13.5a28.98 28.98 0 0 1 20.1 35.7l-65 232.6c-4.6 15-20.4 23.6-35.5 19.3l-402-112.4a28.98 28.98 0 0 1-20.1-35.7l65.1-232.6a28.87 28.87 0 0 1 35.6-19.8l132.7 37.1c15.6 4.3 32.2-2 40.9-15.6l21-30.7c1.6-2.5 4.7-3.6 7.5-2.8L686 365.2"
                                            fill="#2AEFC8" />
                                        <path
                                            d="M597.6 454.5c56.2 15.7 89 74 73.3 130.2-15.7 56.2-74 89-130.2 73.3-56.2-15.7-89-74-73.3-130.2 15.9-56.1 74-88.8 130.2-73.3m7-25.1c-70.1-19.6-142.8 21.3-162.3 91.4-19.6 70.1 21.3 142.8 91.4 162.3 70.1 19.6 142.8-21.3 162.3-91.4 19.5-70-21.4-142.6-91.4-162.3z m0 0"
                                            fill="" />
                                        <path
                                            d="M580.1 513.2a50.39 50.39 0 0 1-27 97.1 50.44 50.44 0 0 1-35.2-61.9 50.5 50.5 0 0 1 62.2-35.2"
                                            fill="#514DDF" />
                                        <path
                                            d="M568.1 635.9c-28.9 0-55.7-15.6-70-41.4a79.69 79.69 0 0 1 7.6-88.8c20.4-25.4 53.8-36 85-26.8 42 12.3 66.5 56.6 54.6 98.7-8.9 31.4-35.5 54-67.9 57.8-3.1 0.3-6.2 0.5-9.3 0.5z m0-136c-16.7 0-32.7 7.5-43.5 21a55.6 55.6 0 0 0-5.3 61.9c11 19.9 32.7 31.1 55.3 28.5a55.45 55.45 0 0 0 47.3-40.3c8.3-29.4-8.8-60.2-38-68.8-5.2-1.6-10.5-2.3-15.8-2.3zM441.2 310.6L391 296.5c-6.9-1.9-11-9.1-9-16.1 1.9-6.9 9.1-11 16.1-9l50.2 14.1c6.9 1.9 11 9.1 9 16.1-1.9 6.9-9.1 10.9-16.1 9z m0 0M413.5 409.8l-50.2-14.1c-6.9-1.9-11-9.1-9-16.1 1.9-6.9 9.1-11 16.1-9.1l50.2 14.1c6.9 1.9 11 9.1 9 16.1-2 7-9.2 11.1-16.1 9.1z m0 0"
                                            fill="" />
                                    </svg>
                                </div>
                            </button>
                            <button type="button" wire:click="save"
                                class="rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-400">
                                Save check
                            </button>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Upload preview</h4>
                        @php
                            $uploadedAttachments = array_merge($attachments ?? [], $cameraAttachments ?? []);
                        @endphp

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @forelse ($uploadedAttachments as $attachment)
                                <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                                    @if (str_starts_with((string) ($attachment->getMimeType() ?? ''), 'image/'))
                                        <img src="{{ $attachment->temporaryUrl() }}" alt="Preview"
                                            class="h-40 w-full object-cover">
                                    @else
                                        <div
                                            class="flex h-40 items-center justify-center bg-gray-50 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $attachment->getClientOriginalName() }}
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Uploaded files will appear here.
                                </p>
                            @endforelse
                        </div>

                        <div class="mt-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">This saves as a warning review when no
                                product match is found.</p>
                        </div>
                    </div>
                </div>
            @endif
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
                            @this.call('matchProductFromCode');
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
