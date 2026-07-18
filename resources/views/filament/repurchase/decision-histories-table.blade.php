@if ($record && $record->purchaseRequest)
    @php
        $purchaseRequest = $record->purchaseRequest;
        $itemIds = $purchaseRequest->items->pluck('id')->toArray();
    @endphp

    <div style="display: flex; flex-direction: column; gap: 16px; width: 100%;">
        @foreach ($purchaseRequest->failChecks as $fc)
            @php
                $ruleIds = \App\Modules\Core\Validation\Models\ValidationRule::where('field_name', $fc->field_name)
                    ->orWhere('label', $fc->field_name)
                    ->pluck('id')
                    ->toArray();
                    
                $histories = \App\Modules\Core\Validation\Models\ValidationHistory::with('user')
                    ->whereIn('rule_id', $ruleIds)
                    ->where(function($query) use ($purchaseRequest, $itemIds) {
                        $query->where(function($q) use ($purchaseRequest) {
                            $q->where('validatable_type', \App\Modules\Purchase\Models\PurchaseRequest::class)
                              ->where('validatable_id', $purchaseRequest->id);
                        })->orWhere(function($q) use ($itemIds) {
                            $q->where('validatable_type', \App\Modules\Purchase\Models\PurchaseItem::class)
                              ->whereIn('validatable_id', $itemIds);
                        });
                    })
                    ->latest()
                    ->get();
            @endphp

            <div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #ffffff; padding: 16px;">
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #111827; margin: 0 0 12px 0; text-transform: uppercase; letter-spacing: 0.05em;">
                    Check History for Field: <span style="color: #d97706;">{{ $fc->field_name }}</span>
                </h4>
                
                <div style="overflow-x: auto; width: 100%;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.75rem; color: #4b5563;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e5e7eb; background: #f9fafb; color: #374151; font-weight: 700;">
                                <th style="padding: 10px;">User</th>
                                <th style="padding: 10px;">Actual Value</th>
                                <th style="padding: 10px;">Expected Value</th>
                                <th style="padding: 10px;">Status</th>
                                <th style="padding: 10px;">Remarks</th>
                                <th style="padding: 10px;">Time Checked</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $history)
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 10px; font-weight: 600; color: #111827;">{{ $history->user?->name ?? 'System' }}</td>
                                    <td style="padding: 10px;">{{ $history->input_value ?? 'null' }}</td>
                                    <td style="padding: 10px;">{{ $history->expected_value ?? 'null' }}</td>
                                    <td style="padding: 10px;">
                                        @if($history->status === 'PASS')
                                            <span style="color: #047857; font-weight: 700; background: #ecfdf5; padding: 2px 8px; border-radius: 9999px;">PASS</span>
                                        @else
                                            <span style="color: #b91c1c; font-weight: 700; background: #fef2f2; padding: 2px 8px; border-radius: 9999px;">FAIL</span>
                                        @endif
                                    </td>
                                    <td style="padding: 10px;">{{ $history->remarks ?? '-' }}</td>
                                    <td style="padding: 10px; color: #6b7280;">{{ $history->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 16px; text-align: center; color: #9ca3af; font-style: italic;">No check history found for this field.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endif
