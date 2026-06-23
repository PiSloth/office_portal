<?php

namespace App\Services;

use App\Models\ProductCheck;
use App\Models\Decision;
use App\Models\DecisionRule;
use App\Models\DecisionHistory;
use App\Models\Comment;

class DecisionWorkflowService
{
    /**
     * Evaluate decision rules on a completed ProductCheck and generate decisions if rules match.
     *
     * @param ProductCheck $productCheck
     * @return void
     */
    public function evaluateRulesAndCreateDecisions(ProductCheck $productCheck): void
    {
        // Only run if check status is FAIL or WARNING
        if ($productCheck->result_status === 'PASS') {
            return;
        }

        // Fetch failed values in this check
        $failedValues = $productCheck->checkValues()
            ->where('status', 'FAIL')
            ->get();

        if ($failedValues->isEmpty()) {
            return;
        }

        // Fetch all active decision rules
        $rules = DecisionRule::where('is_active', true)->get();

        foreach ($rules as $rule) {
            // Check if any failed value matches the rule's criteria_field
            $matchedFailure = $failedValues->first(function ($val) use ($rule) {
                return strtolower($val->field_name) === strtolower($rule->criteria_field);
            });

            if ($matchedFailure) {
                // Check if a decision of this type is already open for this product check to prevent duplicate decisions
                $exists = Decision::where('product_check_id', $productCheck->id)
                    ->where('decision_type_id', $rule->decision_type_id)
                    ->exists();

                if (!$exists) {
                    $remark = "System automatically created decision based on rule: '{$rule->name}' due to failure on field '{$rule->criteria_field}'. Expected: '{$matchedFailure->expected_value}', Actual: '{$matchedFailure->actual_value}'.";

                    // Create Decision
                    $decision = Decision::create([
                        'product_check_id' => $productCheck->id,
                        'decision_type_id' => $rule->decision_type_id,
                        'action_status' => 'OPEN',
                        'assigned_to' => null,
                        'decision_by' => $productCheck->checked_by,
                        'remark' => $remark,
                    ]);

                    // Log history
                    DecisionHistory::create([
                        'decision_id' => $decision->id,
                        'old_status' => 'NONE',
                        'new_status' => 'OPEN',
                        'changed_by' => $productCheck->checked_by,
                        'remark' => 'Automatic rule trigger.',
                    ]);

                    // Log comment
                    Comment::create([
                        'decision_id' => $decision->id,
                        'user_id' => $productCheck->checked_by,
                        'comment_type' => 'LOG',
                        'comment' => $remark,
                    ]);

                    // Copy attachments from product check to decision
                    foreach ($productCheck->attachments as $attachment) {
                        $decision->attachments()->create([
                            'file_path' => $attachment->file_path,
                            'file_name' => $attachment->file_name,
                            'file_type' => $attachment->file_type,
                            'file_size' => $attachment->file_size,
                            'uploaded_by' => $attachment->uploaded_by,
                        ]);
                    }
                }
            }
        }
    }
}
