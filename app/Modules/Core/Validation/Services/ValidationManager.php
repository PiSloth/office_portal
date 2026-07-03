<?php

namespace App\Modules\Core\Validation\Services;

use App\Modules\Core\Validation\Models\ValidationRuleSet;
use App\Modules\Core\Validation\Models\ValidationHistory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class ValidationManager
{
    /**
     * Validate inputs against a rule set.
     */
    public function validate(Model $validatable, ValidationRuleSet $ruleSet, array $inputs, $userId = null): bool
    {
        $rules = $ruleSet->rules;
        $allPassed = true;
        $productTypeId = $ruleSet->product_type_id;

        foreach ($rules as $rule) {
            if ($productTypeId) {
                // This rule applies to child items of a specific product type
                // Check if the validatable has items relationship
                if (method_exists($validatable, 'items')) {
                    $items = $validatable->items()->where('product_type_id', $productTypeId)->get();
                    if ($items->isEmpty()) {
                        // If it's required but no items of this type exist, fail
                        if ($rule->is_required) {
                            $allPassed = false;
                            $this->logHistory($validatable, $rule, null, null, 'FAIL', $userId);
                        }
                    } else {
                        foreach ($items as $item) {
                            $inputValue = $item->dynamic_fields_json[$rule->field_name] ?? null;
                            $expectedValue = $this->resolveExpectedValue($rule->expected_source ?: $rule->field_name, $item);

                            if ($rule->is_required && blank($inputValue)) {
                                $status = 'FAIL';
                            } else {
                                $status = $this->evaluate($inputValue, $expectedValue, $rule->operator, $rule->tolerance) ? 'PASS' : 'FAIL';
                            }

                            if ($status === 'FAIL') {
                                $allPassed = false;
                            }

                            $this->logHistory($item, $rule, $inputValue, $expectedValue, $status, $userId);
                        }
                    }
                }
            } else {
                // Standard rule applied to the parent model
                $inputValue = $inputs[$rule->field_name] ?? $validatable->getAttribute($rule->field_name) ?? null;
                $expectedValue = $this->resolveExpectedValue($rule->expected_source ?: $rule->field_name, $validatable);

                if ($rule->is_required && blank($inputValue)) {
                    $status = 'FAIL';
                } else {
                    $status = $this->evaluate($inputValue, $expectedValue, $rule->operator, $rule->tolerance) ? 'PASS' : 'FAIL';
                }

                if ($status === 'FAIL') {
                    $allPassed = false;
                }

                $this->logHistory($validatable, $rule, $inputValue, $expectedValue, $status, $userId);
            }
        }

        return $allPassed;
    }

    protected function logHistory(Model $validatable, $rule, $inputValue, $expectedValue, $status, $userId)
    {
        ValidationHistory::create([
            'validatable_type' => get_class($validatable),
            'validatable_id' => $validatable->getKey(),
            'rule_id' => $rule->id,
            'status' => $status,
            'input_value' => is_scalar($inputValue) ? $inputValue : json_encode($inputValue),
            'expected_value' => is_scalar($expectedValue) ? $expectedValue : json_encode($expectedValue),
            'user_id' => $userId,
        ]);
    }

    public function resolveExpectedValue($source, Model $context)
    {
        if (empty($source)) {
            return null;
        }

        // If the source exists as an attribute on the context model (e.g. total_amount or another column)
        if ($context->offsetExists($source)) {
            return $context->getAttribute($source);
        }

        // Check inside dynamic fields if the context is a PurchaseItem
        if (isset($context->dynamic_fields_json) && is_array($context->dynamic_fields_json) && array_key_exists($source, $context->dynamic_fields_json)) {
            return $context->dynamic_fields_json[$source];
        }

        return $source; 
    }

    public function evaluate($input, $expected, $operator, $tolerance): bool
    {
        if ($operator === 'equals') {
            return $input == $expected;
        }

        if ($operator === 'tolerance' && is_numeric($input) && is_numeric($expected)) {
            return abs($input - $expected) <= $tolerance;
        }

        if ($operator === 'greater_than' && is_numeric($input) && is_numeric($expected)) {
            return $input > $expected;
        }

        if ($operator === 'less_than' && is_numeric($input) && is_numeric($expected)) {
            return $input < $expected;
        }

        return false;
    }
}
