<div class="py-8">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Mobile Scanner</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Scan a code, compare the item, and save the inspection record.</p>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Open sessions: {{ $scanStats['open_sessions'] }} · Active configs: {{ $scanStats['active_configs'] }}
            </div>
        </div>

        @if ($flashMessage)
            <div class="rounded-2xl border border-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-200 bg-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-50 p-4 text-sm text-gray-800 shadow-sm dark:border-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-900/40 dark:bg-{{ $flashTone === 'success' ? 'emerald' : 'amber' }}-950/40 dark:text-white">
                {{ $flashMessage }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Selection</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Check session</label>
                        <select wire:model.live="checkSessionId" class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Choose session</option>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }} ({{ $session->status }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Product type</label>
                        <select wire:model.live="productTypeId" class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Choose type</option>
                            @foreach ($productTypes as $productType)
                                <option value="{{ $productType->id }}">{{ $productType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scan config</label>
                        <select wire:model.live="scanConfigId" class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Choose config</option>
                            @foreach ($scanConfigs as $config)
                                <option value="{{ $config->id }}">{{ $config->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 rounded-3xl border border-dashed border-amber-300 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Camera scanner</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Point the device camera at a QR or barcode.</p>
                        </div>
                        <span class="text-xs uppercase tracking-[0.3em] text-amber-700 dark:text-amber-300">html5-qrcode</span>
                    </div>
                    <div id="qr-reader" class="mt-4 overflow-hidden rounded-2xl bg-white dark:bg-gray-800"></div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scanned code</label>
                        <input id="scan-code" wire:model.live="scanCode" type="text" class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="Scan or type a code">
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="button" wire:click="matchProductFromCode" class="rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                            Match product
                        </button>
                        <button type="button" wire:click="save" class="rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-400">
                            Save check
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm">
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
                            <p class="mt-1 text-sm text-slate-300">{{ $selectedProduct->category?->name }} / {{ $selectedProduct->subCategory?->name }}</p>
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
                @else
                    <div class="mt-4 rounded-2xl border border-dashed border-white/20 p-6 text-sm text-slate-300">
                        Scan a barcode or QR code to load the product details here.
                    </div>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Attachments</h3>
                <input type="file" wire:model="attachments" multiple class="mt-4 block w-full text-sm text-gray-600 file:mr-4 file:rounded-full file:border-0 file:bg-amber-500 file:px-4 file:py-2 file:text-white hover:file:bg-amber-400 dark:text-gray-300">
                @error('attachments.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Comparison form</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @forelse ($scanConfigFields as $fieldConfig)
                        @php
                            $fieldName = $fieldConfig['field'] ?? '';
                            $fieldSource = $fieldConfig['source'] ?? 'product';
                            $fieldLabel = $fieldConfig['field'] ?? 'Field';
                            $expectedValue = $selectedProduct ? match ($fieldName) {
                                'location_id', 'category_id', 'sub_category_id' => $selectedProduct->{$fieldName},
                                'code', 'barcode', 'qr_code', 'name', 'description', 'status' => $selectedProduct->{$fieldName},
                                default => $selectedProduct->attributeValues->firstWhere('field_name', $fieldName)?->value,
                            } : null;
                        @endphp
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $fieldLabel }}</p>
                                    <p class="text-xs uppercase tracking-[0.25em] text-gray-400">{{ $fieldSource }}</p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                    {{ data_get($fieldConfig, 'compare', false) ? 'Compare' : 'Note' }}
                                </span>
                            </div>
                            <div class="mt-4 grid gap-3">
                                <div class="rounded-xl bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <span class="block text-xs uppercase tracking-[0.25em] text-gray-400">Expected</span>
                                    <span class="mt-1 block break-words">{{ $expectedValue ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Actual</label>
                                    <input wire:model.live="actualValues.{{ $fieldName }}" type="text" class="w-full rounded-2xl border-gray-300 bg-white text-gray-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pick a scan config to generate the comparison form.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($lastResult)
            <div class="rounded-3xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Validation result</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Overall status: {{ $lastResult['result_status'] }}</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($lastResult['values'] as $result)
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $result['field_name'] }}</p>
                                <span class="text-xs font-semibold uppercase tracking-[0.25em] {{ $result['status'] === 'PASS' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $result['status'] }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Expected: {{ $result['expected_value'] ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Actual: {{ $result['actual_value'] ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Difference: {{ $result['difference_value'] ?? 'N/A' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            if (typeof Html5QrcodeScanner === 'undefined') {
                return;
            }

            const readerId = 'qr-reader';
            const container = document.getElementById(readerId);

            if (!container) {
                return;
            }

            const scanner = new Html5QrcodeScanner(readerId, {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                rememberLastUsedCamera: true,
            }, false);

            scanner.render((decodedText) => {
                const input = document.getElementById('scan-code');
                if (!input) {
                    return;
                }

                input.value = decodedText;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    </script>
</div>
