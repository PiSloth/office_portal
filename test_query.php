<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('validation_histories as vh')
    ->join('validation_rules as vr', 'vh.rule_id', '=', 'vr.id')
    ->leftJoin('purchase_items as pi', function($join) {
        $join->on('vh.validatable_id', '=', 'pi.id')
             ->where('vh.validatable_type', '=', \App\Modules\Purchase\Models\PurchaseItem::class);
    })
    ->join('purchase_requests as pr', function ($join) {
        $join->on(function ($query) {
            $query->on('vh.validatable_id', '=', 'pr.id')
                  ->where('vh.validatable_type', '=', \App\Modules\Purchase\Models\PurchaseRequest::class);
        })
        ->orOn(function ($query) {
            $query->on('pi.purchase_request_id', '=', 'pr.id')
                  ->where('vh.validatable_type', '=', \App\Modules\Purchase\Models\PurchaseItem::class);
        });
    })
    ->leftJoin('branches as b', 'pr.branch_id', '=', 'b.id')
    ->leftJoin('workflow_states as ws', 'pr.workflow_state_id', '=', 'ws.id')
    ->where('vh.status', '=', 'FAIL')
    ->whereNull('pr.deleted_at')
    ->select([
        'vr.label as rule_label',
        'vr.field_name as rule_field',
        'b.name as branch_name',
        'vh.input_value',
        'vh.expected_value',
        'ws.name as state_name',
        'ws.is_end'
    ])
    ->get();

foreach ($rows as $row) {
    echo "Field: " . ($row->rule_label ?: $row->rule_field) . " | Branch: {$row->branch_name} | Actual: {$row->input_value} | Expected: {$row->expected_value} | State: {$row->state_name} (is_end: {$row->is_end})\n";
}













