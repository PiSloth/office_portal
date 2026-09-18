<?php

namespace Database\Seeders;

use App\Services\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ResourcePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPerms = PermissionRegistry::getAllResourcePermissionNames();
        $createdCount = 0;

        foreach ($allPerms as $perm) {
            $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            if ($p->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Update backup file if exists
        $backupPath = database_path('seeders/data/roles_and_permissions_backup.json');
        if (file_exists($backupPath)) {
            $backup = json_decode(file_get_contents($backupPath), true);
            $existing = $backup['permissions'] ?? [];
            $merged = array_values(array_unique(array_merge($existing, $allPerms)));
            sort($merged);
            $backup['permissions'] = $merged;
            file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->command?->info("Created {$createdCount} new permissions. Total in DB: " . Permission::count());
    }
}
