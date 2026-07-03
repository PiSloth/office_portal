<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Core\Calculation\Models\CalculationMethod;
use App\Modules\Core\Calculation\Models\CalculationParameter;

class MultiplierSeeder extends Seeder
{
    public function run(): void
    {
        $calcMethod = CalculationMethod::where('name', 'Standard Jewelry Calculator')->first();
        if (!$calcMethod) {
            return;
        }

        $parameters = [
            // GB Multipliers
            'multiplier_gb_16' => '1.0',
            'multiplier_gb_15' => '0.941176', // 16/17
            'multiplier_gb_14.2' => '0.914286', // 128/140
            'multiplier_gb_14' => '0.888889', // 16/18
            'multiplier_gb_13' => '0.7522',
            'multiplier_gb_12' => '0.75',

            // Other Multipliers
            'multiplier_oth_16' => '0.954',
            'multiplier_oth_15' => '0.8962',
            'multiplier_oth_14.2' => '0.8693',
            'multiplier_oth_14' => '0.8439',
            'multiplier_oth_13' => '0.767513',
            'multiplier_oth_12' => '0.705001',
            'multiplier_oth_10' => '0.580007',
            'multiplier_oth_8' => '0.455013',
            'multiplier_oth_4' => '0.205022',
            
            // Standard parameters
            'gram_per_kyat' => '16.606',
        ];

        foreach ($parameters as $key => $value) {
            CalculationParameter::updateOrCreate(
                [
                    'method_id' => $calcMethod->id,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'type' => 'numeric',
                ]
            );
        }
    }
}
