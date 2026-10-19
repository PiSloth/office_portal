<?php

namespace Tests\Feature;

use App\Models\PromotionItem;
use App\Models\User;
use App\Services\PromotionItemImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PromotionItemTest extends TestCase
{
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
        );
    }

    public function test_import_service_ignores_grouping_rows_and_imports_individual_items(): void
    {
        // Sample CSV content matching the user request
        $csvContent = implode("\n", [
            'Barcode,Product,Weight(g),Gold Weight(g),D/G Weight(g),Addition Wastage(g),Total Wastage(g),Labor Fee(s),Internal Reference,Lot/Serial Number,Product/Design Subgroup/Name',
            'B1/Promotion (3),,3.29998884375,3.29998884375,0,0.52997810625,1.08,0,,,',
            'GBR211233,စက်ဖြတ်လက်စွပ်-B,0.989996653125,0.989996653125,0,0.18999046640625,0.39,0,GBR211233,S0000643/1,ရွှေလက်စွပ်',
            'GBR211234,စက်ဖြတ်လက်စွပ်-B,1.08999902109375,1.08999902109375,0,0.18999046640625,0.39,0,GBR211234,S0000644/1,ရွှေလက်စွပ်',
            'GBP231111,ပုံဆန်းဆွဲသီး-B,1.21999316953125,1.21999316953125,0,0.1499971734375,0.3,0,GBP231111,S0000870/1,ရွှေဆွဲသီး',
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'promo_test_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'promotions.csv',
            'text/csv',
            null,
            true
        );

        $service = new PromotionItemImportService();
        $result = $service->import($uploadedFile);

        $this->assertEquals(3, $result['imported']);
        $this->assertEquals(1, $result['skipped']); // Grouping row B1/Promotion (3) was skipped

        $this->assertDatabaseHas('promotion_items', [
            'barcode' => 'GBR211233',
            'lot_serial_number' => 'S0000643/1',
            'product' => 'စက်ဖြတ်လက်စွပ်-B',
        ]);

        $this->assertDatabaseHas('promotion_items', [
            'barcode' => 'GBP231111',
            'lot_serial_number' => 'S0000870/1',
        ]);

        $this->assertDatabaseMissing('promotion_items', [
            'barcode' => 'B1/Promotion (3)',
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_search_by_barcode_and_lot_serial_number(): void
    {
        $user = User::factory()->create();

        PromotionItem::create([
            'barcode' => 'SEARCH_BAR_999',
            'lot_serial_number' => 'LOT_AAA_111',
            'product' => 'Item One',
        ]);

        PromotionItem::create([
            'barcode' => 'OTHER_BAR_888',
            'lot_serial_number' => 'SEARCH_LOT_222',
            'product' => 'Item Two',
        ]);

        // Search by barcode
        $response1 = $this->actingAs($user)->get('/promotions?search=SEARCH_BAR_999');
        $response1->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.barcode', 'SEARCH_BAR_999')
        );

        // Search by lot / serial number
        $response2 = $this->actingAs($user)->get('/promotions?search=SEARCH_LOT_222');
        $response2->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.lot_serial_number', 'SEARCH_LOT_222')
        );
    }
}
