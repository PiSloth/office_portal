<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Voucher #{{ $record->id }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 148mm;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #111827;
            background-color: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            box-sizing: border-box;
        }

        .voucher-container {
            display: flex;
            width: 210mm;
            height: 148mm;
            position: relative;
            overflow: hidden;
        }

        .detail-side {
            width: 150mm;
            height: 148mm;
            padding: 6mm 5mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .summary-side {
            width: 60mm;
            height: 148mm;
            padding: 6mm 4mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 11px;
        }

        .summary-side .checklist-title {
            font-size: 11px;
        }

        .summary-side .checklist-item {
            font-size: 11px;
        }

        .summary-side .checklist-sub {
            font-size: 11px;
        }

        .summary-side .summary-footer {
            font-size: 11px;
        }

        .summary-side .sig-box {
            font-size: 11px;
        }

        .cut-line {
            position: absolute;
            left: 150mm;
            top: 0;
            bottom: 0;
            width: 0;
            border-left: 1px dashed #6b7280;
            z-index: 10;
        }

        .cut-line::after {
            content: '✂';
            position: absolute;
            top: 50%;
            left: -6px;
            transform: translateY(-50%);
            background: #ffffff;
            padding: 4px 0;
            font-size: 12px;
            color: #4b5563;
        }

        /* Headers and Layout */
        .header {
            margin-bottom: 3mm;
        }

        .shop-title {
            font-size: 14px;
            font-weight: 800;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .shop-sub {
            font-size: 8px;
            color: #4b5563;
            margin-top: 1px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1.5mm;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 1.5mm;
            margin-bottom: 3mm;
        }

        .meta-item {
            font-size: 8px;
            line-height: 1.2;
        }

        .meta-label {
            color: #6b7280;
            font-weight: 500;
            display: block;
        }

        .meta-value {
            font-weight: 600;
            color: #111827;
            display: block;
        }

        /* Table Styles */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: auto;
        }

        .items-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.5px;
            border: 1px solid #d1d5db;
            padding: 1mm 0.8mm;
            text-align: left;
        }

        .items-table td {
            border: 1px solid #e5e7eb;
            padding: 1mm 0.8mm;
            color: #1f2937;
        }

        .items-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Summary Checklist Styles */
        .checklist-title {
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5mm;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        .checklist-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5mm;
            font-size: 8.5px;
        }

        .checkbox {
            width: 9px;
            height: 9px;
            border: 1px solid #4b5563;
            border-radius: 1.5px;
            margin-right: 1.5mm;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .checklist-details {
            line-height: 1.2;
        }

        .checklist-name {
            font-weight: 600;
            color: #111827;
        }

        .checklist-sub {
            font-size: 7.5px;
            color: #6b7280;
            margin-top: 0.5mm;
        }

        /* Footer / Signatures */
        .footer-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4mm;
            margin-top: 2mm;
            font-size: 7.5px;
            text-align: center;
        }

        .sig-box {
            border-top: 1px solid #d1d5db;
            padding-top: 1mm;
            color: #4b5563;
        }

        .summary-footer {
            font-size: 7.5px;
            text-align: center;
            border-top: 1px solid #d1d5db;
            padding-top: 1.5mm;
            color: #4b5563;
        }

        .total-row td {
            font-weight: 700;
            background-color: #f9fafb;
            border-top: 1.5px solid #d1d5db;
        }
    </style>
</head>

