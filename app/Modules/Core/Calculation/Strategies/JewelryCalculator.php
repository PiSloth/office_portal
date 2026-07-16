<?php

namespace App\Modules\Core\Calculation\Strategies;

use App\Modules\Core\Calculation\Contracts\CalculatorContract;

class JewelryCalculator implements CalculatorContract
{
    public function calculate(array $inputs, array $parameters): array
    {
        // 1. Extract inputs
        $reChange = $inputs['reChange'] ?? false; // Is Trade-in?
        $percentDeduction = ((float) ($inputs['percent'] ?? 0)) / 100;

        if ((string)$reChange === '2') {
            $originalVoucherPrice = (float) ($inputs['original_voucher_price'] ?? 0);
            $finalPrice = $originalVoucherPrice - ($originalVoucherPrice * $percentDeduction);

            return [
                'status' => 'success',
                'result' => floor($finalPrice / 100) * 100,
                'message' => 'Percent ထည်ပြန်ဝယ်',
                'details' => [
                    'total_weight' => 0,
                    'price_before_deduction' => $originalVoucherPrice,
                ]
            ];
        }

        $goldList = (float) ($inputs['goldList'] ?? 16);
        $purchaseType = $inputs['purchase_type'] ?? 'gb_product';

        $kyat = (float) ($inputs['kyat'] ?? 0);
        $pae = (float) ($inputs['pae'] ?? 0);
        $yawe = (float) ($inputs['yawe'] ?? 0);

        $kyaukWeightYawe = (float) ($inputs['kyaukWeight'] ?? 0);
        $goldWeightGram = (float) ($inputs['goldWeightGram'] ?? 0);

        // 2. Extract parameters (multipliers from DB)
        $goldPrice = (float) ($parameters['base_gold_price'] ?? 0);
        $tax = (float) ($parameters['tax_rate'] ?? 0); // e.g. "ခွာဈေး" deduction

        // 3. Trade-in Logic (If not trade-in, deduct tax/charges from base price)
        if (!$reChange) {
            $goldPrice -= $tax;
        }

        // 4. Calculate total weight
        $gramPerKyat = (float) ($parameters['gram_per_kyat'] ?? 16.3293);
        if ($goldList == 12 && $purchaseType === 'gb_product') {
            // For 12pae GB product, weight is in Grams directly
            $totalWeight = $goldWeightGram > 0 ? ($goldWeightGram / $gramPerKyat) : (($kyat * 1) + ($pae / 16) + ($yawe / 128));
            $kyaukWeightKyat = $kyaukWeightYawe / 128;
            $totalWeight -= $kyaukWeightKyat;
        } else {
            if ($goldWeightGram > 0 && $kyat == 0 && $pae == 0 && $yawe == 0) {
                $totalWeight = $goldWeightGram / $gramPerKyat;
            } else {
                $totalWeight = ($kyat * 1) + ($pae / 16) + ($yawe / 128);
            }
            $kyaukWeightKyat = $kyaukWeightYawe / 128;
            $totalWeight -= $kyaukWeightKyat;
        }

        // 5. Apply Multiplier Based on Gold Grade & Purchase Type
        $multiplierPrefix = $purchaseType === 'other_product' ? 'multiplier_oth_' : 'multiplier_gb_';
        $multiplierKey = $multiplierPrefix . $goldList;
        if ($goldList == 142) {
            $multiplierKey = $multiplierPrefix . '14.2';
        }

        // Resolve default multipliers
        if ($purchaseType === 'other_product') {
            $defaultMultipliers = [
                '16' => 0.954,
                '15' => 0.8962,
                '14.2' => 0.8693,
                '142' => 0.8693,
                '14' => 0.8439,
                '13' => (15.35 / 332.12) * $gramPerKyat,
                '12' => (14.1 / 332.12) * $gramPerKyat,
                '10' => (11.6 / 332.12) * $gramPerKyat,
                '8' => (9.1 / 332.12) * $gramPerKyat,
                '4' => (4.1 / 332.12) * $gramPerKyat,
            ];
        } else {
            $defaultMultipliers = [
                '16' => 1.0,
                '15' => 16 / 17,
                '14.2' => 128 / 140,
                '142' => 128 / 140,
                '14' => 16 / 18,
                '13' => 0.7522,
                '12' => 0.75,
            ];
        }

        $multiplier = isset($inputs['multiplier']) && (float) $inputs['multiplier'] > 0
            ? (float) $inputs['multiplier']
            : (isset($parameters[$multiplierKey])
                ? (float) $parameters[$multiplierKey]
                : ($defaultMultipliers[(string) $goldList] ?? 0.0));

        $priceBeforeDeduction = $goldPrice * $multiplier * $totalWeight;

        // 6. Apply percentage deduction
        $finalPrice = $priceBeforeDeduction;
        if ($percentDeduction > 0) {
            $finalPrice = $finalPrice - ($finalPrice * $percentDeduction);
        }

        // 7. Quantity is for reference only (does not affect price)
        $quantity = (int) ($inputs['quantity'] ?? 1);
        if ($quantity < 1) {
            $quantity = 1;
        }

        return [
            'status' => 'success',
            'result' => floor($finalPrice / 100) * 100,
            'message' => $reChange ? 'အလဲအထပ်ထည်' : 'ဆိုင်ထည်',
            'details' => [
                'total_weight' => $totalWeight,
                'price_before_deduction' => $priceBeforeDeduction,
            ]
        ];
    }
}
