<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Modules\Core\Calculation\Strategies\JewelryCalculator;

class JewelryCalculatorTest extends TestCase
{
    public function test_kyauk_weight_converts_to_gram_and_deducts_from_gold_weight_gram()
    {
        $calculator = new JewelryCalculator();

        $parameters = [
            'base_gold_price' => 3000000,
            'tax_rate' => 0,
            'gram_per_kyat' => 16.606,
            'multiplier_gb_16' => 1.0,
        ];

        // 1 Kyat gross weight (16.606g), 64 Ywe stone weight (half Kyat = 8.303g)
        $inputs = [
            'goldList' => 16,
            'purchase_type' => 'gb_product',
            'kyat' => 1,
            'pae' => 0,
            'yawe' => 0,
            'kyaukWeight' => 64, // 64 Ywe = 0.5 Kyat stone
            'goldWeightGram' => 8.303, // Net gold weight gram = 16.606 - 8.303 = 8.303g
            'percent' => 0,
            'reChange' => '0',
            'quantity' => 1,
        ];

        $result = $calculator->calculate($inputs, $parameters);

        $this::assertEquals('success', $result['status']);
        // Net total weight should be 0.5 Kyat
        $this::assertEquals(0.5, round($result['details']['total_weight'], 4));
        // Price before deduction should be 3000000 * 1.0 * 0.5 = 1500000
        $this::assertEquals(1500000, $result['result']);
    }

    public function test_kyauk_weight_deduction_when_only_gold_weight_gram_is_provided()
    {
        $calculator = new JewelryCalculator();

        $parameters = [
            'base_gold_price' => 3000000,
            'tax_rate' => 0,
            'gram_per_kyat' => 16.606,
            'multiplier_gb_16' => 1.0,
        ];

        // Gross gold weight = 16.606g (1 Kyat), Kyauk Weight = 128 Ywe (1 Kyat)
        // Net weight should be 0
        $inputs = [
            'goldList' => 16,
            'purchase_type' => 'gb_product',
            'kyat' => 0,
            'pae' => 0,
            'yawe' => 0,
            'kyaukWeight' => 128,
            'goldWeightGram' => 16.606,
            'percent' => 0,
            'reChange' => '0',
            'quantity' => 1,
        ];

        $result = $calculator->calculate($inputs, $parameters);

        $this::assertEquals('success', $result['status']);
        $this::assertEquals(0, round($result['details']['total_weight'], 4));
        $this::assertEquals(0, $result['result']);
    }
}
