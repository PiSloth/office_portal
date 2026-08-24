@if ($record && $record->purchaseRequest)
    @php
        $pr = $record->purchaseRequest;
        $items = $pr->items;
        $reChangeLabels = [
            '0' => 'ဆိုင်ထည်',
            '1' => 'အလဲအထပ်လုပ်မယ် (Yes)',
            '2' => 'Percent ထည်ပြန်ဝယ်',
            '3' => 'စိန်ထည်ပြန်ဝယ်',
            '4' => 'အကျစ်ထည်ပြန်ဝယ်',
            '5' => 'အထည်ပြန်လဲ',
        ];
    @endphp

    <div class="space-y-4 w-full">
        <!-- Customer & Request Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-sm">
            <div>
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block">Customer Name</span>
                <strong class="text-base text-gray-900 dark:text-white">{{ $pr->customer_name ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block">Phone</span>
                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $pr->customer_phone ?? '-' }}</span>
            </div>
            <div>
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block">Branch</span>
                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $pr->branch?->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block">Total Est. Cost</span>
                <strong class="text-base text-amber-600 dark:text-amber-400">{{ number_format($pr->total_amount ?? 0) }} MMK</strong>
            </div>
        </div>

        <!-- Product Items List -->
        @if ($items->isNotEmpty())
            <div class="space-y-3">
                @foreach ($items as $index => $item)
                    @php
                        $data = $item->dynamic_fields_json ?? [];
                        $productName = $data['product_name'] ?? ('Product #' . ($index + 1));
                        $qty = $data['quantity'] ?? 1;
                        $goldGrade = $data['goldList'] ?? null;
                        $weightGram = $data['goldWeightGram'] ?? 0;
                        $kyaukWeight = $data['kyaukWeight'] ?? 0;
                        $isGood = $data['is_good'] ?? false;
                        $reChange = (string)($data['reChange'] ?? '0');
                        $reChangeText = $reChangeLabels[$reChange] ?? 'ဆိုင်ထည်';
                        $origPrice = $data['original_voucher_price'] ?? null;
                        $percent = $data['percent'] ?? null;
                        $image = $data['attachment_image'] ?? null;
                        $itemRemark = $data['remark'] ?? null;
                        $calcPrice = $item->calculated_price ?? 0;
                        $kpyKyat = $data['kyat'] ?? 0;
                        $kpyPae = $data['pae'] ?? 0;
                        $kpyYawe = $data['yawe'] ?? 0;
                        $hasKpy = ((float)$kpyKyat > 0 || (float)$kpyPae > 0 || (float)$kpyYawe > 0);
                    @endphp

                    <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                        <!-- Item Header -->
                        <div class="bg-gray-50/80 dark:bg-gray-800/60 px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 text-xs font-bold">
                                    {{ $index + 1 }}
                                </span>
                                <h4 class="font-bold text-gray-900 dark:text-white text-sm sm:text-base">
                                    {{ $productName }}
                                </h4>
                                @if ($qty > 1)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        Qty: {{ $qty }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($isGood)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                        ရ (Good Condition)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                        မရ (Defect/Damage)
                                    </span>
                                @endif

                                <span class="text-sm font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-3 py-1 rounded-lg border border-amber-200 dark:border-amber-900/50">
                                    {{ number_format($calcPrice) }} MMK
                                </span>
                            </div>
                        </div>

                        <!-- Item Details Grid -->
                        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 text-xs">
                            <!-- Gold Grade -->
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">Gold Grade</span>
                                <span class="text-gray-900 dark:text-gray-100 font-bold text-sm">
                                    {{ $goldGrade ? $goldGrade . ' ပဲ' : '-' }}
                                </span>
                            </div>

                            <!-- Weight in Grams -->
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">Total Weight (Gram)</span>
                                <span class="text-gray-900 dark:text-gray-100 font-semibold text-sm">
                                    {{ $weightGram ? $weightGram . ' g' : '-' }}
                                </span>
                            </div>

                            <!-- Kyauk Weight -->
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">ကျောက်ချိန် (ရွေး)</span>
                                <span class="text-gray-900 dark:text-gray-100 font-semibold text-sm">
                                    {{ $kyaukWeight ? $kyaukWeight . ' ရွေး' : '0' }}
                                </span>
                            </div>

                            <!-- KPY Breakdown -->
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">KPY (ကျပ်/ပဲ/ရွေး)</span>
                                <span class="text-gray-900 dark:text-gray-100 font-medium">
                                    @if ($hasKpy)
                                        {{ (float)$kpyKyat }} ကျပ် {{ (float)$kpyPae }} ပဲ {{ (float)$kpyYawe }} ရွေး
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>

                            <!-- Buyback Type -->
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">ပြန်ဝယ်အမျိုးအစား</span>
                                <span class="text-teal-700 dark:text-teal-300 font-semibold">
                                    {{ $reChangeText }}
                                </span>
                            </div>

                            <!-- Original Voucher / Percent -->
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 font-medium block">Voucher Price / %</span>
                                <span class="text-gray-900 dark:text-gray-100">
                                    @if ($origPrice)
                                        {{ number_format($origPrice) }} MMK
                                        @if ($percent)
                                            ({{ $percent }}%)
                                        @endif
                                    @elseif ($percent)
                                        {{ $percent }}%
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Extra: Image & Remarks -->
                        @if ($image || $itemRemark)
                            <div class="px-4 pb-4 pt-1 flex flex-wrap items-center gap-4 border-t border-gray-100 dark:border-gray-800/80 text-xs">
                                @if ($image)
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-500 dark:text-gray-400 font-medium">Photo:</span>
                                        <a href="{{ asset('storage/' . $image) }}" target="_blank" class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 font-semibold underline hover:text-primary-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            View Image
                                        </a>
                                    </div>
                                @endif

                                @if ($itemRemark)
                                    <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400 italic">
                                        <span class="font-medium not-italic text-gray-500">Note:</span>
                                        {{ $itemRemark }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 text-center text-gray-400 dark:text-gray-500 text-sm italic">
                No product items associated with this purchase request.
            </div>
        @endif
    </div>
@endif
