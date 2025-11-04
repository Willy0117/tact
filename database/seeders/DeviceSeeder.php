<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use Carbon\Carbon;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            Device::create([
                'code' => 'DEVICE' . str_pad($i, 3, '0', STR_PAD_LEFT), // DEVICE001, DEVICE002...
                'name' => 'Device ' . $i,
                'process' => 'Process ' . ($i % 5 + 1), // Process 1～5
                'measurement' => 'Measurement ' . ($i % 3 + 1), // Measurement 1～3
                'disabled' => rand(0, 1),
                'display_order' => $i,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}

