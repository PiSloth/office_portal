<x-filament-widgets::widget>
    <div class="pivot-container" x-data="{ expanded: {} }">
        <style>
            .pivot-container {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .dark .pivot-container {
                background: #18181b;
                border-color: #27272a;
                box-shadow: none;
            }
            .pivot-header {
                border-bottom: 1px solid #f4f4f5;
                padding-bottom: 16px;
                margin-bottom: 20px;
            }
            .dark .pivot-header {
                border-color: #27272a;
            }
            .pivot-title {
                font-size: 1.125rem;
                font-weight: 700;
                color: #09090b;
                margin: 0;
            }
            .dark .pivot-title {
                color: #fafafa;
            }
            .pivot-subtitle {
                font-size: 0.75rem;
                color: #71717a;
                margin: 4px 0 0 0;
            }
            .dark .pivot-subtitle {
                color: #a1a1aa;
            }

            /* Filters Grid */
            .filters-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 16px;
                margin-bottom: 24px;
            }
            @media (min-width: 640px) {
                .filters-grid {
                    grid-template-columns: repeat(4, 1fr);
                }
            }
            .filter-item {
                display: flex;
                flex-direction: column;
            }
            .filter-label {
                font-size: 0.75rem;
                font-weight: 600;
                color: #71717a;
                margin-bottom: 6px;
            }
            .dark .filter-label {
                color: #a1a1aa;
            }
            .filter-select, .filter-input {
                width: 100%;
                font-size: 0.875rem;
                padding: 8px 12px;
                border-radius: 8px;
                border: 1px solid #e4e4e7;
                background-color: #fafafa;
                color: #09090b;
                outline: none;
                transition: border-color 0.2s;
            }
            .dark .filter-select, .dark .filter-input {
                border-color: #27272a;
                background-color: #18181b;
                color: #f4f4f5;
            }
            .filter-select:focus, .filter-input:focus {
                border-color: #f59e0b;
            }

            /* Table Styles */
            .table-container {
                overflow-x: auto;
                border: 1px solid #e4e4e7;
                border-radius: 12px;
                margin-bottom: 24px;
            }
            .dark .table-container {
                border-color: #27272a;
            }
            .pivot-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
                font-size: 0.875rem;
                color: #27272a;
            }
            .dark .pivot-table {
                color: #e4e4e7;
            }
            .pivot-thead {
                background-color: #fafafa;
                font-size: 0.75rem;
                text-transform: uppercase;
                color: #71717a;
                border-bottom: 1px solid #e4e4e7;
            }
            .dark .pivot-thead {
                background-color: #27272a;
                color: #a1a1aa;
                border-color: #3f3f46;
            }
            .pivot-th {
                padding: 12px 20px;
                font-weight: 600;
            }
            .pivot-td {
                padding: 12px 20px;
            }
            .text-right {
                text-align: right;
            }
            
            /* Rows */
            .category-row {
                background-color: #fafafa;
                font-weight: 700;
                color: #09090b;
                cursor: pointer;
                border-bottom: 1px solid #e4e4e7;
                transition: background-color 0.2s;
            }
            .dark .category-row {
                background-color: #27272a;
                color: #fafafa;
                border-color: #3f3f46;
            }
            .category-row:hover {
                background-color: #f4f4f5;
            }
            .dark .category-row:hover {
                background-color: #3f3f46;
            }
            .branch-row {
                border-bottom: 1px solid #f4f4f5;
                transition: background-color 0.2s;
            }
            .dark .branch-row {
                border-color: #27272a;
            }
            .branch-row:hover {
                background-color: #fafafa;
            }
            .dark .branch-row:hover {
                background-color: #27272a;
            }
            
            .category-cell {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .arrow-icon {
                width: 16px;
                height: 16px;
                color: #a1a1aa;
                transition: transform 0.2s;
            }
            .arrow-rotated {
                transform: rotate(90deg);
            }
            .type-badge {
                font-size: 0.65rem;
                padding: 2px 6px;
                border-radius: 4px;
                font-weight: 700;
                text-transform: uppercase;
                background-color: #f4f4f5;
                color: #71717a;
            }
            .dark .type-badge {
                background-color: #27272a;
                color: #a1a1aa;
            }
            .type-badge-numeric {
                background-color: #fef3c7;
                color: #d97706;
            }
            .dark .type-badge-numeric {
                background-color: #78350f;
                color: #fbbf24;
            }
            .section-divider-title {
                font-size: 0.95rem;
                font-weight: 700;
                color: #111827;
                margin: 28px 0 12px 0;
                padding-bottom: 8px;
                border-bottom: 2px solid #e4e4e7;
            }
            .dark .section-divider-title {
                color: #fafafa;
                border-color: #27272a;
            }
        </style>

        <div class="pivot-header">
            <h3 class="pivot-title">Failed Fields Value Summary Report</h3>
            <p class="pivot-subtitle">Pivot report displaying sum of mismatched rule values or counts by Branch.</p>
        </div>

        <!-- Filters Grid -->
        <div class="filters-grid">
            <div class="filter-item">
                <label class="filter-label">Workflow Filter Mode</label>
                <select wire:model.live="workflowFilterMode" class="filter-select">
                    <option value="end_states">End States Only (Default)</option>
                    <option value="all">All States</option>
                    <option value="specific">Specific State</option>
                </select>
            </div>

            @if($workflowFilterMode === 'specific')
                <div class="filter-item">
                    <label class="filter-label">Workflow State</label>
                    <select wire:model.live="selectedStateId" class="filter-select">
                        <option value="">Choose State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="filter-item" style="opacity: 0.5; pointer-events: none;">
                    <label class="filter-label">Workflow State</label>
                    <select class="filter-select" disabled>
                        <option>Not Active</option>
                    </select>
                </div>
            @endif

            <div class="filter-item">
                <label class="filter-label">Start Date</label>
                <input type="date" wire:model.live="startDate" class="filter-input" />
            </div>

            <div class="filter-item">
                <label class="filter-label">End Date</label>
                <input type="date" wire:model.live="endDate" class="filter-input" />
            </div>
        </div>

        <!-- Standard Numeric/Count Table -->
        <div class="section-divider-title">Standard Failed Fields Summary (Number & Counts)</div>
        <div class="table-container">
            <table class="pivot-table">
                <thead class="pivot-thead">
                    <tr>
                        <th class="pivot-th">Failed Field > Branch > Scenario</th>
                        <th class="pivot-th text-right">Sum of Expected</th>
                        <th class="pivot-th text-right">Sum of Actual</th>
                        <th class="pivot-th text-right">Difference (Gain/Loss)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $fieldName => $data)
                        @php
                            $safeId = md5($fieldName);
                            $isExpanded = "expanded['{$safeId}'] === true";
                        @endphp
                        <tr class="category-row"
                            @click="expanded['{{ $safeId }}'] = expanded['{{ $safeId }}'] === true ? false : true">
                            <td class="pivot-td">
                                <div class="category-cell">
                                    <svg class="arrow-icon" :class="expanded['{{ $safeId }}'] === true ? 'arrow-rotated' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span>{{ $fieldName }}</span>
                                    <span class="type-badge {{ $data['is_numeric'] ? 'type-badge-numeric' : '' }}">
                                        {{ $data['is_numeric'] ? 'Number' : 'Text' }}
                                    </span>
                                </div>
                            </td>
                            <td class="pivot-td text-right">
                                {{ $data['is_numeric'] ? number_format($data['expected_total'], 2) : 'N/A' }}
                            </td>
                            <td class="pivot-td text-right">
                                {{ $data['is_numeric'] ? number_format($data['actual_total'], 2) : 'N/A' }}
                            </td>
                            
                            @php
                                $netDiff = $data['actual_total'] - $data['expected_total'];
                                $netColor = $netDiff > 0 ? 'color: #059669; font-weight: bold;' : ($netDiff < 0 ? 'color: #b91c1c; font-weight: bold;' : 'font-weight: bold;');
                                $netText = $netDiff > 0 ? '+' . number_format($netDiff, 2) . ' (Gain)' : ($netDiff < 0 ? number_format($netDiff, 2) . ' (Loss)' : '0.00');
                            @endphp
                            <td class="pivot-td text-right" style="{{ $netColor }}">
                                {{ $data['is_numeric'] ? $netText : number_format($data['count_total']) . ' Fails' }}
                            </td>
                        </tr>

                        @foreach($data['branches'] as $branchName => $scenarios)
                            <!-- Scenario 1: Expected < Actual (Gain) -->
                            <tr class="branch-row" x-show="{{ $isExpanded }}" x-transition>
                                <td class="pivot-td" style="padding-left: 40px; font-style: italic;">
                                    {{ $branchName }} <span style="color: #6b7280; font-size: 0.75rem;">(Expected &lt; Actual &rarr; Gain)</span>
                                </td>
                                <td class="pivot-td text-right">
                                    {{ $data['is_numeric'] ? number_format($scenarios['exp_less_act']['expected_sum'], 2) : 'N/A' }}
                                </td>
                                <td class="pivot-td text-right">
                                    {{ $data['is_numeric'] ? number_format($scenarios['exp_less_act']['actual_sum'], 2) : 'N/A' }}
                                </td>
                                
                                @php
                                    $diffLess = $scenarios['exp_less_act']['actual_sum'] - $scenarios['exp_less_act']['expected_sum'];
                                @endphp
                                <td class="pivot-td text-right" style="color: #059669; font-weight: 600;">
                                    {{ $data['is_numeric'] ? '+' . number_format($diffLess, 2) : number_format($scenarios['exp_less_act']['count']) }}
                                </td>
                            </tr>

                            <!-- Scenario 2: Expected > Actual (Loss) -->
                            <tr class="branch-row" x-show="{{ $isExpanded }}" x-transition style="border-bottom: 1px solid #e4e4e7;">
                                <td class="pivot-td" style="padding-left: 40px; font-style: italic;">
                                    {{ $branchName }} <span style="color: #6b7280; font-size: 0.75rem;">(Expected &gt; Actual &rarr; Loss)</span>
                                </td>
                                <td class="pivot-td text-right">
                                    {{ $data['is_numeric'] ? number_format($scenarios['exp_greater_act']['expected_sum'], 2) : 'N/A' }}
                                </td>
                                <td class="pivot-td text-right">
                                    {{ $data['is_numeric'] ? number_format($scenarios['exp_greater_act']['actual_sum'], 2) : 'N/A' }}
                                </td>
                                
                                @php
                                    $diffGreater = $scenarios['exp_greater_act']['actual_sum'] - $scenarios['exp_greater_act']['expected_sum'];
                                @endphp
                                <td class="pivot-td text-right" style="color: #b91c1c; font-weight: 600;">
                                    {{ $data['is_numeric'] ? number_format($diffGreater, 2) : number_format($scenarios['exp_greater_act']['count']) }}
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="pivot-td text-center" style="padding: 32px; text-align: center; font-style: italic; color: #a1a1aa;">
                                No records found matching the filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Boolean Mismatch Analysis Table (ရ/မရ Fields) -->
        <div class="section-divider-title">Boolean / Checklist Failed Fields Analysis (e.g. ရ/မရ)</div>
        <div class="table-container">
            <table class="pivot-table">
                <thead class="pivot-thead">
                    <tr>
                        <th class="pivot-th">Failed Field > Branch > Scenario</th>
                        <th class="pivot-th text-right">Expected</th>
                        <th class="pivot-th text-right">Actual</th>
                        <th class="pivot-th text-right">Difference</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($booleanReport as $fieldName => $data)
                        @php
                            $safeId = md5($fieldName . '_bool');
                            $isExpanded = "expanded['{$safeId}'] === true";
                        @endphp
                        <tr class="category-row"
                            @click="expanded['{{ $safeId }}'] = expanded['{{ $safeId }}'] === true ? false : true">
                            <td class="pivot-td">
                                <div class="category-cell">
                                    <svg class="arrow-icon" :class="expanded['{{ $safeId }}'] === true ? 'arrow-rotated' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span>{{ $fieldName }}</span>
                                    <span class="type-badge" style="background-color: #d1fae5; color: #065f46;">Boolean</span>
                                </div>
                            </td>
                            <td class="pivot-td text-right font-bold" colspan="3" style="font-size: 0.75rem; color: #71717a;">
                                Click to view scenarios per branch
                            </td>
                        </tr>

                        @foreach($data['branches'] as $branchName => $brVal)
                            <!-- Scenario 1: Expected True -> Actual False -->
                            <tr class="branch-row" x-show="{{ $isExpanded }}" x-transition>
                                <td class="pivot-td" style="padding-left: 40px; font-style: italic;">
                                    {{ $branchName }} <span style="color: #6b7280; font-size: 0.75rem;">(Expected True &rarr; False)</span>
                                </td>
                                <td class="pivot-td text-right">
                                    {{ number_format($brVal['true_to_false_count']) }} ({{ number_format($brVal['true_to_false_weight'], 2) }}g)
                                </td>
                                <td class="pivot-td text-right" style="color: #ef4444;">
                                    0 (0.00g)
                                </td>
                                <td class="pivot-td text-right font-bold" style="color: #ef4444;">
                                    {{ number_format($brVal['true_to_false_count']) }} ({{ number_format($brVal['true_to_false_weight'], 2) }}g)
                                </td>
                            </tr>

                            <!-- Scenario 2: Expected False -> Actual True -->
                            <tr class="branch-row" x-show="{{ $isExpanded }}" x-transition style="border-bottom: 1px solid #e4e4e7;">
                                <td class="pivot-td" style="padding-left: 40px; font-style: italic;">
                                    {{ $branchName }} <span style="color: #6b7280; font-size: 0.75rem;">(Expected False &rarr; True)</span>
                                </td>
                                <td class="pivot-td text-right">
                                    {{ number_format($brVal['false_to_true_count']) }} ({{ number_format($brVal['false_to_true_weight'], 2) }}g)
                                </td>
                                <td class="pivot-td text-right" style="color: #10b981;">
                                    0 (0.00g)
                                </td>
                                <td class="pivot-td text-right font-bold" style="color: #10b981;">
                                    {{ number_format($brVal['false_to_true_count']) }} ({{ number_format($brVal['false_to_true_weight'], 2) }}g)
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="pivot-td text-center" style="padding: 32px; text-align: center; font-style: italic; color: #a1a1aa;">
                                No boolean failed check records found matching the filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>