<body>
    @php
        $creatorName = $record->creator?->name ?? 'System';
        $statusUpdaterName = $record->statusUpdater?->name ?? '-';
        $validatorNames =
            $record->items->flatMap->validationHistories->pluck('user.name')->filter()->unique()->implode(', ') ?: '-';
        $createdTime = $record->created_at->format('d M Y, h:i A');
        $printedTime = now()->format('d M Y, h:i A');
        $printedBy = auth()->user()?->name ?? 'System';
    @endphp

    <div class="voucher-container">
        <!-- Detail Side (Customer Portion - Left 5/7) -->
        <div class="detail-side">
            <div>
                <!-- Shop Header -->
                <div class="header">
                    <div class="shop-title">Shwe Tatar</div>
                    <div class="shop-sub">Location: {{ $record->branch?->name ?? 'Main Branch' }}</div>
                </div>

                <!-- Metadata Grid -->
                <div class="meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Purchase No:</span>
                        <span class="meta-value">{{ $record->purchase_number }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Status:</span>
                        <span class="meta-value">{{ $record->workflowState?->name ?? 'Draft' }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Customer:</span>
                        <span class="meta-value">{{ $record->customer_name }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Phone:</span>
                        <span class="meta-value">{{ $record->customer_phone }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Created At:</span>
                        <span class="meta-value">{{ $createdTime }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Printed At:</span>
                        <span class="meta-value">{{ $printedTime }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Created By:</span>
                        <span class="meta-value">{{ $creatorName }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Verified By:</span>
                        <span class="meta-value" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                            title="{{ $validatorNames }}">{{ $validatorNames }}</span>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Product Name</th>
                            <th style="width: 15%;">Gram</th>
                            <th style="width: 25%;">Weight (K/P/Y)</th>
                            <th style="width: 10%;">Grade</th>
                            <th style="width: 5%;">Qty</th>
                            <th style="width: 15%;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($record->items as $item)
                            @php
                                $inputs = $item->dynamic_fields_json ?? [];
                                $productName = $inputs['product_name'] ?? '-';
                                $goldGrade = ($inputs['goldList'] ?? '-') . ' ပဲ';
                                $weightGram = ($inputs['goldWeightGram'] ?? '0') . ' g';
                                $k = $inputs['kyat'] ?? 0;
                                $p = $inputs['pae'] ?? 0;
                                $y = $inputs['yawe'] ?? 0;
                                $weightKpy = "{$k}ကျပ် {$p}ပဲ {$y}ရွေး";
                                $qty = $inputs['quantity'] ?? 1;
                                $price = number_format($item->calculated_price) . ' MMK';
                            @endphp
                            <tr>
                                <td><strong>{{ $productName }}</strong></td>
                                <td>{{ $weightGram }}</td>
                                <td>{{ $weightKpy }}</td>
                                <td>{{ $goldGrade }}</td>
                                <td>{{ $qty }}</td>
                                <td style="text-align: right; font-weight: 600;">{{ $price }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right;"><strong>Total Amount:</strong></td>
                            <td style="text-align: center;">
                                <strong>{{ $record->items->sum(fn($i) => $i->dynamic_fields_json['quantity'] ?? 1) }}</strong>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #10b981;">
                                <strong>{{ number_format($record->total_amount) }} MMK</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Customer Portion Signatures -->
            <div class="footer-signatures">
                <div class="sig-box">Customer Signature</div>
                <div class="sig-box">Authorized Receiver</div>
            </div>
        </div>

        <!-- Scissor Cut Line Indicator -->
        <div class="cut-line"></div>

        <!-- Summary Side (Company Portion - Right 2/7) -->
        <div class="summary-side">
            <div>
                <!-- Shop Header Summary -->
                <div class="header" style="margin-bottom: 2mm;">
                    <div class="shop-title" style="font-size: 11px;">Shwe Tatar</div>
                    <div class="shop-sub" style="font-size: 8px;">Loc: {{ $record->branch?->name ?? 'Main Branch' }}
                    </div>
                </div>

                <!-- Compact Meta -->
                <div
                    style="font-size: 11px; line-height: 1.25; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 1mm; margin-bottom: 2mm;">
                    <div><span class="meta-label" style="display:inline;">No:</span> <span class="meta-value"
                            style="display:inline;">{{ $record->purchase_number }}</span></div>
                    <div><span class="meta-label" style="display:inline;">Status:</span> <span class="meta-value"
                            style="display:inline;">{{ $record->workflowState?->name ?? 'Draft' }}</span></div>
                    <div><span class="meta-label" style="display:inline;">Updater:</span> <span class="meta-value"
                            style="display:inline;">{{ $statusUpdaterName }}</span></div>
                    <div><span class="meta-label" style="display:inline;">Cust:</span> <span class="meta-value"
                            style="display:inline;">{{ $record->customer_name }}</span></div>
                    <div><span class="meta-label" style="display:inline;">Creator:</span> <span class="meta-value"
                            style="display:inline;">{{ $creatorName }}</span></div>
                    <div><span class="meta-label" style="display:inline;">Verifier:</span> <span class="meta-value"
                            style="display:inline; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $validatorNames }}</span>
                    </div>
                    <div><span class="meta-label" style="display:inline;">Created:</span> <span class="meta-value"
                            style="display:inline;">{{ $createdTime }}</span></div>
                    <div><span class="meta-label" style="display:inline;">Printed By:</span> <span class="meta-value"
                            style="display:inline;">{{ $printedBy }}</span></div>
                    <div><span class="meta-label" style="display:inline;">Printed At:</span> <span class="meta-value"
                            style="display:inline;">{{ $printedTime }}</span></div>
                </div>

                <div class="checklist-title">Checklist</div>

                <!-- Checklist Items -->
                <div style="max-height: 48mm; overflow: hidden;">
                    @foreach ($record->items as $index => $item)
                        @php
                            $inputs = $item->dynamic_fields_json ?? [];
                            $productName = $inputs['product_name'] ?? '-';
                            $goldGrade = ($inputs['goldList'] ?? '-') . ' ပဲ';
                            $qty = $inputs['quantity'] ?? 1;
                            $weightGram = ($inputs['goldWeightGram'] ?? '0') . ' g';
                            $k = $inputs['kyat'] ?? 0;
                            $p = $inputs['pae'] ?? 0;
                            $y = $inputs['yawe'] ?? 0;
                            $weightKpy = "{$k}ကျပ် {$p}ပဲ {$y}ရွေး";
                            $remark = $inputs['remark'] ?? '';
                        @endphp
                        <div class="checklist-item">
                            <div class="checkbox"></div>
                            <div class="checklist-details">
                                <div class="checklist-name">
                                    {{ $productName }} ({{ $weightGram }})
                                    <span style="float: right; font-weight: bold; border: 1px solid #9ca3af; padding: 0px 3px; border-radius: 2px;">
                                        {{ ($inputs['is_good'] ?? false) ? 'ရ' : 'မရ' }}
                                    </span>
                                </div>
                                <div class="checklist-sub">Qty: {{ $qty }} | {{ $goldGrade }}</div>
                                <div class="checklist-sub">{{ $weightKpy }}</div>
                                @if (!empty($remark))
                                    <div class="checklist-sub" style="font-style: italic; color: #4b5563;">Rem:
                                        {{ $remark }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Company Portion Footer -->
            <div class="summary-footer">
                <div style="font-weight: 700; font-size: 11px; color: #111827; margin-bottom: 1mm;">
                    Total: {{ number_format($record->total_amount) }} MMK
                </div>
                <div class="sig-box" style="margin-top: 1mm; font-size: 11px;">Bagger Signature</div>
            </div>
        </div>
    </div>

    <!-- Auto trigger browser print dialog -->
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>
