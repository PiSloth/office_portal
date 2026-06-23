<?php

namespace Database\Seeders;

use App\Models\CheckSession;
use App\Models\Category;
use App\Models\DecisionRule;
use App\Models\DecisionType;
use App\Models\Location;
use App\Models\ProductType;
use App\Models\ProductTypeField;
use App\Models\ScanConfig;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'permissions.manage',
            // 'config.view',
            // 'config.manage',
            'roles.assign',
            'roles.update',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'sub-categories.view',
            'sub-categories.create',
            'sub-categories.update',
            'sub-categories.delete',
            'locations.view',
            'locations.create',
            'locations.update',
            'locations.delete',
            'product-types.view',
            'product-types.create',
            'product-types.update',
            'product-types.delete',
            'scan-configs.view',
            'scan-configs.create',
            'scan-configs.update',
            'scan-configs.delete',
            'sessions.view',
            'sessions.create',
            'sessions.update',
            'sessions.delete',
            'decisions.view',
            'decisions.create',
            'decisions.update',
            'decisions.delete',
            'decisions.assign',
            'reports.view',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.import',
            'products.rollback',
            'scan-configs.view',
            'scan-configs.create',
            'scan-configs.update',
            'scan-configs.delete',
            'sessions.view',
            'sessions.create',
            'sessions.update',
            'sessions.delete',
            'sessions.manage',
            'checks.view',
            'checks.create',
            'checks.update',
            'checks.delete',
            'product-checks.view',
            'product-checks.create',
            'product-checks.update',
            'product-checks.delete',
            'decisions.view',
            'decisions.create',
            'decisions.update',
            'decisions.delete',
            'decisions.assign',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $checker = Role::firstOrCreate(['name' => 'checker', 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::all());
        $admin->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'roles.view',
            'roles.assign',
            'permissions.view',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'sub-categories.view',
            'sub-categories.create',
            'sub-categories.update',
            'sub-categories.delete',
            'locations.view',
            'locations.create',
            'locations.update',
            'locations.delete',
            'product-types.view',
            'product-types.create',
            'product-types.update',
            'product-types.delete',
            'scan-configs.view',
            'scan-configs.create',
            'scan-configs.update',
            'scan-configs.delete',
            'sessions.view',
            'sessions.create',
            'sessions.update',
            'sessions.delete',
            'decisions.view',
            'decisions.create',
            'decisions.update',
            'decisions.delete',
            'decisions.assign',
            'reports.view',
            'products.view',
            'products.create',
            'products.update',
            'products.import',
            'scan-configs.view',
            'scan-configs.create',
            'scan-configs.update',
            'sessions.view',
            'sessions.create',
            'sessions.update',
            'sessions.manage',
            'checks.view',
            'checks.create',
            'checks.update',
            'product-checks.view',
            'product-checks.create',
            'product-checks.update',
            'product-checks.delete',
            'decisions.view',
            'decisions.create',
            'decisions.update',
            'decisions.assign',
            'reports.view',
        ]);
        $manager->syncPermissions([
            'roles.assign',
            'roles.update',
            'products.view',
            'products.update',
            'scan-configs.view',
            'sessions.view',
            'sessions.create',
            'sessions.update',
            'sessions.manage',
            'checks.view',
            'checks.create',
            'product-checks.view',
            'product-checks.create',
            'product-checks.update',
            'decisions.view',
            'decisions.create',
            'decisions.update',
            'decisions.assign',
            'reports.view',
        ]);
        $checker->syncPermissions([
            'products.view',
            'sessions.view',
            'checks.view',
            'checks.create',
            'decisions.view',
            'reports.view',
        ]);

        $users = [
            ['name' => 'Super Admin User', 'email' => 'superadmin@mahar.com', 'password' => 'super@Admin', 'status' => 'ACTIVE', 'roles' => ['super-admin']],
            ['name' => 'Admin User', 'email' => 'admin@mahar.com', 'password' => 'password123', 'status' => 'ACTIVE', 'roles' => ['admin']],
            ['name' => 'Manager User', 'email' => 'manager@mahar.com', 'password' => 'password123', 'status' => 'ACTIVE', 'roles' => ['manager']],
            ['name' => 'Checker User', 'email' => 'checker@mahar.com', 'password' => 'password123', 'status' => 'ACTIVE', 'roles' => ['checker']],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'status' => $data['status'],
                ]
            );
            $user->syncRoles($data['roles']);
        }

        // Existing operational seed data retained
        $locations = [
            ['code' => 'WH-A1', 'name' => 'Warehouse A - Shelf 1', 'description' => 'Main storage for high value items'],
            ['code' => 'WH-A2', 'name' => 'Warehouse A - Shelf 2', 'description' => 'Storage for clothes and accessories'],
            ['code' => 'WH-B1', 'name' => 'Warehouse B - Freezer 1', 'description' => 'Cold storage for perishable food items'],
            ['code' => 'SHOP-DISPLAY', 'name' => 'Main Shop Display', 'description' => 'Front of store display shelves'],
        ];
        foreach ($locations as $loc) {
            Location::updateOrCreate(['code' => $loc['code']], $loc);
        }

        $productTypes = [
            ['code' => 'JEWELRY', 'name' => 'Jewelry', 'is_active' => true],
            ['code' => 'FOOD', 'name' => 'Food', 'is_active' => true],
            ['code' => 'PHONE', 'name' => 'Phone/Laptop', 'is_active' => true],
        ];
        foreach ($productTypes as $pt) {
            ProductType::updateOrCreate(['code' => $pt['code']], $pt);
        }

        $jewelryType = ProductType::where('code', 'JEWELRY')->first();
        $foodType = ProductType::where('code', 'FOOD')->first();
        $phoneType = ProductType::where('code', 'PHONE')->first();

        foreach (
            [
                [$jewelryType, [['field_name' => 'weight_g', 'field_label' => 'Weight (grams)', 'field_type' => 'decimal', 'required' => true]]],
                [$foodType, [['field_name' => 'expiry_date', 'field_label' => 'Expiry Date', 'field_type' => 'date', 'required' => true]]],
                [$phoneType, [['field_name' => 'imei', 'field_label' => 'IMEI Number', 'field_type' => 'text', 'required' => true]]],
            ] as [$type, $fields]
        ) {
            foreach ($fields as $f) {
                ProductTypeField::updateOrCreate(
                    ['product_type_id' => $type->id, 'field_name' => $f['field_name']],
                    $f
                );
            }
        }

        Category::updateOrCreate(['product_type_id' => $jewelryType->id, 'name' => 'Rings']);
        Category::updateOrCreate(['product_type_id' => $foodType->id, 'name' => 'Canned Food']);
        Category::updateOrCreate(['product_type_id' => $phoneType->id, 'name' => 'Smartphones']);

        DecisionType::updateOrCreate(['code' => 'INVESTIGATE'], ['name' => 'Investigate Mismatch', 'description' => 'Perform physical check on mismatch']);
        DecisionType::updateOrCreate(['code' => 'RECOUNT'], ['name' => 'Recount Stock', 'description' => 'Request second verification count']);
        DecisionType::updateOrCreate(['code' => 'ESCALATE'], ['name' => 'Escalate to Management', 'description' => 'Report severe anomaly']);

        $rules = [
            ['name' => 'Weight Mismatch Rules', 'criteria_field' => 'weight_g', 'criteria_condition' => 'exceeds_tolerance', 'decision_code' => 'INVESTIGATE'],
            ['name' => 'Location Mismatch Rules', 'criteria_field' => 'location_id', 'criteria_condition' => 'mismatch', 'decision_code' => 'RECOUNT'],
            ['name' => 'Missing Barcode/Code Mismatch', 'criteria_field' => 'code', 'criteria_condition' => 'mismatch', 'decision_code' => 'ESCALATE'],
        ];
        foreach ($rules as $rule) {
            $decisionType = DecisionType::where('code', $rule['decision_code'])->first();
            DecisionRule::updateOrCreate(
                ['name' => $rule['name']],
                [
                    'criteria_field' => $rule['criteria_field'],
                    'criteria_condition' => $rule['criteria_condition'],
                    'decision_type_id' => $decisionType?->id,
                    'is_active' => true,
                ]
            );
        }

        $jewelryType = ProductType::where('code', 'JEWELRY')->first();
        $admin = User::where('email', 'admin@maha.com')->first();
        if ($jewelryType && $admin) {
            CheckSession::updateOrCreate(
                ['name' => 'Main Warehouse Audit June 2026'],
                [
                    'description' => 'Full count audit',
                    'started_by' => $admin->id,
                    'started_at' => now(),
                    'status' => 'OPEN',
                ]
            );
            ScanConfig::updateOrCreate(
                ['product_type_id' => $jewelryType->id, 'name' => 'Jewelry Standard Count'],
                [
                    'description' => 'Weight based review',
                    'config_json' => ['fields' => [['field' => 'weight_g', 'source' => 'product', 'required' => true, 'compare' => true, 'tolerance' => 0.02]]],
                    'is_active' => true,
                ]
            );
        }
    }
}
