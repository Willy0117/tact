<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SensorFactory extends Factory
{
    protected $model = \App\Models\Sensor::class;

    public function definition()
    {
        return [
            'code' => 'M' . $this->faker->unique()->numberBetween(100, 999),
            'name' => 'Sensor ' . $this->faker->unique()->word,
            'model' => $this->faker->regexify('[A-Z]{2}[0-9]{3}'), // 固定長10も可
            'serial_number' => $this->faker->unique()->numerify('#######'),
            'disabled' => $this->faker->boolean(10),
            'display_order' => $this->faker->numberBetween(1, 30),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

