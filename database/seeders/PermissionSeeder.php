<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Super Admin 用のグローバル権限
        $permissions = [
            'manage tenants',
            'manage roles',
            'manage permissions',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'tenant_id' => null,
                'guard_name' => 'web',
            ]);
        }

        // ✅ Super Admin ロール作成
        $role = Role::firstOrCreate([
            'name' => 'super admin',
            'tenant_id' => null,
            'guard_name' => 'web',
        ]);

        // ✅ グローバル権限を全部付与
        $role->syncPermissions($permissions);
    }
}

