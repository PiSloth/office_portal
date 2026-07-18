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
                display: flex;
                flex-direction: column;
                gap: 16px;
                border-bottom: 1px solid #f4f4f5;
                padding-bottom: 16px;
                margin-bottom: 20px;
            }
            @media (min-width: 768px) {
                .pivot-header {
                    flex-direction: row;
                    justify-content: space-between;
                    align-items: center;
                }
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
            
            /* Toggle Switch Style */
            .toggle-wrapper {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .toggle-label {
                font-size: 0.75rem;
                font-weight: 600;
                color: #71717a;
            }
            .dark .toggle-label {
                color: #a1a1aa;
            }
            .switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
            }
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #e4e4e7;
                transition: .3s;
                border-radius: 24px;
            }
            .dark .slider {
                background-color: #27272a;
            }
            .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s;
                border-radius: 50%;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .dark .slider:before {
                background-color: #a1a1aa;
            }
            input:checked + .slider {
                background-color: #f59e0b;
            }
            .dark input:checked + .slider {
                background-color: #d97706;
            }
            input:checked + .slider:before {
                transform: translateX(20px);
                background-color: white;
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
                    grid-template-columns: repeat(3, 1fr);
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
            .filter-select {
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
            .dark .filter-select {
                border-color: #27272a;
                background-color: #18181b;
                color: #f4f4f5;
            }
            .filter-select:focus {
                border-color: #f59e0b;
            }

            /* Table Styles */
            .table-container {
                overflow-x: auto;
                border: 1px solid #e4e4e7;
                border-radius: 12px;
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
            .grand-total-row {
                background-color: #f4f4f5;
                color: #09090b;
                font-weight: 800;
                border-top: 2px solid #e4e4e7;
            }
            .dark .grand-total-row {
                background-color: #27272a;
                color: #fafafa;
                border-color: #3f3f46;
            }

            /* Utilities */
            .balance-positive {
                color: #059669;
                font-weight: 800;
            }
            .balance-negative {
                color: #dc2626;
                font-weight: 800;
            }
            .balance-zero {
                color: #09090b;
            }
            .dark .balance-zero {
                color: #fafafa;
            }
            .branch-balance-positive {
                color: #10b981;
                font-weight: 600;
            }
            .branch-balance-negative {
                color: #ef4444;
                font-weight: 600;
            }
            .branch-balance-zero {
                color: #71717a;
            }
            .dark .branch-balance-zero {
                color: #a1a1aa;
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
        </style>

        <div class="pivot-header">
            <div>
                <h3 class="pivot-title">Product Pivot Summary Report</h3>
                <p class="pivot-subtitle">Summary of product counts and weights grouped by Subcategory and Branch.</p>
            </div>
            
            <!-- Display Mode Toggle -->
            <div class="toggle-wrapper">
                <span class="toggle-label">Sum of Qty</span>
                <label class="switch">
                    <input type="checkbox" wire:model.live="displayMode" 
                           true-value="weight" false-value="qty"
                           {{ $displayMode === 'weight' ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
                <span class="toggle-label">Sum of Weight (g)</span>
            </div>
        </div>

        <!-- Filters Grid -->
        <div class="filters-grid">
            <div class="filter-item">
                <label class="filter-label">Check Session</label>
                <select wire:model.live="selectedSessionId" class="filter-select">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label">Category</label>
                <select wire:model.live="selectedCategoryId" class="filter-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-item">
                <label class="filter-label">Stock Status</label>
                <select wire:model.live="stockStatusFilter" class="filter-select">
                    <option value="all">All Statuses</option>
                    <option value="not_started">Not Start Check</option>
                    <option value="over_stock">Over Stock</option>
                    <option value="loss_stock">Loss Stock</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="pivot-table">
                <thead class="pivot-thead">
                    <tr>
                        <th class="pivot-th">Sub Category > Branch</th>
                        <th class="pivot-th text-right">Imported</th>
                        <th class="pivot-th text-right">During Created</th>
                        <th class="pivot-th text-right">Checked Product</th>
                        <th class="pivot-th text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $scId => $scData)
                        @php
                            $isExpanded = "expanded['{$scId}'] !== false";
                        @endphp
                        <!-- Subcategory Header Row -->
                        <tr class="category-row"
                            @click="expanded['{{ $scId }}'] = expanded['{{ $scId }}'] === false ? true : false">
                            <td class="pivot-td">
                                <div class="category-cell">
                                    <svg class="arrow-icon" :class="expanded['{{ $scId }}'] === false ? '' : 'arrow-rotated'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span>{{ $scData['name'] }}</span>
                                </div>
                            </td>
                            <td class="pivot-td text-right">
                                {{ $displayMode === 'qty' ? number_format($scData['imported']) : number_format($scData['imported'], 2) }}
                            </td>
                            <td class="pivot-td text-right">
                                {{ $displayMode === 'qty' ? number_format($scData['during_created']) : number_format($scData['during_created'], 2) }}
                            </td>
                            <td class="pivot-td text-right">
                                {{ $displayMode === 'qty' ? number_format($scData['checked']) : number_format($scData['checked'], 2) }}
                            </td>
                            <td class="pivot-td text-right">
                                @php
                                    $balanceVal = $scData['balance'];
                                    $balanceClass = $balanceVal < 0 ? 'balance-negative' : ($balanceVal > 0 ? 'balance-positive' : 'balance-zero');
                                @endphp
                                <span class="{{ $balanceClass }}">
                                    {{ $displayMode === 'qty' ? number_format($balanceVal) : number_format($balanceVal, 2) }}
                                </span>
                            </td>
                        </tr>

                        <!-- Branch Rows -->
                        @foreach($scData['branches'] as $branch)
                            <tr class="branch-row" x-show="{{ $isExpanded }}" x-transition>
                                <td class="pivot-td" style="padding-left: 40px; font-style: italic;">
                                    {{ $branch['name'] }}
                                </td>
                                <td class="pivot-td text-right">
                                    {{ $displayMode === 'qty' ? number_format($branch['imported']) : number_format($branch['imported'], 2) }}
                                </td>
                                <td class="pivot-td text-right">
                                    {{ $displayMode === 'qty' ? number_format($branch['during_created']) : number_format($branch['during_created'], 2) }}
                                </td>
                                <td class="pivot-td text-right">
                                    {{ $displayMode === 'qty' ? number_format($branch['checked']) : number_format($branch['checked'], 2) }}
                                </td>
                                <td class="pivot-td text-right">
                                    @php
                                        $brBalance = $branch['balance'];
                                        $brBalanceClass = $brBalance < 0 ? 'branch-balance-negative' : ($brBalance > 0 ? 'branch-balance-positive' : 'branch-balance-zero');
                                    @endphp
                                    <span class="{{ $brBalanceClass }}">
                                        {{ $displayMode === 'qty' ? number_format($brBalance) : number_format($brBalance, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="pivot-td text-center" style="padding: 32px; text-align: center; font-style: italic; color: #a1a1aa;">
                                No records found matching the filter criteria.
                            </td>
                        </tr>
                    @endforelse

                    <!-- Grand Total Row -->
                    @if(!empty($reportData))
                        <tr class="grand-total-row">
                            <td class="pivot-td" style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">
                                Grand Total
                            </td>
                            <td class="pivot-td text-right">
                                {{ $displayMode === 'qty' ? number_format($grandTotal['imported']) : number_format($grandTotal['imported'], 2) }}
                            </td>
                            <td class="pivot-td text-right">
                                {{ $displayMode === 'qty' ? number_format($grandTotal['during_created']) : number_format($grandTotal['during_created'], 2) }}
                            </td>
                            <td class="pivot-td text-right">
                                {{ $displayMode === 'qty' ? number_format($grandTotal['checked']) : number_format($grandTotal['checked'], 2) }}
                            </td>
                            <td class="pivot-td text-right">
                                @php
                                    $gtBalance = $grandTotal['balance'];
                                    $gtBalanceClass = $gtBalance < 0 ? 'balance-negative' : ($gtBalance > 0 ? 'balance-positive' : 'balance-zero');
                                @endphp
                                <span class="{{ $gtBalanceClass }}" style="font-weight: 900;">
                                    {{ $displayMode === 'qty' ? number_format($gtBalance) : number_format($gtBalance, 2) }}
                                </span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-filament-widgets::widget>
