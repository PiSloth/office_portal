<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Purchase\Models\PurchaseRequest;
use App\Modules\Purchase\Models\PurchaseItem;

class PurchasePrintViewTest extends TestCase
{
    public function test_purchase_print_view_renders_with_kyauk_weight()
    {
        $request = new PurchaseRequest();
        $request->id = 1;
        $request->purchase_number = 'PR-TEST/001';
        $request->customer_name = 'U Ba';
        $request->customer_phone = '0912345678';
        $request->created_at = now();

        $itemWithKyauk = new PurchaseItem();
        $itemWithKyauk->id = 1;
        $itemWithKyauk->calculated_price = 500000;
        $itemWithKyauk->dynamic_fields_json = [
            'product_name' => 'Diamond Ring',
            'goldList' => '16',
            'goldWeightGram' => 10.0,
            'kyaukWeight' => 4, // 4 yawe: 4/128 * 16.606 = 0.5189375 g
            'quantity' => 1,
            'is_good' => true,
        ];
        $itemWithKyauk->setRelation('validationHistories', collect([]));

        $itemWithoutKyauk = new PurchaseItem();
        $itemWithoutKyauk->id = 2;
        $itemWithoutKyauk->calculated_price = 300000;
        $itemWithoutKyauk->dynamic_fields_json = [
            'product_name' => 'Plain Ring',
            'goldList' => '16',
            'goldWeightGram' => 5.5,
            'kyaukWeight' => 0,
            'quantity' => 1,
            'is_good' => true,
        ];
        $itemWithoutKyauk->setRelation('validationHistories', collect([]));

        $request->setRelation('items', collect([$itemWithKyauk, $itemWithoutKyauk]));

        $html = view('purchase-print', ['record' => $request])->render();

        $this->assertStringContainsString('Diamond Ring', $html);
        $this->assertStringContainsString('Net: 9.4811 g', $html);
        $this->assertStringContainsString('ကျောက်: 0.5189 g', $html);

        // Plain ring should show original weight
        $this->assertStringContainsString('Plain Ring (5.5 g)', $html);
    }

    public function test_purchase_print_view_net_weight_and_kpy_matching_calculation_step_modal()
    {
        $request = new PurchaseRequest();
        $request->id = 2;
        $request->purchase_number = 'PR-B5/260920044';
        $request->customer_name = 'Customer';
        $request->created_at = now();

        $item = new PurchaseItem();
        $item->id = 10;
        $item->calculated_price = 653700;
        $item->dynamic_fields_json = [
            'product_name' => 'ပုံဆန်းကျောက်နားကပ်',
            'goldList' => '14.2',
            'purchase_type' => 'gb_product',
            'kyat' => 0,
            'pae' => 1,
            'yawe' => 2.483,
            'goldWeightGram' => 1.36,
            'kyaukWeight' => 1.3,
            'quantity' => 1,
            'is_good' => false,
            'remark' => 'ပုံဆန်းကျောက်နားကပ် 1pcs',
        ];
        $item->setRelation('validationHistories', collect([]));

        $request->setRelation('items', collect([$item]));

        $html = view('purchase-print', ['record' => $request])->render();

        $this->assertStringContainsString('ပုံဆန်းကျောက်နားကပ်', $html);
        $this->assertStringContainsString('Net: 1.1914 g', $html);
        $this->assertStringContainsString('ကျောက်: 0.1687 g', $html);
        $this->assertStringContainsString('0ကျပ် 1ပဲ 1.18ရွေး', $html);
    }
}
