<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->bothify('D###'),
            'name' => $this->faker->word . ' Device',
            'process' => $this->faker->randomElement(['加熱','冷却','搬送','検査']),
            'measurement' => $this->faker->boolean(10),
            'disabled' => $this->faker->boolean(10),
            'display_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}

