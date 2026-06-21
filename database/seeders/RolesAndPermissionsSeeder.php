<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Location;
use App\Models\ProductType;
use App\Models\ProductTypeField;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\DecisionType;
use App\Models\DecisionRule;
use App\Models\ScanConfig;
use App\Models\CheckSession;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Permissions
        $permissions = [
            'manage users',
            'manage configuration',
            'manage products',
            'import products',
            'rollback import',
            'manage scan configs',
            'manage sessions',
            'perform check',
            'manage decisions',
            'view reports',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // 2. Create Roles and Assign Permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        // Super Admin gets all permissions implicitly via Gate, but let's sync all anyway
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions([
            'manage users',
            'manage configuration',
            'manage products',
            'import products',
            'rollback import',
            'manage scan configs',
            'manage sessions',
            'manage decisions',
            'view reports',
        ]);

        $supervisorRole = Role::firstOrCreate(['name' => 'Supervisor']);
        $supervisorRole->syncPermissions([
            'manage sessions',
            'manage decisions',
            'view reports',
            'perform check',
        ]);

        $checkerRole = Role::firstOrCreate(['name' => 'Checker']);
        $checkerRole->syncPermissions([
            'perform check',
            'view reports',
        ]);

        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);
        $viewerRole->syncPermissions([
            'view reports',
        ]);

        // 3. Create Default Users
        $usersData = [
            [
                'name' => 'Super Admin User',
                'email' => 'superadmin@maha.com',
                'password' => 'password123',
                'status' => 'ACTIVE',
                'role' => 'Super Admin',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@maha.com',
                'password' => 'password123',
                'status' => 'ACTIVE',
                'role' => 'Admin',
            ],
            [
                'name' => 'Supervisor User',
                'email' => 'supervisor@maha.com',
                'password' => 'password123',
                'status' => 'ACTIVE',
                'role' => 'Supervisor',
            ],
            [
                'name' => 'Checker User',
                'email' => 'checker@maha.com',
                'password' => 'password123',
                'status' => 'ACTIVE',
                'role' => 'Checker',
            ],
            [
                'name' => 'Viewer User',
                'email' => 'viewer@maha.com',
                'password' => 'password123',
                'status' => 'ACTIVE',
                'role' => 'Viewer',
            ],
            [
                'name' => 'Suspended Checker',
                'email' => 'suspended@maha.com',
                'password' => 'password123',
                'status' => 'SUSPENDED',
                'role' => 'Checker',
            ],
        ];

        foreach ($usersData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'status' => $data['status'],
                ]
            );
            $user->syncRoles($data['role']);
        }

        // 4. Seed Locations
        $locations = [
            ['code' => 'WH-A1', 'name' => 'Warehouse A - Shelf 1', 'description' => 'Main storage for high value items'],
            ['code' => 'WH-A2', 'name' => 'Warehouse A - Shelf 2', 'description' => 'Storage for clothes and accessories'],
            ['code' => 'WH-B1', 'name' => 'Warehouse B - Freezer 1', 'description' => 'Cold storage for perishable food items'],
            ['code' => 'SHOP-DISPLAY', 'name' => 'Main Shop Display', 'description' => 'Front of store display shelves'],
        ];

        foreach ($locations as $loc) {
            Location::updateOrCreate(['code' => $loc['code']], $loc);
        }

        // 5. Seed Product Types
        $productTypes = [
            ['code' => 'JEWELRY', 'name' => 'Jewelry', 'is_active' => true],
            ['code' => 'FOOD', 'name' => 'Food', 'is_active' => true],
            ['code' => 'PHONE', 'name' => 'Phone/Laptop', 'is_active' => true],
            ['code' => 'CLOTHES', 'name' => 'Clothes', 'is_active' => true],
            ['code' => 'ACCESSORIES', 'name' => 'Human Accessories', 'is_active' => true],
        ];

        foreach ($productTypes as $pt) {
            ProductType::updateOrCreate(['code' => $pt['code']], $pt);
        }

        // 6. Seed Product Type Dynamic Fields
        $jewelryType = ProductType::where('code', 'JEWELRY')->first();
        $foodType = ProductType::where('code', 'FOOD')->first();
        $phoneType = ProductType::where('code', 'PHONE')->first();

        // Jewelry Fields
        $jewelryFields = [
            ['field_name' => 'weight_g', 'field_label' => 'Weight (grams)', 'field_type' => 'decimal', 'required' => true],
            ['field_name' => 'gold_type', 'field_label' => 'Gold Purity (e.g. 18K, 24K)', 'field_type' => 'select', 'required' => true],
            ['field_name' => 'diamond_count', 'field_label' => 'Diamond Count', 'field_type' => 'number', 'required' => false],
        ];
        foreach ($jewelryFields as $f) {
            ProductTypeField::updateOrCreate(
                ['product_type_id' => $jewelryType->id, 'field_name' => $f['field_name']],
                $f
            );
        }

        // Food Fields
        $foodFields = [
            ['field_name' => 'expiry_date', 'field_label' => 'Expiry Date', 'field_type' => 'date', 'required' => true],
            ['field_name' => 'batch_no', 'field_label' => 'Batch Number', 'field_type' => 'text', 'required' => true],
        ];
        foreach ($foodFields as $f) {
            ProductTypeField::updateOrCreate(
                ['product_type_id' => $foodType->id, 'field_name' => $f['field_name']],
                $f
            );
        }

        // Phone Fields
        $phoneFields = [
            ['field_name' => 'imei', 'field_label' => 'IMEI Number', 'field_type' => 'text', 'required' => true],
            ['field_name' => 'ram', 'field_label' => 'RAM (GB)', 'field_type' => 'number', 'required' => true],
            ['field_name' => 'storage', 'field_label' => 'Storage (GB)', 'field_type' => 'number', 'required' => true],
        ];
        foreach ($phoneFields as $f) {
            ProductTypeField::updateOrCreate(
                ['product_type_id' => $phoneType->id, 'field_name' => $f['field_name']],
                $f
            );
        }

        // 7. Seed Categories & Sub-Categories
        // Jewelry Categories
        $jCat = Category::updateOrCreate(['product_type_id' => $jewelryType->id, 'name' => 'Rings']);
        SubCategory::updateOrCreate(['category_id' => $jCat->id, 'name' => 'Gold Rings']);
        SubCategory::updateOrCreate(['category_id' => $jCat->id, 'name' => 'Diamond Rings']);

        $jCat2 = Category::updateOrCreate(['product_type_id' => $jewelryType->id, 'name' => 'Necklaces']);
        SubCategory::updateOrCreate(['category_id' => $jCat2->id, 'name' => 'Chains']);

        // Food Categories
        $fCat = Category::updateOrCreate(['product_type_id' => $foodType->id, 'name' => 'Canned Food']);
        SubCategory::updateOrCreate(['category_id' => $fCat->id, 'name' => 'Canned Fish']);

        // Phone Categories
        $pCat = Category::updateOrCreate(['product_type_id' => $phoneType->id, 'name' => 'Smartphones']);
        SubCategory::updateOrCreate(['category_id' => $pCat->id, 'name' => 'Android']);
        SubCategory::updateOrCreate(['category_id' => $pCat->id, 'name' => 'iOS']);

        // 8. Seed Decision Types
        $decisionTypes = [
            ['code' => 'INVESTIGATE', 'name' => 'Investigate Mismatch', 'description' => 'Perform physical check on why properties mismatched'],
            ['code' => 'REPAIR', 'name' => 'Send for Repair', 'description' => 'Send damaged item for repair'],
            ['code' => 'TRANSFER', 'name' => 'Transfer Location', 'description' => 'Transfer item back to its correct location'],
            ['code' => 'RECOUNT', 'name' => 'Recount Stock', 'description' => 'Request second verification count on this stock'],
            ['code' => 'ADJUST_STOCK', 'name' => 'Adjust Stock Value', 'description' => 'Adjust system stock quantities/attributes'],
            ['code' => 'ESCALATE', 'name' => 'Escalate to Management', 'description' => 'Report severe anomalies or missing high-value item'],
        ];

        foreach ($decisionTypes as $dt) {
            DecisionType::updateOrCreate(['code' => $dt['code']], $dt);
        }

        // 9. Seed Decision Rules
        $rules = [
            [
                'name' => 'Weight Mismatch Rules',
                'criteria_field' => 'weight_g',
                'criteria_condition' => 'exceeds_tolerance',
                'decision_code' => 'INVESTIGATE'
            ],
            [
                'name' => 'Location Mismatch Rules',
                'criteria_field' => 'location_id',
                'criteria_condition' => 'mismatch',
                'decision_code' => 'RECOUNT'
            ],
            [
                'name' => 'Missing Barcode/Code Mismatch',
                'criteria_field' => 'code',
                'criteria_condition' => 'mismatch',
                'decision_code' => 'ESCALATE'
            ],
        ];

        foreach ($rules as $r) {
            $dt = DecisionType::where('code', $r['decision_code'])->first();
            DecisionRule::updateOrCreate(
                ['name' => $r['name']],
                [
                    'criteria_field' => $r['criteria_field'],
                    'criteria_condition' => $r['criteria_condition'],
                    'decision_type_id' => $dt->id,
                    'is_active' => true,
                ]
            );
        }

        // 10. Seed Scan Config
        $detailCheckJson = [
            'name' => 'Detail Check',
            'fields' => [
                [
                    'field' => 'code',
                    'source' => 'product',
                    'required' => true,
                    'compare' => true,
                ],
                [
                    'field' => 'weight_g',
                    'source' => 'product',
                    'required' => true,
                    'compare' => true,
                    'tolerance' => 0.02,
                ],
                [
                    'field' => 'remark',
                    'source' => 'check',
                    'required' => false,
                ],
            ],
        ];

        ScanConfig::updateOrCreate(
            ['product_type_id' => $jewelryType->id, 'name' => 'Jewelry Standard Count'],
            [
                'description' => 'Jewelry count comparing code and weight with 0.02g tolerance',
                'config_json' => $detailCheckJson,
                'is_active' => true,
            ]
        );

        $phoneCheckJson = [
            'name' => 'Phone IMEI Count',
            'fields' => [
                [
                    'field' => 'code',
                    'source' => 'product',
                    'required' => true,
                    'compare' => true,
                ],
                [
                    'field' => 'imei',
                    'source' => 'product',
                    'required' => true,
                    'compare' => true,
                ],
                [
                    'field' => 'storage',
                    'source' => 'product',
                    'required' => true,
                    'compare' => true,
                ],
            ]
        ];

        ScanConfig::updateOrCreate(
            ['product_type_id' => $phoneType->id, 'name' => 'Phone IMEI & Spec Audit'],
            [
                'description' => 'Check Phone IMEI and Storage specifications matches expected',
                'config_json' => $phoneCheckJson,
                'is_active' => true,
            ]
        );

        // 11. Seed Check Session
        $admin = User::where('email', 'admin@maha.com')->first();
        CheckSession::updateOrCreate(
            ['name' => 'Main Warehouse Audit June 2026'],
            [
                'description' => 'Full count of Jewelry and Electronics items',
                'started_by' => $admin->id,
                'started_at' => now(),
                'status' => 'OPEN',
            ]
        );
    }
}
