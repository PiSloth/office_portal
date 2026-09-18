<x-filament-panels::page>
    @php
        $reportData = $this->getReportData();
        $table1 = $reportData['table1'];
        $table2 = $reportData['table2'];
        $totalWrongFieldCount = $reportData['totalWrongFieldCount'];
        $totalDistinctRepurchases = $reportData['totalDistinctRepurchases'];
        $totalOpen = $reportData['totalOpenDecisionsCount'];
        $startDateText = $reportData['formattedStartDate'];
        $endDateText = $reportData['formattedEndDate'];
    @endphp

    <style>
        /* Myanmar Typography & Line-Height Standards */
        .report-root {
            font-family: 'Pyidaungsu', 'Padauk', 'Noto Sans Myanmar', 'Myanmar3', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.85;
            color: #1f2937;
        }

        .mm-font {
            font-family: 'Pyidaungsu', 'Padauk', 'Noto Sans Myanmar', 'Myanmar3', system-ui, sans-serif;
            line-height: 1.9 !important;
        }

        /* SVG Icon Fix */
        .export-btn svg, .print-btn svg, .report-icon {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
            max-width: 18px !important;
            max-height: 18px !important;
            vertical-align: middle;
            flex-shrink: 0;
        }

        /* Toolbar Controls Bar */
        .report-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
        }

        .dark .report-toolbar {
            background: #18181b;
            border-color: #27272a;
        }

        .toolbar-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .date-control {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .control-label {
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
        }

        .dark .control-label {
            color: #9ca3af;
        }

        .date-input {
            font-size: 13px;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            outline: none;
            transition: border-color 0.15s;
        }

        .dark .date-input {
            background: #27272a;
            border-color: #3f3f46;
            color: #f4f4f5;
        }

        .date-input:focus {
            border-color: #d97706;
        }

        .preset-group {
            display: flex;
            align-items: center;
            gap: 6px;
            padding-left: 12px;
            border-left: 1px solid #e5e7eb;
        }

        .dark .preset-group {
            border-color: #374151;
        }

        .preset-btn {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .dark .preset-btn {
            background: #27272a;
            border-color: #3f3f46;
            color: #d4d4d8;
        }

        .preset-btn:hover {
            background: #e5e7eb;
            color: #111827;
        }

        .dark .preset-btn:hover {
            background: #3f3f46;
            color: #ffffff;
        }

        .export-btn, .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: #d97706;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(217, 119, 6, 0.3);
            transition: all 0.15s ease;
        }

        .export-btn:hover, .print-btn:hover {
            background: #b45309;
        }

        .export-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        /* Document Paper Canvas (A4 Look) */
        .document-paper {
            background: #ffffff;
            color: #111827;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 44px 48px;
            max-width: 960px;
            margin: 0 auto;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            line-height: 1.85;
        }

        /* Header Layout */
        .doc-header {
            border-bottom: 2px solid #111827;
            padding-bottom: 20px;
            margin-bottom: 28px;
            text-align: center;
        }

        .doc-pretitle {
            font-size: 11px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .doc-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #111827;
            margin: 4px 0 8px 0;
            line-height: 1.4;
        }

        .doc-subtitle-badge {
            display: inline-block;
            padding: 4px 16px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-top: 6px;
            line-height: 1.8;
        }

        .doc-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #6b7280;
            margin-top: 16px;
            padding: 0 4px;
            line-height: 1.8;
        }

        /* Section Headings */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            margin-top: 24px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.8;
        }

        .section-bullet {
            width: 8px;
            height: 8px;
            background-color: #d97706;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .section-info {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.8;
        }

        /* Solid Crisp Tables */
        .solid-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #111827;
            margin-top: 6px;
            margin-bottom: 24px;
            font-size: 13px;
            line-height: 1.9;
        }

        .solid-table th {
            background-color: #f3f4f6;
            border: 1px solid #374151;
            padding: 10px 14px;
            font-weight: 700;
            color: #111827;
            text-align: left;
            line-height: 1.8;
        }

        .solid-table td {
            border: 1px solid #4b5563;
            padding: 9px 14px;
            color: #1f2937;
            line-height: 1.9;
            vertical-align: middle;
        }

        .solid-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .solid-table tfoot td {
            background-color: #f3f4f6;
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            font-weight: 700;
            color: #111827;
            line-height: 1.8;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .branch-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            line-height: 1.8;
        }

        .branch-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            background: #f3f4f6;
            color: #1f2937;
            border: 1px solid #d1d5db;
            line-height: 1.5;
        }

        /* Sign-off Section */
        .sign-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 40px;
            padding-top: 32px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.8;
        }

        .sign-line {
            border-bottom: 1px solid #9ca3af;
            width: 75%;
            margin: 44px auto 8px auto;
        }

        /* Print Specific Rules */
        @media print {
            * {
                color-scheme: only light !important;
                forced-color-adjust: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print, nav, header, aside, .fi-topbar, .fi-sidebar, .fi-header, .fi-breadcrumbs {
                display: none !important;
            }

            :root, html, html.dark, body, body.dark, 
            .fi-layout, .fi-main, .fi-page, .fi-page-header-main-ctn, .fi-page-main, .fi-page-content,
            .report-root, .document-paper {
                color-scheme: only light !important;
                background: transparent !important;
                background-color: transparent !important;
                color: #000000 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .document-paper {
                background: transparent !important;
                background-color: transparent !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                color: #000000 !important;
            }

            .doc-header {
                border-bottom: 2px solid #000000 !important;
            }

            .doc-title {
                color: #000000 !important;
            }

            .doc-subtitle-badge {
                background-color: #f3f4f6 !important;
                border: 1px solid #9ca3af !important;
                color: #000000 !important;
            }

            .section-title {
                color: #000000 !important;
            }

            .section-bullet {
                background-color: #000000 !important;
            }

            .solid-table {
                border-collapse: collapse !important;
                width: 100% !important;
                border: 2px solid #000000 !important;
                page-break-inside: auto !important;
                background-color: transparent !important;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            th, td {
                border: 1px solid #000000 !important;
                color: #000000 !important;
            }

            .solid-table th {
                background-color: #f3f4f6 !important;
                border: 1px solid #000000 !important;
                color: #000000 !important;
                font-weight: 700 !important;
            }

            .solid-table td {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                background-color: transparent !important;
            }

            .solid-table tbody tr:nth-child(even) td {
                background-color: #f9fafb !important;
            }

            .solid-table tfoot td {
                background-color: #f3f4f6 !important;
                border-top: 2px solid #000000 !important;
                border-bottom: 2px solid #000000 !important;
                color: #000000 !important;
                font-weight: 700 !important;
            }

            .branch-badge {
                background-color: #f3f4f6 !important;
                border: 1px solid #9ca3af !important;
                color: #000000 !important;
            }

            .sign-grid {
                border-top: 1px solid #9ca3af !important;
                color: #000000 !important;
            }

            .sign-line {
                border-bottom: 1px solid #000000 !important;
            }
        }
    </style>

    <div class="report-root">
        {{-- Controls Bar (Hidden in Print) --}}
        <div class="no-print report-toolbar">
            <div class="toolbar-group">
                <div class="date-control">
                    <label class="control-label">From Date:</label>
                    <input 
                        type="date" 
                        wire:model.live="startDate"
                        class="date-input"
                    />
                </div>

                <div class="date-control">
                    <label class="control-label">To Date:</label>
                    <input 
                        type="date" 
                        wire:model.live="endDate"
                        class="date-input"
                    />
                </div>

                <div class="preset-group">
                    <button 
                        type="button" 
                        wire:click="setPreset('this_month')"
                        class="preset-btn"
                    >
                        This Month
                    </button>
                    <button 
                        type="button" 
                        wire:click="setPreset('last_month')"
                        class="preset-btn"
                    >
                        Last Month
                    </button>
                    <button 
                        type="button" 
                        wire:click="setPreset('this_year')"
                        class="preset-btn"
                    >
                        This Year
                    </button>
                    <button 
                        type="button" 
                        wire:click="setPreset('all')"
                        class="preset-btn"
                    >
                        All Time
                    </button>
                </div>
            </div>

            <div>
                <button 
                    type="button" 
                    wire:click="exportPdf"
                    wire:loading.attr="disabled"
                    class="export-btn"
                >
                    <span wire:loading.remove wire:target="exportPdf" style="display: inline-flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="report-icon">
                            <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
                            <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                        </svg>
                        <span>Export PDF</span>
                    </span>

                    <span wire:loading wire:target="exportPdf" style="display: none; align-items: center; gap: 8px;">
                        <svg class="report-icon animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity: 0.25;"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" style="opacity: 0.75;"></path>
                        </svg>
                        <span>Generating PDF...</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- Formatted Document Canvas (A4 Document Style) --}}
        <div class="document-paper">
            
            {{-- Document Header --}}
            <div class="doc-header">
                <div class="doc-pretitle">
                    {{ $reportData['companyTitle'] ?? 'MAHAR JEWELRY & GOLD REPURCHASE' }}
                </div>
                <h1 class="doc-title">
                    Repurchse Performance Analysis
                </h1>
                <div class="doc-subtitle-badge mm-font">
                    The report at within {{ $startDateText }} and {{ $endDateText }}
                </div>

                <div class="doc-meta mm-font">
                    <div>
                        <strong style="color: #374151;">ထုတ်ယူသည့်ရက်စွဲ:</strong> 
                        {{ now()->format('d M Y, h:i A') }}
                    </div>
                    <div>
                        <strong style="color: #374151;">ထုတ်ယူသူ:</strong> 
                        {{ auth()->user()?->name ?? 'System' }}
                    </div>
                </div>
            </div>

            {{-- 1. Summary Report Table --}}
            <div style="margin-bottom: 30px;">
                <div class="section-header">
                    <div class="section-title mm-font">
                        <span class="section-bullet"></span>
                        ၁။ စစ်ဆေးတွေ့ရှိချက် အလိုက် ကြိမ်နှုန်း အနှစ်ချုပ် (Failure Field Summary)
                    </div>
                    <div class="section-info mm-font">
                        စုစုပေါင်း အချက်အလက်: <strong>{{ count($table1) }}</strong> ခု
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="solid-table mm-font">
                        <thead>
                            <tr>
                                <th style="width: 40%;">
                                    စစ်ဆေးတွေ့ရှိချက်
                                </th>
                                <th class="text-center" style="width: 20%;">
                                    မှားယွင်းသည့် ကြိမ်နှုန်း
                                </th>
                                <th class="text-center" style="width: 20%;">
                                    Repurchase ID (သီးခြား)
                                </th>
                                <th class="text-center" style="width: 20%;">
                                    မှတ်ချက်(ဖြေရှင်းရန် ကျန်)
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($table1 as $row)
                                <tr>
                                    <td style="font-weight: 600;">
                                        {{ $row['label'] }}
                                    </td>
                                    <td class="text-center" style="font-weight: 700;">
                                        {{ number_format($row['wrong_field_count']) }}
                                    </td>
                                    <td class="text-center" style="font-weight: 700; color: #0369a1;">
                                        {{ number_format($row['distinct_repurchase_count']) }}
                                    </td>
                                    <td class="text-center" style="font-weight: 700; {{ $row['open_count'] > 0 ? 'color: #b45309; background-color: #fffbeb;' : 'color: #047857;' }}">
                                        {{ number_format($row['open_count']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center" style="padding: 28px; font-style: italic; color: #6b7280;">
                                        ရွေးချယ်ထားသော ရက်စွဲအတွင်း စစ်ဆေးတွေ့ရှိချက် မှတ်တမ်း မရှိပါ။ (No records found for the selected date range)
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($table1) > 0)
                            <tfoot>
                                <tr>
                                    <td class="text-right" style="padding-right: 20px;">
                                        စုစုပေါင်း (Total) :
                                    </td>
                                    <td class="text-center" style="font-size: 15px;">
                                        {{ number_format($reportData['totalWrongFieldCount']) }}
                                    </td>
                                    <td class="text-center" style="font-size: 15px; color: #0369a1;">
                                        {{ number_format($reportData['totalDistinctRepurchases']) }}
                                    </td>
                                    <td class="text-center" style="font-size: 15px; color: #9a3412;">
                                        {{ number_format($reportData['totalOpenDecisionsCount']) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- 2. Breakdown Report Table Grouped By Branch --}}
            <div style="padding-top: 10px; border-top: 1px dashed #d1d5db;">
                <div class="section-header">
                    <div class="section-title mm-font">
                        <span class="section-bullet"></span>
                        ၂။ စစ်ဆေးတွေ့ရှိချက် နှင့် ဌာနခွဲအလိုက် ကြိမ်နှုန်း အသေးစိတ် (Breakdown by Branch)
                    </div>
                    <div class="section-info mm-font" style="font-style: italic;">
                        *အများဆုံး ကြိမ်နှုန်းမှ အနည်းဆုံးသို့ အစီအစဉ်တကျ ပြသထားပါသည်
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="solid-table mm-font">
                        <thead>
                            <tr>
                                <th style="width: 45%;">
                                    စစ်ဆေးတွေ့ရှိချက်
                                </th>
                                <th style="width: 55%;">
                                    Branch အလိုက် ကြိမ်နှုန်း
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($table2 as $group)
                                @php
                                    $branches = $group['branches'];
                                    $branchCount = count($branches);
                                    $isFirst = true;
                                @endphp

                                @if($branchCount > 0)
                                    @foreach($branches as $branchName => $bData)
                                        <tr>
                                            @if($isFirst)
                                                <td 
                                                    rowspan="{{ $branchCount }}" 
                                                    style="vertical-align: top; background-color: #f9fafb; font-weight: 700; padding: 12px 14px;"
                                                >
                                                    <div style="font-size: 14px; font-weight: 700; color: #111827;">
                                                        {{ $group['label'] }}
                                                    </div>
                                                    <div style="font-size: 12px; font-weight: normal; color: #4b5563; margin-top: 5px; line-height: 1.6;">
                                                        စုစုပေါင်း မှားယွင်းမှု: <strong style="color: #111827;">{{ number_format($group['total_wrong_count']) }}</strong> ကြိမ်<br>
                                                        သီးခြား Repurchase: <strong style="color: #0369a1;">{{ number_format($group['distinct_repurchase_count']) }}</strong> ခု
                                                    </div>
                                                </td>
                                                @php $isFirst = false; @endphp
                                            @endif

                                            <td>
                                                <div class="branch-item">
                                                    <span style="font-weight: 600; color: #374151;">{{ $branchName }}</span>
                                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                        <span class="branch-badge" title="Wrong Field Occurrences">
                                                            {{ number_format($bData['wrong_count']) }} ကြိမ်
                                                        </span>
                                                        <span class="branch-badge" style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;" title="Distinct Repurchases">
                                                            {{ number_format($bData['distinct_repurchase_count']) }} Repurchases
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td style="font-weight: 700;">
                                            {{ $group['label'] }}
                                        </td>
                                        <td style="font-style: italic; color: #6b7280;">
                                            မှတ်တမ်း မရှိပါ
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center" style="padding: 28px; font-style: italic; color: #6b7280;">
                                        ရွေးချယ်ထားသော ရက်စွဲအတွင်း ဌာနခွဲအလိုက် စစ်ဆေးတွေ့ရှိချက် မှတ်တမ်း မရှိပါ။ (No branch breakdown records found)
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Document Sign-off Section (Official Document Finish) --}}
            <div class="sign-grid mm-font">
                <div>
                    <strong style="color: #1f2937;">အစီရင်ခံစာ ပြုစုသူ</strong>
                    <div class="sign-line"></div>
                    <div>( .................................................... )</div>
                </div>
                <div>
                    <strong style="color: #1f2937;">စိစစ်သူ / ဌာနမှူး</strong>
                    <div class="sign-line"></div>
                    <div>( .................................................... )</div>
                </div>
                <div>
                    <strong style="color: #1f2937;">အတည်ပြုသူ / စီမံခန့်ခွဲမှု</strong>
                    <div class="sign-line"></div>
                    <div>( .................................................... )</div>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
