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
        // Fetch all active decision rules
        $rules = DecisionRule::where('is_active', true)->get();

        // Fetch failed values in this check
        $failedValues = $productCheck->checkValues()
            ->where('status', 'FAIL')
            ->get();

        // Fetch existing open decisions for this check
        $openDecisions = Decision::where('product_check_id', $productCheck->id)
            ->where('action_status', 'OPEN')
            ->get();

        // Auto-resolve decisions if the check passed or specific field is no longer failing
        foreach ($openDecisions as $openDecision) {
            $matchingRule = $rules->firstWhere('id', $openDecision->decision_rule_id);
            if ($matchingRule) {
                $fieldName = $matchingRule->criteria_field;
                $stillFailing = $failedValues->contains(function ($val) use ($fieldName) {
                    return strtolower($val->field_name) === strtolower($fieldName);
                });

                if ($productCheck->result_status === 'PASS' || !$stillFailing) {
                    $openDecision->update(['action_status' => 'DONE']);

                    // Log history
                    DecisionHistory::create([
                        'decision_id' => $openDecision->id,
                        'old_status' => 'OPEN',
                        'new_status' => 'DONE',
                        'changed_by' => $productCheck->checked_by,
                        'remark' => 'Resolved automatically: check value corrected to match expected value.',
                    ]);

                    // Log comment
                    Comment::create([
                        'decision_id' => $openDecision->id,
                        'user_id' => $productCheck->checked_by,
                        'comment_type' => 'LOG',
                        'comment' => 'Resolved automatically: check value corrected to match expected value.',
                    ]);
                }
            }
        }

        // If the check has resolved to PASS entirely, return early
        if ($productCheck->result_status === 'PASS') {
            return;
        }

        if ($failedValues->isEmpty()) {
            return;
        }

        // Get ScanConfig fields config to check is_apply_validate
        $scanConfig = $productCheck->scanConfig;
        $fieldsConfig = collect(data_get($scanConfig?->config_json, 'fields', []));

        foreach ($rules as $rule) {
            // Find field config to check if is_apply_validate is disabled
            $fieldConfig = $fieldsConfig->first(function ($field) use ($rule) {
                return strtolower($field['field'] ?? '') === strtolower($rule->criteria_field);
            });

            // If the field is configured in scan config and is_apply_validate is false, do not trigger decision rule
            if ($fieldConfig && !($fieldConfig['is_apply_validate'] ?? false)) {
                continue;
            }

            // Check if any failed value matches the rule's criteria_field
            $matchedFailure = $failedValues->first(function ($val) use ($rule) {
                return strtolower($val->field_name) === strtolower($rule->criteria_field);
            });

            if ($matchedFailure) {
                // If actual value is empty, it means user has not checked/scanned it yet. Skip triggering a decision.
                if ($matchedFailure->actual_value === null || $matchedFailure->actual_value === '') {
                    continue;
                }
                // If condition is greater_than, verify if actual > expected
                if ($rule->criteria_condition === 'greater_than') {
                    if (is_numeric($matchedFailure->actual_value) && is_numeric($matchedFailure->expected_value)) {
                        if ((float)$matchedFailure->actual_value <= (float)$matchedFailure->expected_value) {
                            continue; // Skip, not greater than
                        }
                    } else {
                        continue;
                    }
                }

                // If condition is less_than, verify if actual < expected
                if ($rule->criteria_condition === 'less_than') {
                    if (is_numeric($matchedFailure->actual_value) && is_numeric($matchedFailure->expected_value)) {
                        if ((float)$matchedFailure->actual_value >= (float)$matchedFailure->expected_value) {
                            continue; // Skip, not less than
                        }
                    } else {
                        continue;
                    }
                }

                // Check if a decision of this rule is already open for this product check to prevent duplicate decisions
                $existingOpenDecision = Decision::where('product_check_id', $productCheck->id)
                    ->where('decision_rule_id', $rule->id)
                    ->where('action_status', 'OPEN')
                    ->first();

                // Format boolean values as Yes/No for user-friendly remarks
                $fieldModel = \App\Models\ProductTypeField::where('product_type_id', $productCheck->product->product_type_id)
                    ->where('field_name', $rule->criteria_field)
                    ->first();
                $isBoolean = $fieldModel && $fieldModel->field_type === 'boolean';

                $expectedValStr = $isBoolean
                    ? ($matchedFailure->expected_value === '1' || $matchedFailure->expected_value === 1 ? 'Yes' : 'No')
                    : $matchedFailure->expected_value;

                $actualValStr = $isBoolean
                    ? ($matchedFailure->actual_value === '1' || $matchedFailure->actual_value === 1 ? 'Yes' : 'No')
                    : $matchedFailure->actual_value;

                $remark = "System automatically created decision based on rule: '{$rule->name}' due to failure on field '{$rule->criteria_field}'. Expected: '{$expectedValStr}', Actual: '{$actualValStr}'.";

                if ($existingOpenDecision) {
                    // Update the existing open decision remark if it has changed
                    if ($existingOpenDecision->remark !== $remark) {
                        $existingOpenDecision->update([
                            'remark' => $remark,
                            'decision_rule_id' => $rule->id,
                        ]);

                        // Log comment update
                        Comment::create([
                            'decision_id' => $existingOpenDecision->id,
                            'user_id' => $productCheck->checked_by,
                            'comment_type' => 'LOG',
                            'comment' => "Value updated. Expected: '{$expectedValStr}', Actual: '{$actualValStr}'.",
                        ]);
                    }
                } else {
                    // Create Decision
                    $decision = Decision::create([
                        'product_check_id' => $productCheck->id,
                        'decision_type_id' => $rule->decision_type_id,
                        'decision_rule_id' => $rule->id,
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
