<?php

namespace Tests\Feature;

use App\Exceptions\PromotionImportValidationException;
use App\Models\Branch;
use App\Models\ProductCategoryMultiplier;
use App\Models\PromotionItem;
use App\Models\User;
use App\Services\PromotionItemCategoryUpdateService;
use App\Services\PromotionItemImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PromotionItemTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'promotion.import', 'guard_name' => 'web']);
    }

    public function test_promotions_index_page_is_accessible_by_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/promotions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Promotions/Index')
            ->has('items.data')
            ->has('filters')
            ->has('productCategories')
        );
    }

    public function test_import_service_rejects_import_if_product_category_is_missing(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Test Main Branch'], ['code' => 'TMB01']);
        $barcode = 'TEST_NO_CAT_' . uniqid();
        $lot = 'LOT_NC_' . uniqid();

        // CSV without product category column or empty category value
        $csvContent = implode("\n", [
            'Barcode,Product,Weight(g),Gold Weight(g),D/G Weight(g),Addition Wastage(g),Total Wastage(g),Labor Fee(s),Internal Reference,Lot/Serial Number,Product/Design Subgroup/Name,Branch,Product Category',
            "{$barcode},Test Ring,0.98,0.98,0,0.18,0.39,0,{$barcode},{$lot},Gold Ring,{$branch->name},",
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'promo_test_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $uploadedFile = new UploadedFile($tempPath, 'promotions.csv', 'text/csv', null, true);

        $service = new PromotionItemImportService();

        try {
            $service->import($uploadedFile);
            $this->fail('Expected PromotionImportValidationException was not thrown for missing category.');
        } catch (PromotionImportValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
            $this->assertStringContainsString('Product category is missing', $e->getErrors()[0]['error']);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function test_import_service_auto_creates_product_category_and_references_id(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Test Main Branch'], ['code' => 'TMB01']);
        $bar1 = 'TEST_AC_1_' . uniqid();
        $lot1 = 'LOT_AC_1_' . uniqid();
        $bar2 = 'TEST_AC_2_' . uniqid();
        $lot2 = 'LOT_AC_2_' . uniqid();
        $catName1 = '999_' . uniqid();
        $catName2 = '18K_' . uniqid();

        $csvContent = implode("\n", [
            'Barcode,Product,Weight(g),Gold Weight(g),D/G Weight(g),Addition Wastage(g),Total Wastage(g),Labor Fee(s),Internal Reference,Lot/Serial Number,Product/Design Subgroup/Name,Branch,Product Category',
            "B1/Promotion (3),,3.29,3.29,0,0.52,1.08,0,,,{$branch->name},{$catName1}",
            "{$bar1},Ring 1,0.98,0.98,0,0.18,0.39,0,{$bar1},{$lot1},Gold Ring,{$branch->name},{$catName1}",
            "{$bar2},Ring 2,1.08,1.08,0,0.18,0.39,0,{$bar2},{$lot2},Gold Ring,{$branch->name},{$catName2}",
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'promo_test_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $uploadedFile = new UploadedFile($tempPath, 'promotions.csv', 'text/csv', null, true);

        $service = new PromotionItemImportService();
        $result = $service->import($uploadedFile);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(1, $result['skipped']);

        // Check category 999 and 18K were auto created in product_category_multipliers table
        $category1 = ProductCategoryMultiplier::where('name', $catName1)->first();
        $this->assertNotNull($category1);
        $this->assertEquals(1.0, (float) $category1->multiplier);

        $category2 = ProductCategoryMultiplier::where('name', $catName2)->first();
        $this->assertNotNull($category2);

        // Check promotion item references product_category_id
        $this->assertDatabaseHas('promotion_items', [
            'barcode' => $bar1,
            'product_category_id' => $category1->id,
        ]);

        $this->assertDatabaseHas('promotion_items', [
            'barcode' => $bar2,
            'product_category_id' => $category2->id,
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_category_multiplier_crud_operations(): void
    {
        $user = User::factory()->create();

        // 1. Create (Store)
        $storeResponse = $this->actingAs($user)->post('/promotions/categories', [
            'name' => '999 Gold',
            'multiplier' => '0.9990',
            'description' => '999 gold grade',
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('product_category_multipliers', [
            'name' => '999 Gold',
            'multiplier' => '0.999000',
        ]);

        $category = ProductCategoryMultiplier::where('name', '999 Gold')->first();

        // 2. Read (Index JSON)
        $indexResponse = $this->actingAs($user)->getJson('/promotions/categories');
        $indexResponse->assertStatus(200);
        $indexResponse->assertJsonFragment(['name' => '999 Gold']);

        // 3. Update
        $updateResponse = $this->actingAs($user)->put("/promotions/categories/{$category->id}", [
            'name' => '999 Ultra Gold',
            'multiplier' => '0.9985',
            'description' => 'Updated 999 description',
        ]);
        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('product_category_multipliers', [
            'id' => $category->id,
            'name' => '999 Ultra Gold',
            'multiplier' => '0.998500',
        ]);

        // 4. Delete
        $deleteResponse = $this->actingAs($user)->delete("/promotions/categories/{$category->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('product_category_multipliers', [
            'id' => $category->id,
        ]);
    }

    public function test_update_categories_from_excel_service(): void
    {
        $item = PromotionItem::create([
            'barcode' => 'ITEM_UPDATE_101',
            'lot_serial_number' => 'LOT_101',
            'product' => 'Gold Ring',
            'total_wastage' => 0.5,
        ]);

        $this->assertNull($item->product_category_id);

        // CSV to update category for this item
        $csvContent = implode("\n", [
            'Barcode,Lot/Serial Number,Product Category',
            'ITEM_UPDATE_101,LOT_101,999',
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'update_cat_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $uploadedFile = new UploadedFile($tempPath, 'update_categories.csv', 'text/csv', null, true);

        $service = new PromotionItemCategoryUpdateService();
        $result = $service->updateFromExcel($uploadedFile);

        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, $result['created_categories']);

        $category999 = ProductCategoryMultiplier::where('name', '999')->first();
        $this->assertNotNull($category999);

        $item->refresh();
        $this->assertEquals($category999->id, $item->product_category_id);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_update_categories_service_rejects_import_if_category_missing(): void
    {
        PromotionItem::create([
            'barcode' => 'ITEM_NO_CAT_202',
            'lot_serial_number' => 'LOT_202',
            'product' => 'Gold Pendant',
        ]);

        $csvContent = implode("\n", [
            'Barcode,Lot/Serial Number,Product Category',
            'ITEM_NO_CAT_202,LOT_202,',
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'update_cat_fail_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $uploadedFile = new UploadedFile($tempPath, 'update_categories.csv', 'text/csv', null, true);

        $service = new PromotionItemCategoryUpdateService();

        try {
            $service->updateFromExcel($uploadedFile);
            $this->fail('Expected PromotionImportValidationException was not thrown for missing category in update import.');
        } catch (PromotionImportValidationException $e) {
            $this->assertNotEmpty($e->getErrors());
            $this->assertStringContainsString('Product category is missing', $e->getErrors()[0]['error']);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function test_search_by_product_category_name(): void
    {
        $user = User::factory()->create();

        $cat999 = ProductCategoryMultiplier::create(['name' => '999 Platinum', 'multiplier' => 0.999]);
        $cat18k = ProductCategoryMultiplier::create(['name' => '18K White', 'multiplier' => 0.750]);

        PromotionItem::create([
            'barcode' => 'SEARCH_CAT_1',
            'product' => 'Special Ring',
            'product_category_id' => $cat999->id,
        ]);

        PromotionItem::create([
            'barcode' => 'SEARCH_CAT_2',
            'product' => 'Normal Ring',
            'product_category_id' => $cat18k->id,
        ]);

        $response = $this->actingAs($user)->get('/promotions?search=Platinum');
        $response->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.barcode', 'SEARCH_CAT_1')
        );
    }

    public function test_category_multiplier_effects_promotion_price_formula(): void
    {
        $user = User::factory()->create();

        // Formula: (total wastage / 2) * ((gold price * multiplier) / 16.606)
        $cat999 = ProductCategoryMultiplier::create([
            'name' => '999 Pure',
            'multiplier' => 0.9990,
        ]);

        $item = PromotionItem::create([
            'barcode' => 'TEST_FORMULA_' . uniqid(),
            'product' => '999 Ring',
            'total_wastage' => 1.0, // wastage / 2 = 0.5
            'addition_wastage' => null,
            'labor_fee' => 0,
            'product_category_id' => $cat999->id,
        ]);

        $goldPrice = 7000000;
        $multiplier = 0.9990;
        $calculatedRaw = (1.0 / 2) * (($goldPrice * $multiplier) / 16.606);
        $roundedTens = floor($calculatedRaw / 10) * 10; // 210550

        // Store sold item at this calculated promotion price
        $response = $this->actingAs($user)->post('/promotions/sold', [
            'reference_number' => 'VOUCH_999_' . uniqid(),
            'items' => [
                [
                    'id' => $item->id,
                    'promotion_price' => $roundedTens,
                ]
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('promotion_sold_items', [
            'promotion_item_id' => $item->id,
            'promotion_price' => $roundedTens,
        ]);
    }

    public function test_import_service_updates_existing_promotion_item(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Test Branch Alpha'], ['code' => 'TBA01']);
        $cat1 = ProductCategoryMultiplier::create(['name' => 'CatOld_' . uniqid(), 'multiplier' => 1.0]);
        $cat2 = ProductCategoryMultiplier::create(['name' => 'CatNew_' . uniqid(), 'multiplier' => 0.95]);

        $barcode = 'EXISTING_BAR_' . uniqid();
        $lot = 'EXISTING_LOT_' . uniqid();

        $existingItem = PromotionItem::create([
            'barcode' => $barcode,
            'lot_serial_number' => $lot,
            'product' => 'Old Product Name',
            'weight' => 1.0,
            'gold_weight' => 1.0,
            'total_wastage' => 0.2,
            'branch_id' => $branch->id,
            'product_category_id' => $cat1->id,
        ]);

        // CSV with updated values for the same barcode
        $csvContent = implode("\n", [
            'Barcode,Product,Weight(g),Gold Weight(g),D/G Weight(g),Addition Wastage(g),Total Wastage(g),Labor Fee(s),Internal Reference,Lot/Serial Number,Product/Design Subgroup/Name,Branch,Product Category',
            "{$barcode},Updated Product Name,2.5,2.5,0,0.1,0.6,500,REF123,{$lot},New Subgroup,{$branch->name},{$cat2->name}",
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'update_existing_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $uploadedFile = new UploadedFile($tempPath, 'promotions.csv', 'text/csv', null, true);

        $service = new PromotionItemImportService();

        // When updateExisting is false, duplicate should throw exception
        try {
            $service->import($uploadedFile, false);
            $this->fail('Expected duplicate barcode error when updateExisting is false.');
        } catch (PromotionImportValidationException $e) {
            $this->assertStringContainsString('Duplicate barcode', $e->getErrors()[0]['error']);
        }

        // When updateExisting is true, it should update the item!
        $uploadedFile2 = new UploadedFile($tempPath, 'promotions.csv', 'text/csv', null, true);
        $result = $service->import($uploadedFile2, true);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['updated']);

        $existingItem->refresh();
        $this->assertEquals('Updated Product Name', $existingItem->product);
        $this->assertEquals('2.50000000', $existingItem->weight);
        $this->assertEquals('0.60000000', $existingItem->total_wastage);
        $this->assertEquals($cat2->id, $existingItem->product_category_id);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}
