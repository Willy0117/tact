<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sensor;

class SensorDummySeeder extends Seeder
{
    public function run(): void
    {
        Sensor::factory(30)->create();
    }
}

