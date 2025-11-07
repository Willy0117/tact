<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class SuperAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'dev@coo-net.co.jp'; // ← 実際の管理者メールに変更

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->command->error("User not found: {$email}");
            return;
        }

        $user->assignRole('super admin');
        $this->command->info("Role assigned to {$email}");
    }
}
