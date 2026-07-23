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
            $calculatedResult = floor($finalPrice / 100) * 100;

            return [
                'status' => 'success',
                'result' => max(0, $calculatedResult),
                'message' => 'Percent ထည်ပြန်ဝယ်',
                'details' => [
                    'total_weight' => 0,
                    'price_before_deduction' => $originalVoucherPrice,
                ]
            ];
        }

        if ((string)$reChange === '3') {
            $originalVoucherPrice = (float) ($inputs['original_voucher_price'] ?? 0);
            $plusPercent = (float) ($inputs['percent'] ?? 0);
            $finalPrice = $originalVoucherPrice + ($originalVoucherPrice * ($plusPercent / 100));
            $calculatedResult = floor($finalPrice / 100) * 100;

            return [
                'status' => 'success',
                'result' => max(0, $calculatedResult),
                'message' => 'စိန်ထည်ပြန်ဝယ်',
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
        $kyaukWeightGram = ($kyaukWeightYawe / 128) * $gramPerKyat;

        if ($goldList == 12 && $purchaseType === 'gb_product') {
            // For 12pae GB product, weight is in Grams directly
            $grossGram = $goldWeightGram > 0 ? $goldWeightGram : ((($kyat * 1) + ($pae / 16) + ($yawe / 128)) * $gramPerKyat);
            $netGram = max(0, $grossGram - $kyaukWeightGram);
            $totalWeight = $netGram / $gramPerKyat;
        } else {
            if ($goldWeightGram > 0 && $kyat == 0 && $pae == 0 && $yawe == 0) {
                $grossGram = $goldWeightGram;
            } else {
                $grossGram = (($kyat * 1) + ($pae / 16) + ($yawe / 128)) * $gramPerKyat;
            }
            $netGram = max(0, $grossGram - $kyaukWeightGram);
            $totalWeight = $netGram / $gramPerKyat;
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

        $calculatedResult = floor($finalPrice / 100) * 100;

        return [
            'status' => 'success',
            'result' => max(0, $calculatedResult),
            'message' => $reChange ? 'အလဲအထပ်ထည်' : 'ဆိုင်ထည်',
            'details' => [
                'total_weight' => $totalWeight,
                'price_before_deduction' => $priceBeforeDeduction,
            ]
        ];
    }

    /**
     * Generate step-by-step calculation breakdown data and HTML for verification.
     */
    public static function renderStepsHtml(array $inputs, array $parameters): string
    {
        $reChange = (string) ($inputs['reChange'] ?? '0');
        $percent = (float) ($inputs['percent'] ?? 0);
        $percentDeduction = $percent / 100;
        $productName = e($inputs['product_name'] ?? 'Product Line');
        $purchaseType = $inputs['purchase_type'] ?? 'gb_product';
        $purchaseTypeName = $purchaseType === 'other_product' ? 'အခြားဆိုင်ထည် (Other Product)' : 'ဆိုင်ထည် (GB Product)';

        $gramPerKyat = (float) ($parameters['gram_per_kyat'] ?? 16.606);
        if ($gramPerKyat <= 0) $gramPerKyat = 16.606;

        $baseGoldPrice = (float) ($parameters['base_gold_price'] ?? 0);
        $taxRate = (float) ($parameters['tax_rate'] ?? 0);

        if ($reChange === '2') {
            // Percent Buyback logic
            $originalVoucherPrice = (float) ($inputs['original_voucher_price'] ?? 0);
            $deductionAmount = $originalVoucherPrice * $percentDeduction;
            $priceBeforeRounding = $originalVoucherPrice - $deductionAmount;
            $finalPrice = max(0, floor($priceBeforeRounding / 100) * 100);

            $html = '<div style="font-family: system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.5; font-size: 0.875rem;">';
            
            // Summary Header Card
            $html .= '<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
            $html .= '<div>';
            $html .= '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #15803d;">Percent Buyback Breakdown</div>';
            $html .= '<div style="font-size: 1.125rem; font-weight: 800; color: #14532d; margin-top: 2px;">' . $productName . '</div>';
            $html .= '</div>';
            $html .= '<div style="text-align: right;">';
            $html .= '<div style="font-size: 0.75rem; color: #166534; font-weight: 600;">Calculated Final Price</div>';
            $html .= '<div style="font-size: 1.35rem; font-weight: 900; color: #15803d;">' . number_format($finalPrice) . ' MMK</div>';
            $html .= '</div>';
            $html .= '</div>';

            // Steps
            $html .= '<div style="display: grid; gap: 10px;">';

            // Step 1
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 1</span> <span style="font-weight: 700; color: #0f172a;">Original Voucher Price Baseline</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Original Voucher Price = <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($originalVoucherPrice) . ' MMK</span></div>';
            $html .= '</div>';

            // Step 2
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 2</span> <span style="font-weight: 700; color: #0f172a;">Percent Deduction (' . $percent . '%)</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Deduction Amount = ' . number_format($originalVoucherPrice) . ' × ' . $percent . '% = <span style="font-family: monospace; font-weight: 700; color: #dc2626;">-' . number_format($deductionAmount) . ' MMK</span></div>';
            $html .= '</div>';

            // Step 3
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 3</span> <span style="font-weight: 700; color: #0f172a;">Subtotal Price Before Rounding</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Subtotal = ' . number_format($originalVoucherPrice) . ' - ' . number_format($deductionAmount) . ' = <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($priceBeforeRounding) . ' MMK</span></div>';
            $html .= '</div>';

            // Step 4
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 4</span> <span style="font-weight: 700; color: #0f172a;">Rounding Floor 100 MMK</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Formula: floor(' . number_format($priceBeforeRounding) . ' / 100) × 100 = <span style="font-family: monospace; font-weight: 800; color: #059669; font-size: 0.9375rem;">' . number_format($finalPrice) . ' MMK</span></div>';
            $html .= '</div>';

            $html .= '</div>';

            // Verification Box
            $html .= '<div style="background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px; margin-top: 16px;">';
            $html .= '<div style="font-weight: 700; color: #78350f; font-size: 0.875rem; margin-bottom: 8px;">🧮 Check with Calculator (ဂဏန်းပေါင်းစက်ဖြင့် တိုက်စစ်ရန်)</div>';
            $html .= '<div style="background-color: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 12px; font-family: monospace; font-size: 0.75rem; color: #451a03; line-height: 1.6;">';
            $html .= '<div><strong>Formula:</strong> (Original Voucher Price) × (1 - Percent%)</div>';
            $html .= '<div><strong>Calculator Key-in:</strong> ' . number_format($originalVoucherPrice, 2, '.', '') . ' × ' . (1 - $percentDeduction) . ' = ' . number_format($priceBeforeRounding, 2, '.', '') . '</div>';
            $html .= '<div><strong>Final Floor(100):</strong> ' . number_format($finalPrice) . ' MMK</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
            return $html;
        }

        if ($reChange === '3') {
            // Diamond Buyback (စိန်ထည်ပြန်ဝယ်) logic
            $originalVoucherPrice = (float) ($inputs['original_voucher_price'] ?? 0);
            $plusPercent = (float) ($inputs['percent'] ?? 0);
            $additionAmount = $originalVoucherPrice * ($plusPercent / 100);
            $priceBeforeRounding = $originalVoucherPrice + $additionAmount;
            $finalPrice = max(0, floor($priceBeforeRounding / 100) * 100);

            $html = '<div style="font-family: system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.5; font-size: 0.875rem;">';
            
            // Summary Header Card
            $html .= '<div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
            $html .= '<div>';
            $html .= '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #b45309;">Diamond Buyback Breakdown (စိန်ထည်ပြန်ဝယ်)</div>';
            $html .= '<div style="font-size: 1.125rem; font-weight: 800; color: #78350f; margin-top: 2px;">' . $productName . '</div>';
            $html .= '</div>';
            $html .= '<div style="text-align: right;">';
            $html .= '<div style="font-size: 0.75rem; color: #92400e; font-weight: 600;">Calculated Final Price</div>';
            $html .= '<div style="font-size: 1.35rem; font-weight: 900; color: #b45309;">' . number_format($finalPrice) . ' MMK</div>';
            $html .= '</div>';
            $html .= '</div>';

            // Steps
            $html .= '<div style="display: grid; gap: 10px;">';

            // Step 1
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 1</span> <span style="font-weight: 700; color: #0f172a;">Original Voucher Price Baseline</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Original Voucher Price = <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($originalVoucherPrice) . ' MMK</span></div>';
            $html .= '</div>';

            // Step 2
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #fef3c7; color: #92400e; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 2</span> <span style="font-weight: 700; color: #0f172a;">Plus Percent Addition (+' . $plusPercent . '%)</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Addition Amount = ' . number_format($originalVoucherPrice) . ' × ' . $plusPercent . '% = <span style="font-family: monospace; font-weight: 700; color: #059669;">+' . number_format($additionAmount) . ' MMK</span></div>';
            $html .= '</div>';

            // Step 3
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 3</span> <span style="font-weight: 700; color: #0f172a;">Subtotal Price Before Rounding</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Subtotal = ' . number_format($originalVoucherPrice) . ' + ' . number_format($additionAmount) . ' = <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($priceBeforeRounding) . ' MMK</span></div>';
            $html .= '</div>';

            // Step 4
            $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;"><span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 4</span> <span style="font-weight: 700; color: #0f172a;">Rounding Floor 100 MMK</span></div>';
            $html .= '<div style="color: #334155; font-size: 0.8125rem;">Formula: floor(' . number_format($priceBeforeRounding) . ' / 100) × 100 = <span style="font-family: monospace; font-weight: 800; color: #059669; font-size: 0.9375rem;">' . number_format($finalPrice) . ' MMK</span></div>';
            $html .= '</div>';

            $html .= '</div>';

            // Verification Box
            $html .= '<div style="background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px; margin-top: 16px;">';
            $html .= '<div style="font-weight: 700; color: #78350f; font-size: 0.875rem; margin-bottom: 8px;">🧮 Check with Calculator (ဂဏန်းပေါင်းစက်ဖြင့် တိုက်စစ်ရန်)</div>';
            $html .= '<div style="background-color: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 12px; font-family: monospace; font-size: 0.75rem; color: #451a03; line-height: 1.6;">';
            $html .= '<div><strong>Formula:</strong> (Original Voucher Price) × (1 + PlusPercent%)</div>';
            $html .= '<div><strong>Calculator Key-in:</strong> ' . number_format($originalVoucherPrice, 2, '.', '') . ' × ' . (1 + ($plusPercent / 100)) . ' = ' . number_format($priceBeforeRounding, 2, '.', '') . '</div>';
            $html .= '<div><strong>Final Floor(100):</strong> ' . number_format($finalPrice) . ' MMK</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
            return $html;
        }

        // Standard Gold Grade Buyback
        $goldList = (string) ($inputs['goldList'] ?? '16');
        $kyat = (float) ($inputs['kyat'] ?? 0);
        $pae = (float) ($inputs['pae'] ?? 0);
        $yawe = (float) ($inputs['yawe'] ?? 0);
        $kyaukWeightYawe = (float) ($inputs['kyaukWeight'] ?? 0);
        $goldWeightGram = (float) ($inputs['goldWeightGram'] ?? 0);

        $isTradeIn = ($reChange === '1');
        $reChangeLabel = $isTradeIn ? 'အလဲအထပ် (Yes - No Khwa Zay Deduction)' : 'ဆိုင်ထည် (No - Khwa Zay Deducted)';
        $khwaZay = $isTradeIn ? 0.0 : $taxRate;
        $effectiveBasePrice = max(0, $baseGoldPrice - $khwaZay);

        $kyaukWeightGram = ($kyaukWeightYawe / 128) * $gramPerKyat;

        if ($goldList == '12' && $purchaseType === 'gb_product') {
            $grossGram = $goldWeightGram > 0 ? $goldWeightGram : ((($kyat * 1) + ($pae / 16) + ($yawe / 128)) * $gramPerKyat);
        } else {
            if ($goldWeightGram > 0 && $kyat == 0 && $pae == 0 && $yawe == 0) {
                $grossGram = $goldWeightGram;
            } else {
                $grossGram = (($kyat * 1) + ($pae / 16) + ($yawe / 128)) * $gramPerKyat;
            }
        }

        $netGram = max(0, $grossGram - $kyaukWeightGram);
        $totalWeightKyat = $netGram / $gramPerKyat;

        // Convert $totalWeightKyat into KPY
        $kKyat = floor($totalWeightKyat);
        $remKyat = $totalWeightKyat - $kKyat;
        $tPae = $remKyat * 16;
        $kPae = floor($tPae);
        $remPae = $tPae - $kPae;
        $kYawe = round($remPae * 8, 2);
        $kpyString = "{$kKyat}ကျပ် {$kPae}ပဲ {$kYawe}ရွေး";

        // Multiplier resolution
        $multiplierPrefix = $purchaseType === 'other_product' ? 'multiplier_oth_' : 'multiplier_gb_';
        $multiplierKey = $multiplierPrefix . $goldList;
        if ($goldList == '142') {
            $multiplierKey = $multiplierPrefix . '14.2';
        }

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

        $gradePricePerKyat = $effectiveBasePrice * $multiplier;
        $priceBeforeDeduction = $gradePricePerKyat * $totalWeightKyat;

        $percentDeductionAmount = $priceBeforeDeduction * $percentDeduction;
        $subtotalAfterDeduction = $priceBeforeDeduction - $percentDeductionAmount;
        $finalCalculatedPrice = max(0, floor($subtotalAfterDeduction / 100) * 100);

        $html = '<div style="font-family: system-ui, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.5; font-size: 0.875rem;">';

        // Summary Header Card
        $html .= '<div style="background-color: #eef2ff; border: 1px solid #c7d2fe; border-radius: 12px; padding: 16px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">';
        $html .= '<div>';
        $html .= '<div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #4338ca;">' . e($purchaseTypeName) . ' • Grade ' . e($goldList) . ' ပဲ</div>';
        $html .= '<div style="font-size: 1.125rem; font-weight: 800; color: #1e1b4b; margin-top: 2px;">' . $productName . '</div>';
        $html .= '<div style="font-size: 0.75rem; color: #3730a3; margin-top: 2px;">Net Weight: <strong>' . round($netGram, 4) . ' g</strong> (' . $kpyString . ')</div>';
        $html .= '</div>';
        $html .= '<div style="text-align: right;">';
        $html .= '<div style="font-size: 0.75rem; color: #3730a3; font-weight: 600;">Calculated Final Price</div>';
        $html .= '<div style="font-size: 1.35rem; font-weight: 900; color: #4338ca;">' . number_format($finalCalculatedPrice) . ' MMK</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Steps Grid
        $html .= '<div style="display: grid; gap: 10px;">';

        // Step 1
        $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
        $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 1</span> <span style="font-weight: 700; color: #0f172a;">Weight & Net Gold Gram Conversion (အလေးချိန်တွက်ချက်ခြင်း)</span></div>';
        $html .= '<div style="color: #334155; font-size: 0.8125rem; line-height: 1.6;">';
        $html .= '<div>• Conversion Standard: <span style="font-family: monospace; font-weight: 700;">1 ကျပ် = ' . $gramPerKyat . ' g</span></div>';
        $html .= '<div>• Gross Weight: <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . round($grossGram, 4) . ' g</span></div>';
        if ($kyaukWeightYawe > 0) {
            $html .= '<div>• Stone Deduction (ကျောက်ချိန် ' . $kyaukWeightYawe . ' ရွေး): <span style="font-family: monospace; font-weight: 700; color: #dc2626;">-' . round($kyaukWeightGram, 4) . ' g</span></div>';
        }
        $html .= '<div>• Net Gold Weight: <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . round($netGram, 4) . ' g</span> ÷ ' . $gramPerKyat . ' = <span style="font-family: monospace; font-weight: 800; color: #4f46e5;">' . round($totalWeightKyat, 6) . ' ကျပ်</span> (' . $kpyString . ')</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Step 2
        $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
        $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 2</span> <span style="font-weight: 700; color: #0f172a;">Base Gold Price & Khwa Zay Adjustment (အခြေခံရွှေဈေး နှင့် ခွာဈေး)</span></div>';
        $html .= '<div style="color: #334155; font-size: 0.8125rem; line-height: 1.6;">';
        $html .= '<div>• Market Base Gold Price: <span style="font-family: monospace; font-weight: 700;">' . number_format($baseGoldPrice) . ' MMK / ကျပ်</span></div>';
        $html .= '<div>• Mode: <strong>' . $reChangeLabel . '</strong></div>';
        if ($isTradeIn) {
            $html .= '<div>• Khwa Zay Deduction (ခွာဈေး): <span style="font-family: monospace; font-weight: 700; color: #059669;">0 MMK (အလဲအထပ်ဖြစ်၍ မနှုတ်ပါ)</span></div>';
        } else {
            $html .= '<div>• Khwa Zay Deduction (ခွာဈေး): <span style="font-family: monospace; font-weight: 700; color: #dc2626;">-' . number_format($taxRate) . ' MMK / ကျပ်</span></div>';
        }
        $html .= '<div>• Effective Base Price: ' . number_format($baseGoldPrice) . ' - ' . number_format($khwaZay) . ' = <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($effectiveBasePrice) . ' MMK / ကျပ်</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        // Step 3
        $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
        $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 3</span> <span style="font-weight: 700; color: #0f172a;">Gold Grade Multiplier (ပဲရည် မြှောက်ကိန်း)</span></div>';
        $html .= '<div style="color: #334155; font-size: 0.8125rem; line-height: 1.6;">';
        $html .= '<div>• Grade: <strong>' . e($goldList) . ' ပဲ</strong> (' . e($purchaseTypeName) . ')</div>';
        $html .= '<div>• Multiplier Ratio: <span style="font-family: monospace; font-weight: 700; color: #4f46e5;">' . round($multiplier, 6) . '</span></div>';
        $html .= '<div>• Grade Adjusted Price per Kyat: ' . number_format($effectiveBasePrice) . ' × ' . round($multiplier, 6) . ' = <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($gradePricePerKyat, 2) . ' MMK / ကျပ်</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        // Step 4
        $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
        $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 4</span> <span style="font-weight: 700; color: #0f172a;">Price Before Percent Deduction (အလျော့မနှုတ်မီ ပမာဏ)</span></div>';
        $html .= '<div style="color: #334155; font-size: 0.8125rem; line-height: 1.6;">';
        $html .= '<div>• Formula: Grade Price per Kyat × Net Weight (Kyat)</div>';
        $html .= '<div>• Calculation: ' . number_format($gradePricePerKyat, 2) . ' MMK × ' . round($totalWeightKyat, 6) . ' ကျပ် = <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($priceBeforeDeduction, 2) . ' MMK</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        // Step 5
        $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
        $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;"><span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 5</span> <span style="font-weight: 700; color: #0f172a;">Percent Deduction (ရာခိုင်နှုန်း အလျော့ ' . $percent . '%)</span></div>';
        $html .= '<div style="color: #334155; font-size: 0.8125rem; line-height: 1.6;">';
        $html .= '<div>• Deduction Amount: ' . number_format($priceBeforeDeduction, 2) . ' MMK × ' . $percent . '% = <span style="font-family: monospace; font-weight: 700; color: #dc2626;">-' . number_format($percentDeductionAmount, 2) . ' MMK</span></div>';
        $html .= '<div>• Subtotal After Percent Deduction: <span style="font-family: monospace; font-weight: 700; color: #0f172a;">' . number_format($subtotalAfterDeduction, 2) . ' MMK</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        // Step 6
        $html .= '<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">';
        $html .= '<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;"><span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Step 6</span> <span style="font-weight: 700; color: #0f172a;">Rounding Floor 100 MMK (ရာဂဏန်း ဖြတ်တောက်ခြင်း)</span></div>';
        $html .= '<div style="color: #334155; font-size: 0.8125rem; line-height: 1.6;">';
        $html .= '<div>• Formula: floor(' . number_format($subtotalAfterDeduction, 2) . ' / 100) × 100</div>';
        $html .= '<div>• Final Calculated Price: <span style="font-family: monospace; font-weight: 800; color: #059669; font-size: 0.9375rem;">' . number_format($finalCalculatedPrice) . ' MMK</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        // Verification Box
        $html .= '<div style="background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px; margin-top: 16px;">';
        $html .= '<div style="font-weight: 700; color: #78350f; font-size: 0.875rem; margin-bottom: 8px;">🧮 Check with Calculator (ဂဏန်းပေါင်းစက်ဖြင့် တိုက်စစ်ရန်)</div>';
        $html .= '<div style="background-color: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 12px; font-family: monospace; font-size: 0.75rem; color: #451a03; line-height: 1.6;">';
        $html .= '<div><strong>Formula:</strong> [ ( Base Price - Khwa Zay ) × Multiplier ] × [ Net Gram / Gram Per Kyat ] × [ 1 - Percent% ]</div>';
        $html .= '<div style="margin-top: 4px; font-weight: 700; color: #92400e;">Calculator Key-in Steps:</div>';
        $html .= '<div>1) Effective Base Price = ' . number_format($baseGoldPrice, 0, '.', '') . ' - ' . number_format($khwaZay, 0, '.', '') . ' = ' . number_format($effectiveBasePrice, 0, '.', '') . '</div>';
        $html .= '<div>2) Grade Price per Kyat = ' . number_format($effectiveBasePrice, 0, '.', '') . ' × ' . round($multiplier, 6) . ' = ' . number_format($gradePricePerKyat, 4, '.', '') . '</div>';
        $html .= '<div>3) Weight in Kyat = ' . round($netGram, 4) . ' ÷ ' . $gramPerKyat . ' = ' . round($totalWeightKyat, 6) . '</div>';
        $html .= '<div>4) Base Amount = ' . number_format($gradePricePerKyat, 4, '.', '') . ' × ' . round($totalWeightKyat, 6) . ' = ' . number_format($priceBeforeDeduction, 2, '.', '') . '</div>';
        if ($percent > 0) {
            $html .= '<div>5) After ' . $percent . '% deduction = ' . number_format($priceBeforeDeduction, 2, '.', '') . ' × ' . (1 - $percentDeduction) . ' = ' . number_format($subtotalAfterDeduction, 2, '.', '') . '</div>';
        }
        $html .= '<div>6) Final Floor(100) = <strong style="color: #047857; font-size: 0.8125rem;">' . number_format($finalCalculatedPrice) . ' MMK</strong></div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }
}
