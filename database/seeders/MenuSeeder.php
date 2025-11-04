<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use Carbon\Carbon;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serving_times = [
            '朝食' => '08:00:00',
            '昼食' => '12:00:00',
            '夕食' => '18:00:00',
        ];

        foreach ($serving_times as $meal => $time) {
            for ($i = 1; $i <= 50; $i++) {
                Menu::create([
                    'name' => "{$meal} メニュー {$i}",
                    'serving_time' => $time,
                    'ingredients' => "材料A, 材料B, 材料C",
                    'disabled' => rand(0,1),
                    'display_order' => $i,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}

