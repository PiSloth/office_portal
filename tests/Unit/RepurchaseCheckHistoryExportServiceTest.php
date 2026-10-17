<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\ProductType;
use App\Modules\Core\Validation\Models\ValidationHistory;
use App\Modules\Core\Validation\Models\ValidationRule;
use App\Modules\Core\Validation\Models\ValidationRuleSet;
use App\Modules\Core\Workflow\Models\WorkflowState;
use App\Modules\Purchase\Models\PurchaseItem;
use App\Modules\Purchase\Models\PurchaseRequest;
use App\Services\RepurchaseCheckHistoryExportService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RepurchaseCheckHistoryExportServiceTest extends TestCase
{
    public function test_it_exports_field_checked_history_with_boolean_qc_profit_and_durations(): void
    {
        $branch = new Branch(['name' => 'branch 1', 'code' => 'B1']);

        $pr = new PurchaseRequest([
            'purchase_number' => 'PR-/39489384',
            'customer_name' => 'Ko Nyo',
        ]);
        $pr->id = 101;
        $pr->created_at = Carbon::parse('2026-10-01 09:00:00');
        $pr->setRelation('branch', $branch);

        $item = new PurchaseItem([
            'purchase_request_id' => 101,
            'dynamic_fields_json' => [
                'product_name' => 'Gold Ring',
                'goldWeightGram' => 16.60,
                'is_good' => false,
            ],
        ]);
        $item->id = 501;
        $item->created_at = Carbon::parse('2026-10-01 09:00:00');

        // Workflow States
        $stateVerified = new WorkflowState(['name' => 'Verified']);
        $stateVerified->id = 1;

        $stateSupervisor = new WorkflowState(['name' => 'Supervisor Check']);
        $stateSupervisor->id = 2;

        $stateInvValidate = new WorkflowState(['name' => 'Inventory Validate']);
        $stateInvValidate->id = 3;

        // Rules
        $ruleSet = new ValidationRuleSet(['name' => 'Jewelry Validation']);
        $ruleWeight = new ValidationRule(['field_name' => 'goldWeightGram', 'label' => 'Weight']);
        $ruleWeight->setRelation('ruleSet', $ruleSet);

        $ruleIsGood = new ValidationRule(['field_name' => 'is_good', 'label' => 'ရ/မရ']);
        $ruleIsGood->setRelation('ruleSet', $ruleSet);

        // 1. Weight Histories across 3 stages
        $vhWeight1 = new ValidationHistory([
            'expected_value' => '16.60',
            'input_value' => '16.60',
            'status' => 'PASS',
        ]);
        $vhWeight1->id = 1;
        $vhWeight1->created_at = Carbon::parse('2026-10-01 10:00:00');
        $vhWeight1->setRelation('rule', $ruleWeight);
        $vhWeight1->setRelation('workflowState', $stateVerified);

        $vhWeight2 = new ValidationHistory([
            'expected_value' => '16.60',
            'input_value' => '16.55',
            'status' => 'FAIL',
        ]);
        $vhWeight2->id = 2;
        $vhWeight2->created_at = Carbon::parse('2026-10-03 14:00:00');
        $vhWeight2->setRelation('rule', $ruleWeight);
        $vhWeight2->setRelation('workflowState', $stateSupervisor);

        $vhWeight3 = new ValidationHistory([
            'expected_value' => '16.60',
            'input_value' => '16.50',
            'status' => 'FAIL',
        ]);
        $vhWeight3->id = 3;
        $vhWeight3->created_at = Carbon::parse('2026-10-04 15:00:00');
        $vhWeight3->setRelation('rule', $ruleWeight);
        $vhWeight3->setRelation('workflowState', $stateInvValidate);

        // 2. Boolean (ရ/မရ) Histories: false -> true => QC Profit = "good"
        $vhBool1 = new ValidationHistory([
            'expected_value' => '0',
            'input_value' => '0',
            'status' => 'PASS',
        ]);
        $vhBool1->id = 4;
        $vhBool1->created_at = Carbon::parse('2026-10-01 10:00:00');
        $vhBool1->setRelation('rule', $ruleIsGood);
        $vhBool1->setRelation('workflowState', $stateVerified);

        $vhBool2 = new ValidationHistory([
            'expected_value' => '0',
            'input_value' => '1',
            'status' => 'FAIL',
        ]);
        $vhBool2->id = 5;
        $vhBool2->created_at = Carbon::parse('2026-10-03 14:00:00');
        $vhBool2->setRelation('rule', $ruleIsGood);
        $vhBool2->setRelation('workflowState', $stateSupervisor);

        $vhBool3 = new ValidationHistory([
            'expected_value' => '0',
            'input_value' => '1',
            'status' => 'FAIL',
        ]);
        $vhBool3->id = 6;
        $vhBool3->created_at = Carbon::parse('2026-10-04 15:00:00');
        $vhBool3->setRelation('rule', $ruleIsGood);
        $vhBool3->setRelation('workflowState', $stateInvValidate);

        $item->setRelation('validationHistories', collect([
            $vhWeight1, $vhWeight2, $vhWeight3,
            $vhBool1, $vhBool2, $vhBool3,
        ]));

        // Second Item: Boolean true -> false => QC Profit = "Loss"
        $item2 = new PurchaseItem([
            'purchase_request_id' => 101,
            'dynamic_fields_json' => [
                'product_name' => 'Gold Chain',
                'is_good' => true,
            ],
        ]);
        $item2->id = 502;
        $item2->created_at = Carbon::parse('2026-10-01 09:00:00');

        $vhLoss1 = new ValidationHistory([
            'expected_value' => '1',
            'input_value' => '1',
            'status' => 'PASS',
        ]);
        $vhLoss1->id = 7;
        $vhLoss1->created_at = Carbon::parse('2026-10-01 10:00:00');
        $vhLoss1->setRelation('rule', $ruleIsGood);
        $vhLoss1->setRelation('workflowState', $stateVerified);

        $vhLoss2 = new ValidationHistory([
            'expected_value' => '1',
            'input_value' => '0',
            'status' => 'FAIL',
        ]);
        $vhLoss2->id = 8;
        $vhLoss2->created_at = Carbon::parse('2026-10-02 10:00:00');
        $vhLoss2->setRelation('rule', $ruleIsGood);
        $vhLoss2->setRelation('workflowState', $stateSupervisor);

        $item2->setRelation('validationHistories', collect([$vhLoss1, $vhLoss2]));

        // Third Item: Boolean true, false, true => Supervisor unagree
        $item3 = new PurchaseItem([
            'purchase_request_id' => 101,
            'dynamic_fields_json' => [
                'product_name' => 'Gold Bracelet',
                'is_good' => true,
            ],
        ]);
        $item3->id = 503;
        $item3->created_at = Carbon::parse('2026-10-01 09:00:00');

        $vhSupUnagree1 = new ValidationHistory([
            'expected_value' => '1',
            'input_value' => '1',
            'status' => 'PASS',
        ]);
        $vhSupUnagree1->id = 9;
        $vhSupUnagree1->created_at = Carbon::parse('2026-10-01 10:00:00');
        $vhSupUnagree1->setRelation('rule', $ruleIsGood);
        $vhSupUnagree1->setRelation('workflowState', $stateVerified);

        $vhSupUnagree2 = new ValidationHistory([
            'expected_value' => '1',
            'input_value' => '0',
            'status' => 'FAIL',
        ]);
        $vhSupUnagree2->id = 10;
        $vhSupUnagree2->created_at = Carbon::parse('2026-10-02 10:00:00');
        $vhSupUnagree2->setRelation('rule', $ruleIsGood);
        $vhSupUnagree2->setRelation('workflowState', $stateSupervisor);

        $vhSupUnagree3 = new ValidationHistory([
            'expected_value' => '1',
            'input_value' => '1',
            'status' => 'PASS',
        ]);
        $vhSupUnagree3->id = 11;
        $vhSupUnagree3->created_at = Carbon::parse('2026-10-02 16:00:00');
        $vhSupUnagree3->setRelation('rule', $ruleIsGood);
        $vhSupUnagree3->setRelation('workflowState', $stateInvValidate);

        $item3->setRelation('validationHistories', collect([$vhSupUnagree1, $vhSupUnagree2, $vhSupUnagree3]));

        $pr->setRelation('items', collect([$item, $item2, $item3]));
        $pr->setRelation('workflowState', $stateInvValidate);

        $service = new RepurchaseCheckHistoryExportService();
        $rows = $service->buildReportRows(collect([$pr]));

        $this->assertCount(4, $rows);

        // Row 1: Weight (2 Days + 1 Day = 3 Days, Late Check = true, Pickup Remark = '-')
        $this->assertSame('branch 1', $rows[0]['branch_name']);
        $this->assertSame('2026-10-01', $rows[0]['created_at']);
        $this->assertSame('Ko Nyo', $rows[0]['customer_name']);
        $this->assertSame('PR-/39489384', $rows[0]['purchase_number']);
        $this->assertSame('Gold Ring', $rows[0]['inventory_name']);
        $this->assertSame('အလေးချိန် (Weight Gram)', $rows[0]['checked_field']);
        $this->assertSame('16.60', $rows[0]['verify']);
        $this->assertSame('16.55', $rows[0]['supervisor_check']);
        $this->assertSame('16.50', $rows[0]['inv_validate']);
        $this->assertSame('2 Days', $rows[0]['duration_verify_to_supervisor']);
        $this->assertSame('1 Day', $rows[0]['duration_supervisor_to_inv']);
        $this->assertSame('3 Days', $rows[0]['total_lead_day']);
        $this->assertSame('true', $rows[0]['late_check']);
        $this->assertSame('-', $rows[0]['pickup_remark']);
        $this->assertSame('-', $rows[0]['qc_profit']);
        $this->assertSame('-', $rows[0]['who_unagree']);
        $this->assertSame('FAIL', $rows[0]['status']);

        // Row 2: Boolean false, true, true => good & Cashier unagree
        $this->assertSame('ရ/မရ (Pass/Fail)', $rows[1]['checked_field']);
        $this->assertSame('false', $rows[1]['verify']);
        $this->assertSame('true', $rows[1]['supervisor_check']);
        $this->assertSame('true', $rows[1]['inv_validate']);
        $this->assertSame('2 Days', $rows[1]['duration_verify_to_supervisor']);
        $this->assertSame('1 Day', $rows[1]['duration_supervisor_to_inv']);
        $this->assertSame('3 Days', $rows[1]['total_lead_day']);
        $this->assertSame('true', $rows[1]['late_check']);
        $this->assertSame('-', $rows[1]['pickup_remark']);
        $this->assertSame('good', $rows[1]['qc_profit']);
        $this->assertSame('Cashier unagree', $rows[1]['who_unagree']);
        $this->assertSame('FAIL', $rows[1]['status']);

        // Row 3: Boolean true, false, Pending => Loss & '-'
        $this->assertSame('Gold Chain', $rows[2]['inventory_name']);
        $this->assertSame('ရ/မရ (Pass/Fail)', $rows[2]['checked_field']);
        $this->assertSame('true', $rows[2]['verify']);
        $this->assertSame('false', $rows[2]['supervisor_check']);
        $this->assertSame('Pending', $rows[2]['inv_validate']);
        $this->assertSame('1 Day', $rows[2]['duration_verify_to_supervisor']);
        $this->assertSame('-', $rows[2]['duration_supervisor_to_inv']);
        $this->assertSame('-', $rows[2]['total_lead_day']);
        $this->assertSame('true', $rows[2]['late_check']);
        $this->assertSame('-', $rows[2]['pickup_remark']);
        $this->assertSame('Loss', $rows[2]['qc_profit']);
        $this->assertSame('-', $rows[2]['who_unagree']);
        $this->assertSame('FAIL', $rows[2]['status']);

        // Row 4: Boolean true, false, true (Duration 1 = 1 Day, Duration 2 = 0 Day) => Late Check = true & Same day with pickup
        $this->assertSame('Gold Bracelet', $rows[3]['inventory_name']);
        $this->assertSame('ရ/မရ (Pass/Fail)', $rows[3]['checked_field']);
        $this->assertSame('true', $rows[3]['verify']);
        $this->assertSame('false', $rows[3]['supervisor_check']);
        $this->assertSame('true', $rows[3]['inv_validate']);
        $this->assertSame('1 Day', $rows[3]['duration_verify_to_supervisor']);
        $this->assertSame('0 Day', $rows[3]['duration_supervisor_to_inv']);
        $this->assertSame('1 Day', $rows[3]['total_lead_day']);
        $this->assertSame('true', $rows[3]['late_check']);
        $this->assertSame('Same day with pickup', $rows[3]['pickup_remark']);
        $this->assertSame('-', $rows[3]['qc_profit']);
        $this->assertSame('Supervisor unagree', $rows[3]['who_unagree']);
        $this->assertSame('FAIL', $rows[3]['status']);

        // Direct unit assertions for all Who Unagree combinations
        $this->assertSame('Supervisor unagree', RepurchaseCheckHistoryExportService::resolveWhoUnagree('true', 'false', 'true'));
        $this->assertSame('Supervisor unagree', RepurchaseCheckHistoryExportService::resolveWhoUnagree('false', 'true', 'false'));
        $this->assertSame('Cashier unagree', RepurchaseCheckHistoryExportService::resolveWhoUnagree('false', 'true', 'true'));
        $this->assertSame('Cashier unagree', RepurchaseCheckHistoryExportService::resolveWhoUnagree('true', 'false', 'false'));
        $this->assertSame('Inv Validate unagree', RepurchaseCheckHistoryExportService::resolveWhoUnagree('true', 'true', 'false'));
        $this->assertSame('Inv Validate unagree', RepurchaseCheckHistoryExportService::resolveWhoUnagree('false', 'false', 'true'));
        $this->assertSame('-', RepurchaseCheckHistoryExportService::resolveWhoUnagree('true', 'true', 'true'));
        $this->assertSame('-', RepurchaseCheckHistoryExportService::resolveWhoUnagree('true', 'false', null));

        // Date Filter assertions (PR created_at is 2026-10-01)
        $matchingRows = $service->buildReportRows(collect([$pr]), '2026-10-01', '2026-10-05');
        $this->assertCount(4, $matchingRows);

        $excludedByStartRows = $service->buildReportRows(collect([$pr]), '2026-10-02', '2026-10-05');
        $this->assertCount(0, $excludedByStartRows);

        $excludedByEndRows = $service->buildReportRows(collect([$pr]), '2026-09-01', '2026-09-30');
        $this->assertCount(0, $excludedByEndRows);
    }
}
