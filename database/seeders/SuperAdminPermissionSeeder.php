<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class SuperAdminPermissionSeeder extends Seeder
{
    public function run()
    {
        // super admin ユーザーを取得（ID=1）
        $superAdmin = User::find(1);

        if (!$superAdmin) {
            $this->command->info('Super admin user not found.');
            return;
        }

        // すべての権限を取得
        $permissions = Permission::all()->pluck('name')->toArray();

        // super admin にすべての権限を付与
        $superAdmin->givePermissionTo($permissions);

        $this->command->info('Super admin permissions assigned.');
    }
}
