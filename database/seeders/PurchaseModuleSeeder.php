<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Core\Workflow\Models\Workflow;
use App\Modules\Core\Workflow\Models\WorkflowState;
use App\Modules\Core\Workflow\Models\WorkflowTransition;
use App\Modules\Core\Calculation\Models\CalculationMethod;
use App\Modules\Core\Calculation\Models\CalculationParameter;
use App\Modules\Core\Validation\Models\ValidationRuleSet;
use App\Modules\Core\Validation\Models\ValidationRule;

class PurchaseModuleSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------
        // 1. WORKFLOW ENGINE
        // ---------------------------------------------------
        $workflow = Workflow::create([
            'name' => 'Jewelry Repurchase Workflow',
            'description' => 'Default workflow for purchasing jewelry from customers.',
        ]);

        $stateDraft = WorkflowState::create(['workflow_id' => $workflow->id, 'name' => 'Draft', 'color' => 'gray', 'is_start' => true]);
        $stateSubmitted = WorkflowState::create(['workflow_id' => $workflow->id, 'name' => 'Submitted', 'color' => 'blue']);
        $stateVerified = WorkflowState::create(['workflow_id' => $workflow->id, 'name' => 'Verified', 'color' => 'success']);
        $stateRejected = WorkflowState::create(['workflow_id' => $workflow->id, 'name' => 'Rejected', 'color' => 'danger', 'is_end' => true]);
        $statePaid = WorkflowState::create(['workflow_id' => $workflow->id, 'name' => 'Paid', 'color' => 'success', 'is_end' => true]);

        $transitionSubmit = WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateDraft->id, 'to_state_id' => $stateSubmitted->id, 'action_name' => 'Submit']);
        $transitionVerify = WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateSubmitted->id, 'to_state_id' => $stateVerified->id, 'action_name' => 'Verify']);
        $transitionReject = WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateSubmitted->id, 'to_state_id' => $stateRejected->id, 'action_name' => 'Reject']);
        $transitionPay    = WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateVerified->id, 'to_state_id' => $statePaid->id, 'action_name' => 'Pay']);

        // ---------------------------------------------------
        // 2. CALCULATION ENGINE
        // ---------------------------------------------------
        $calcMethod = CalculationMethod::create([
            'name' => 'Standard Jewelry Calculator',
            'php_class_name' => 'App\Modules\Calculation\Strategies\JewelryCalculator', // To be created
        ]);

        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'base_gold_price', 'value' => '4500000', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'tax_rate', 'value' => '0.05', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'gram_per_kyat', 'value' => '16.606', 'type' => 'numeric']);

        // GB Multipliers
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_gb_16', 'value' => '1.0', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_gb_15', 'value' => '0.941176', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_gb_14.2', 'value' => '0.914286', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_gb_14', 'value' => '0.888889', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_gb_13', 'value' => '0.7522', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_gb_12', 'value' => '0.75', 'type' => 'numeric']);

        // Other Multipliers
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_16', 'value' => '0.954', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_15', 'value' => '0.8962', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_14.2', 'value' => '0.8693', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_14', 'value' => '0.8439', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_13', 'value' => '0.767513', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_12', 'value' => '0.705001', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_10', 'value' => '0.580007', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_8', 'value' => '0.455013', 'type' => 'numeric']);
        CalculationParameter::create(['method_id' => $calcMethod->id, 'key' => 'multiplier_oth_4', 'value' => '0.205022', 'type' => 'numeric']);

        // ---------------------------------------------------
        // 3. VALIDATION ENGINE
        // ---------------------------------------------------
        $ruleSet = ValidationRuleSet::create([
            'name' => 'Standard Jewelry Validation',
            'description' => 'Validates weight tolerances for purchased jewelry.',
        ]);

        ValidationRule::create([
            'rule_set_id' => $ruleSet->id,
            'field_name' => 'weight_gram',
            'operator' => 'tolerance',
            'expected_source' => 'product_master.weight', // Example of dynamic mapping
            'tolerance' => 0.05, // 0.05 grams tolerance
            'is_required' => true,
        ]);
        
        ValidationRule::create([
            'rule_set_id' => $ruleSet->id,
            'field_name' => 'gold_grade',
            'operator' => 'equals',
            'expected_source' => 'product_master.gold_grade', 
            'tolerance' => 0,
            'is_required' => true,
        ]);

        // Associate validation rules with Verify transition
        $transitionVerify->update(['validation_rule_set_id' => $ruleSet->id]);

        // ---------------------------------------------------
        // 4. WORKFLOW TRANSITION CHECKLISTS
        // ---------------------------------------------------
        $jewelryProductType = \App\Models\ProductType::where('code', 'JEWELRY')->orWhere('name', 'jewelry')->first();
        $productTypeId = $jewelryProductType?->id;

        // 4.1 Submit Transition Checklist
        $checklistSubmit = \App\Modules\Core\Workflow\Models\Checklist::create([
            'product_type_id' => $productTypeId,
            'name' => 'Submit Checklist',
            'description' => 'Checks required before submitting the purchase request.',
        ]);
        \App\Modules\Core\Workflow\Models\ChecklistItem::create(['checklist_id' => $checklistSubmit->id, 'label' => 'Confirm customer identity card details matches name', 'is_required' => true, 'is_active' => true]);
        \App\Modules\Core\Workflow\Models\ChecklistItem::create(['checklist_id' => $checklistSubmit->id, 'label' => 'Clean the jewelry items to remove dirt before weighing', 'is_required' => false, 'is_active' => true]);
        $transitionSubmit->update(['checklist_id' => $checklistSubmit->id]);

        // 4.2 Verify Transition Checklist
        $checklistVerify = \App\Modules\Core\Workflow\Models\Checklist::create([
            'product_type_id' => $productTypeId,
            'name' => 'Verify Checklist',
            'description' => 'Checks required during the verifier inspection stage.',
        ]);
        \App\Modules\Core\Workflow\Models\ChecklistItem::create(['checklist_id' => $checklistVerify->id, 'label' => 'Acid testing performed to confirm gold carat value', 'is_required' => true, 'is_active' => true]);
        \App\Modules\Core\Workflow\Models\ChecklistItem::create(['checklist_id' => $checklistVerify->id, 'label' => 'Gold hallmark stamp inspected under magnifying glass', 'is_required' => true, 'is_active' => true]);
        $transitionVerify->update(['checklist_id' => $checklistVerify->id]);

        // 4.3 Pay Transition Checklist
        $checklistPay = \App\Modules\Core\Workflow\Models\Checklist::create([
            'product_type_id' => $productTypeId,
            'name' => 'Payment Checklist',
            'description' => 'Checks required before finalizing the payout to the customer.',
        ]);
        \App\Modules\Core\Workflow\Models\ChecklistItem::create(['checklist_id' => $checklistPay->id, 'label' => 'Double count physical currency cash count against total voucher amount', 'is_required' => true, 'is_active' => true]);
        \App\Modules\Core\Workflow\Models\ChecklistItem::create(['checklist_id' => $checklistPay->id, 'label' => 'Secure customer physical signature on printed voucher copy', 'is_required' => true, 'is_active' => true]);
        $transitionPay->update(['checklist_id' => $checklistPay->id]);
    }
}
