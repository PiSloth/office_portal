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

        WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateDraft->id, 'to_state_id' => $stateSubmitted->id, 'action_name' => 'Submit']);
        WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateSubmitted->id, 'to_state_id' => $stateVerified->id, 'action_name' => 'Verify']);
        WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateSubmitted->id, 'to_state_id' => $stateRejected->id, 'action_name' => 'Reject']);
        WorkflowTransition::create(['workflow_id' => $workflow->id, 'from_state_id' => $stateVerified->id, 'to_state_id' => $statePaid->id, 'action_name' => 'Pay']);

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
    }
}
