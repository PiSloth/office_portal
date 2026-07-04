<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchased Items Report</title>
    <style>
        @page {
            size: A4 landscape;
            margin-top: 25mm;
            margin-bottom: 15mm;
            margin-left: 10mm;
            margin-right: 10mm;
        }
        @font-face {
            font-family: 'Padauk';
            src: url('{{ storage_path("fonts/Padauk-Regular.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Padauk';
            src: url('{{ storage_path("fonts/Padauk-Bold.ttf") }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        body {
            font-family: 'Padauk', sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        /* Fixed Header on every printed page */
        .report-header {
            position: fixed;
            top: -18mm;
            left: 0;
            right: 0;
            height: 15mm;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 2px;
            z-index: 1000;
        }
        .report-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            margin: 0;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 10px;
            color: #4b5563;
            font-weight: bold;
            margin-top: 2px;
        }
        .report-meta {
            text-align: right;
            font-size: 9px;
            color: #4b5563;
            line-height: 1.4;
        }
        .page-number::after {
            content: counter(page);
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8mm;
        }
        /* Ensure table header repeats automatically on each page break */
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        .report-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            border: 1px solid #d1d5db;
            padding: 6px 4px;
            text-align: left;
        }
        .report-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 4px;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .total-row {
            background-color: #f9fafb !important;
            border-top: 2px solid #111827;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #111827;
            padding: 8px 4px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $totalQty = 0;
        $totalGrams = 0.0;
        $totalKyat = 0;
        $totalPae = 0;
        $totalYawe = 0;
        $totalPrice = 0;

        foreach($items as $item) {
            $inputs = $item->dynamic_fields_json ?? [];
            $qty = $inputs['quantity'] ?? 1;
            $totalQty += $qty;
            
            $totalGrams += ($inputs['goldWeightGram'] ?? 0) * $qty;
            $totalKyat += ($inputs['kyat'] ?? 0) * $qty;
            $totalPae += ($inputs['pae'] ?? 0) * $qty;
            $totalYawe += ($inputs['yawe'] ?? 0) * $qty;
            
            $totalPrice += $item->calculated_price;
        }

        // Normalize Kyat/Pae/Yawe
        $extraPae = floor($totalYawe / 8);
        $totalYawe = $totalYawe % 8;
        $totalPae += $extraPae;

        $extraKyat = floor($totalPae / 16);
        $totalPae = $totalPae % 16;
        $totalKyat += $extraKyat;
        
        $printedBy = auth()->user()?->name ?? 'System';
        $printedTime = now()->format('d M Y, h:i A');
        
        $dateText = $date ? \Carbon\Carbon::parse($date)->format('d M Y') : 'All Dates';
    @endphp

    <!-- Repeating Header across pages using table-based layout compatible with Dompdf -->
    <div class="report-header">
        <table class="report-header-table">
            <tr>
                <td style="width: 70%; vertical-align: top;">
                    <h1 class="report-title">Purchased Items Report</h1>
                    <div class="report-subtitle">
                        Location: {{ $branchName }} | Report Date: {{ $dateText }}
                    </div>
                </td>
                <td style="width: 30%; vertical-align: top;" class="report-meta">
                    <div>Printed By: <strong>{{ $printedBy }}</strong></div>
                    <div>Printed At: <strong>{{ $printedTime }}</strong></div>
                    <div>Page: <strong><span class="page-number"></span></strong></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 11%;">PR No.</th>
                <th style="width: 14%;">Customer Name</th>
                <th style="width: 11%;">Customer Phone</th>
                <th style="width: 18%;">Product Name</th>
                <th style="width: 8%;">Gold Grade</th>
                <th style="width: 8%;" class="text-right">Weight (Gram)</th>
                <th style="width: 12%;">Weight (K/P/Y)</th>
                <th style="width: 4%;" class="text-center">Qty</th>
                <th style="width: 8%;" class="text-center">ရ/မရ</th>
                <th style="width: 12%;" class="text-right">Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                @php
                    $inputs = $item->dynamic_fields_json ?? [];
                    $productName = $inputs['product_name'] ?? '-';
                    $goldGrade = ($inputs['goldList'] ?? '-') . ' ပဲ';
                    $weightGram = number_format(($inputs['goldWeightGram'] ?? 0), 2) . ' g';
                    $k = $inputs['kyat'] ?? 0;
                    $p = $inputs['pae'] ?? 0;
                    $y = $inputs['yawe'] ?? 0;
                    $weightKpy = "{$k}ကျပ် {$p}ပဲ {$y}ရွေး";
                    $qty = $inputs['quantity'] ?? 1;
                    $price = number_format($item->calculated_price) . ' MMK';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $item->purchaseRequest?->purchase_number ?? '-' }}</strong></td>
                    <td>{{ $item->purchaseRequest?->customer_name ?? '-' }}</td>
                    <td>{{ $item->purchaseRequest?->customer_phone ?? '-' }}</td>
                    <td><strong>{{ $productName }}</strong></td>
                    <td>{{ $goldGrade }}</td>
                    <td class="text-right">{{ $weightGram }}</td>
                    <td>{{ $weightKpy }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-center">{{ ($inputs['is_good'] ?? false) ? 'ရ' : 'မရ' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $price }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-8 text-gray-500">No purchased items found matching the selected filters.</td>
                </tr>
            @endforelse
            
            @if($items->isNotEmpty())
                <tr class="total-row">
                    <td colspan="6" class="text-right">Grand Total:</td>
                    <td class="text-right">{{ number_format($totalGrams, 2) }} g</td>
                    <td>{{ $totalKyat }}ကျပ် {{ $totalPae }}ပဲ {{ $totalYawe }}ရွေး</td>
                    <td class="text-center">{{ $totalQty }}</td>
                    <td></td>
                    <td class="text-right" style="color: #10b981;">{{ number_format($totalPrice) }} MMK</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
