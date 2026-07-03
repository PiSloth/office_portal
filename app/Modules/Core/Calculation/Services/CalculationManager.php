<?php

namespace App\Modules\Core\Calculation\Services;

use App\Modules\Core\Calculation\Models\CalculationMethod;
use App\Modules\Core\Calculation\Models\CalculationHistory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class CalculationManager
{
    /**
     * Execute a calculation method with the given inputs.
     */
    public function calculate(Model $calculatable, CalculationMethod $method, array $inputs, $userId = null)
    {
        $parameters = $method->parameters()->pluck('value', 'key')->toArray();
        $className = $method->php_class_name;

        if (!class_exists($className)) {
            throw new Exception("Calculator class {$className} not found.");
        }

        $calculator = app($className);
        
        if (!method_exists($calculator, 'calculate')) {
            throw new Exception("Calculator class {$className} must implement calculate() method.");
        }

        // Execute the calculation strategy
        $totalAmount = $calculator->calculate($inputs, $parameters);

        // Snapshot the result
        return CalculationHistory::create([
            'calculatable_type' => get_class($calculatable),
            'calculatable_id' => $calculatable->getKey(),
            'parameter_snapshot_json' => $parameters,
            'input_snapshot_json' => $inputs,
            'total_amount' => $totalAmount,
            'user_id' => $userId,
        ]);
    }
}
