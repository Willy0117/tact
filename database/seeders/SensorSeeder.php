<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sensor;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            Sensor::create([
                'code' => 'SENSOR' . str_pad($i, 3, '0', STR_PAD_LEFT), // SENSOR001, SENSOR002...
                'name' => 'Sensor ' . $i,
                'model' => 'Model ' . chr(64 + ($i % 26 + 1)), // Model A, Model B ...
                'serial_number' => 'SN' . str_pad($i, 5, '0', STR_PAD_LEFT), // SN00001, SN00002 ...
                'disabled' => rand(0, 1),
                'display_order' => $i,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}

