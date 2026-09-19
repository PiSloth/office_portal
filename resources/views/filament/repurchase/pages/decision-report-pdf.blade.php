<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Repurchase Performance Analysis</title>
    <style>
        @page {
            size: A4 portrait;
            margin-top: 15mm;
            margin-bottom: 15mm;
            margin-left: 12mm;
            margin-right: 12mm;
        }

        @font-face {
            font-family: 'Zawgyi-One';
            src: url('{{ str_replace('\\', '/', storage_path("fonts/Zawgyi-One.ttf")) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Zawgyi-One';
            src: url('{{ str_replace('\\', '/', storage_path("fonts/Zawgyi-One.ttf")) }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Zawgyi-One';
            src: url('{{ str_replace('\\', '/', storage_path("fonts/Zawgyi-One.ttf")) }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        @font-face {
            font-family: 'Zawgyi-One';
            src: url('{{ str_replace('\\', '/', storage_path("fonts/Zawgyi-One.ttf")) }}') format('truetype');
            font-weight: bold;
            font-style: italic;
        }

        * {
            font-family: 'Zawgyi-One' !important;
        }

        body {
            font-family: 'Zawgyi-One', sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /* Document Header */
        .doc-header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 16px;
            text-align: center;
        }

        .doc-pretitle {
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 4px;
        }

        .doc-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
            margin: 4px 0 6px 0;
        }

        .doc-subtitle-badge {
            display: inline-block;
            padding: 3px 12px;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            margin-top: 4px;
        }

        .meta-table {
            width: 100%;
            margin-top: 10px;
            font-size: 9px;
            color: #4b5563;
        }

        /* Section Titles */
        .section-header-table {
            width: 100%;
            margin-top: 16px;
            margin-bottom: 6px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }

        .section-info {
            font-size: 9px;
            color: #4b5563;
            text-align: right;
        }

        /* Solid Tables */
        .solid-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #111827;
            margin-top: 4px;
            margin-bottom: 16px;
            font-size: 10px;
        }

        .solid-table th {
            background-color: #f3f4f6;
            border: 1px solid #374151;
            padding: 6px 8px;
            font-weight: bold;
            color: #111827;
            text-align: left;
        }

        .solid-table td {
            border: 1px solid #4b5563;
            padding: 5px 8px;
            color: #111827;
            vertical-align: middle;
        }

        .solid-table tbody tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .solid-table tfoot td {
            background-color: #f3f4f6;
            border-top: 1.5px solid #111827;
            border-bottom: 1.5px solid #111827;
            font-weight: bold;
            color: #111827;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .branch-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            background-color: #f3f4f6;
            color: #111827;
            border: 1px solid #d1d5db;
        }

        /* Sign-off Section */
        .sign-table {
            width: 100%;
            margin-top: 30px;
            border-top: 1px solid #d1d5db;
            padding-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #374151;
        }

        .sign-table td {
            width: 33.33%;
            vertical-align: top;
            padding: 0 10px;
        }

        .sign-line {
            border-bottom: 1px solid #6b7280;
            width: 75%;
            margin: 35px auto 6px auto;
        }
    </style>
</head>
<body>
    @php
        $zg = function($text) {
            if (empty($text)) return '';
            return class_exists('Rabbit') ? \Rabbit::uni2zg($text) : $text;
        };
    @endphp

    {{-- Document Header --}}
    <div class="doc-header">
        <div class="doc-pretitle">
            {{ $zg($companyTitle ?? 'MAHAR JEWELRY & GOLD REPURCHASE') }}
        </div>
        <div class="doc-title">
            REPURCHASE PERFORMANCE ANALYSIS
        </div>
        <div class="doc-subtitle-badge">
            The report at within {{ $zg($startDateText) }} and {{ $zg($endDateText) }}
        </div>
        @if(!empty($stateInfo) && !empty($stateInfo['isFiltered']))
            <div style="margin-top: 4px; font-size: 9px; color: #1e40af;">
                <strong>{{ $zg('PR အခြေအနေ:') }}</strong> 
                {{ $zg($stateInfo['label']) }}
            </div>
        @endif
        @if(!empty($toleranceInfo) && !empty($toleranceInfo['isActive']))
            <div style="margin-top: 4px; font-size: 9px; color: #4b5563;">
                <strong>{{ $zg('စစ်ထုတ်မှု:') }}</strong> 
                {{ $zg($toleranceInfo['fieldLabel']) }} (Tolerance: &plusmn;{{ $toleranceInfo['value'] }}g) &mdash; 
                <strong>{{ $zg($toleranceInfo['modeLabel']) }}</strong>
            </div>
        @endif

        <table class="meta-table">
            <tr>
                <td style="text-align: left;">
                    <strong>{{ $zg('ထုတ်ယူသည့်ရက်စွဲ:') }}</strong> {{ now()->format('d M Y, h:i A') }}
                </td>
                <td style="text-align: right;">
                    <strong>{{ $zg('ထုတ်ယူသူ:') }}</strong> {{ $zg(auth()->user()?->name ?? 'System') }}
                </td>
            </tr>
        </table>
    </div>

    {{-- 1. Summary Report Table --}}
    <table class="section-header-table">
        <tr>
            <td class="section-title">
                ၁။ {{ $zg('စစ်ဆေးတွေ့ရှိချက် အလိုက် ကြိမ်နှုန်း အနှစ်ချုပ်') }} (Failure Field Summary)
            </td>
            <td class="section-info">
                {{ $zg('စုစုပေါင်း အချက်အလက်:') }} <strong>{{ count($table1) }}</strong> {{ $zg('ခု') }}
            </td>
        </tr>
    </table>

    <table class="solid-table">
        <thead>
            <tr>
                <th style="width: 50%;">
                    {{ $zg('စစ်ဆေးတွေ့ရှိချက်') }}
                </th>
                <th class="text-center" style="width: 25%;">
                    {{ $zg('မှားယွင်းကြိမ်နှုန်း') }}
                </th>
                <th class="text-center" style="width: 25%;">
                    {{ $zg('မှတ်ချက်(ဖြေရှင်းရန် ကျန်)') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($table1 as $row)
                <tr>
                    <td style="font-weight: bold;">
                        {{ $zg($row['label']) }}
                    </td>
                    <td class="text-center" style="font-weight: bold;">
                        {{ number_format($row['distinct_repurchase_count']) }}
                    </td>
                    <td class="text-center" style="font-weight: bold; {{ $row['open_count'] > 0 ? 'color: #b45309;' : 'color: #047857;' }}">
                        {{ number_format($row['open_count']) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="padding: 18px; color: #6b7280;">
                        {{ $zg('ရွေးချယ်ထားသော ရက်စွဲအတွင်း စစ်ဆေးတွေ့ရှိချက် မှတ်တမ်း မရှိပါ။') }} (No records found for the selected date range)
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($table1) > 0)
            <tfoot>
                <tr>
                    <td class="text-right" style="padding-right: 15px;">
                        {{ $zg('စုစုပေါင်း (Total) :') }}
                    </td>
                    <td class="text-center" style="font-size: 11px;">
                        {{ number_format($totalDistinctRepurchases) }}
                    </td>
                    <td class="text-center" style="font-size: 11px; color: #9a3412;">
                        {{ number_format($totalOpen) }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- 2. Breakdown Report Table Grouped By Branch --}}
    <table class="section-header-table" style="margin-top: 14px;">
        <tr>
            <td class="section-title">
                ၂။ {{ $zg('စစ်ဆေးတွေ့ရှိချက် နှင့် ဌာနခွဲအလိုက် ကြိမ်နှုန်း အသေးစိတ်') }} (Breakdown by Branch)
            </td>
            <td class="section-info">
                *{{ $zg('အများဆုံး ကြိမ်နှုန်းမှ အနည်းဆုံးသို့ အစီအစဉ်တကျ ပြသထားပါသည်') }}
            </td>
        </tr>
    </table>

    <table class="solid-table">
        <thead>
            <tr>
                <th style="width: 50%;">
                    {{ $zg('စစ်ဆေးတွေ့ရှိချက်') }}
                </th>
                <th style="width: 50%;">
                    Branch {{ $zg('အလိုက် ကြိမ်နှုန်း') }}
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
                                <td rowspan="{{ $branchCount }}" style="vertical-align: top; background-color: #f9fafb; font-weight: bold; padding: 6px 8px;">
                                    <div style="font-size: 10.5px; font-weight: bold; color: #111827;">
                                        {{ $zg($group['label']) }}
                                    </div>
                                    <div style="font-size: 9px; font-weight: normal; color: #6b7280; margin-top: 3px;">
                                        {{ $zg('စုစုပေါင်း:') }} <strong style="color: #374151;">{{ number_format($group['distinct_repurchase_count']) }}</strong> {{ $zg('ကြိမ်') }}
                                    </div>
                                </td>
                                @php $isFirst = false; @endphp
                            @endif

                            <td>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="border: none; padding: 0; font-weight: bold; color: #374151;">
                                            {{ $zg($branchName) }}
                                        </td>
                                        <td style="border: none; padding: 0; text-align: right; white-space: nowrap;">
                                            <span class="branch-badge">
                                                {{ number_format($bData['distinct_repurchase_count']) }} {{ $zg('ကြိမ်') }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="font-weight: bold;">
                            {{ $zg($group['label']) }}
                        </td>
                        <td style="color: #6b7280;">
                            {{ $zg('မှတ်တမ်း မရှိပါ') }}
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="2" class="text-center" style="padding: 18px; color: #6b7280;">
                        {{ $zg('ရွေးချယ်ထားသော ရက်စွဲအတွင်း ဌာနခွဲအလိုက် စစ်ဆေးတွေ့ရှိချက် မှတ်တမ်း မရှိပါ။') }} (No branch breakdown records found)
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- 3. Weight & Numeric Variance Analysis Table --}}
    <table class="section-header-table" style="page-break-before: auto; margin-top: 25px;">
        <tr>
            <td class="section-title">
                ၃။ {{ $zg('အလေးချိန်နှင့် ကိန်းဂဏန်း ကွာဟချက် အနှစ်ချုပ်') }} (Weight & Numeric Variance Analysis)
            </td>
            <td class="section-info">
                {{ $zg('*Expected Value နှင့် Checked Value နှိုင်းယှဉ်ချက်') }}
            </td>
        </tr>
    </table>

    <table class="solid-table">
        <thead>
            <tr>
                <th style="width: 34%;">
                    {{ $zg('စစ်ဆေးချက်') }}
                </th>
                <th style="width: 22%; text-align: right;">
                    Expected {{ $zg('ထက် ပိုသော ပမာဏ') }} (+)
                </th>
                <th style="width: 22%; text-align: right;">
                    Expected {{ $zg('ထက် လျော့သော ပမာဏ') }} (-)
                </th>
                <th style="width: 22%; text-align: right;">
                    {{ $zg('အသားတင် ကွာဟချက်') }} (Balance)
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($table3 ?? [] as $row)
                @php
                    $unit = $row['unit'] ? ' ' . $zg($row['unit']) : '';
                    $isWeight = in_array($row['field_name'], ['weight_gram', 'ကျပ်-ချိန်', 'ရွေး-ချိန်', 'ကျောက်-ချိန်']);
                    $fmt = function($num) use ($isWeight, $unit) {
                        if ($num == 0) return '0' . $unit;
                        $val = $isWeight ? number_format($num, 4) : number_format($num, 2);
                        $trimmed = rtrim(rtrim($val, '0'), '.');
                        return $trimmed . $unit;
                    };
                    $overText = $fmt($row['over_amount']);
                    $shortText = $fmt($row['short_amount']);
                    $balance = $row['net_balance'];
                @endphp
                <tr>
                    <td style="font-weight: bold; vertical-align: top;">
                        {{ $zg($row['label']) }}
                    </td>
                    <td style="text-align: right; font-weight: bold; color: #047857; vertical-align: top;">
                        @if($row['over_amount'] > 0)
                            +{{ $overText }}
                        @else
                            <span style="color: #9ca3af;">-</span>
                        @endif
                        @if(!empty($showVarianceDetail) && !empty($row['branches']))
                            @php
                                $overBranches = array_filter($row['branches'], fn($b) => $b['over_amount'] > 0);
                            @endphp
                            @if(!empty($overBranches))
                                <div style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #d1fae5; font-size: 8px; text-align: left; font-weight: normal;">
                                    @foreach($overBranches as $bName => $bData)
                                        <div style="margin-bottom: 2px;">
                                            <span style="color: #4b5563;">{{ $zg($bName) }}:</span>
                                            <span style="color: #047857; font-weight: bold;">+{{ $fmt($bData['over_amount']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: bold; color: #b45309; vertical-align: top;">
                        @if($row['short_amount'] > 0)
                            -{{ $shortText }}
                        @else
                            <span style="color: #9ca3af;">-</span>
                        @endif
                        @if(!empty($showVarianceDetail) && !empty($row['branches']))
                            @php
                                $shortBranches = array_filter($row['branches'], fn($b) => $b['short_amount'] > 0);
                            @endphp
                            @if(!empty($shortBranches))
                                <div style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #fef3c7; font-size: 8px; text-align: left; font-weight: normal;">
                                    @foreach($shortBranches as $bName => $bData)
                                        <div style="margin-bottom: 2px;">
                                            <span style="color: #4b5563;">{{ $zg($bName) }}:</span>
                                            <span style="color: #b45309; font-weight: bold;">-{{ $fmt($bData['short_amount']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: bold; vertical-align: top;">
                        @if($balance > 0)
                            <span style="color: #047857;">+{{ $fmt($balance) }}</span>
                        @elseif($balance < 0)
                            <span style="color: #b91c1c;">-{{ $fmt(abs($balance)) }}</span>
                        @else
                            <span style="color: #4b5563;">0{{ $unit }}</span>
                        @endif
                        @if(!empty($showVarianceDetail) && !empty($row['branches']))
                            @php
                                $activeBranches = array_filter($row['branches'], fn($b) => $b['over_amount'] > 0 || $b['short_amount'] > 0);
                            @endphp
                            @if(!empty($activeBranches))
                                <div style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #e5e7eb; font-size: 8px; text-align: left; font-weight: normal;">
                                    @foreach($activeBranches as $bName => $bData)
                                        @php $bBal = $bData['net_balance']; @endphp
                                        <div style="margin-bottom: 2px;">
                                            <span style="color: #4b5563;">{{ $zg($bName) }}:</span>
                                            <span style="font-weight: bold; color: {{ $bBal > 0 ? '#047857' : ($bBal < 0 ? '#b91c1c' : '#4b5563') }};">
                                                {{ $bBal > 0 ? '+' : ($bBal < 0 ? '-' : '') }}{{ $fmt(abs($bBal)) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 14px; color: #6b7280;">
                        {{ $zg('ရွေးချယ်ထားသော ရက်စွဲအတွင်း ကိန်းဂဏန်း/အလေးချိန် ကွာဟချက် မှတ်တမ်း မရှိပါ။') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Document Sign-off Section --}}
    <table class="sign-table">
        <tr>
            <td>
                <strong>{{ $zg('အစီရင်ခံစာ ပြုစုသူ') }}</strong>
                <div class="sign-line"></div>
                <div>( .................................................... )</div>
            </td>
            <td>
                <strong>{{ $zg('စိစစ်သူ / ဌာနမှူး') }}</strong>
                <div class="sign-line"></div>
                <div>( .................................................... )</div>
            </td>
            <td>
                <strong>{{ $zg('အတည်ပြုသူ / စီမံခန့်ခွဲမှု') }}</strong>
                <div class="sign-line"></div>
                <div>( .................................................... )</div>
            </td>
        </tr>
    </table>
</body>
</html>