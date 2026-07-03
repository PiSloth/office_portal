<?php

namespace App\Modules\Core\Calculation\Contracts;

interface CalculatorContract
{
    /**
     * Calculate the value based on inputs and parameters.
     *
     * @param array $inputs Inputs from the user (e.g. weight, type)
     * @param array $parameters Business rules/multipliers from the database
     * @return array Returns an array with ['status', 'result', 'message', 'details']
     */
    public function calculate(array $inputs, array $parameters): array;
}
