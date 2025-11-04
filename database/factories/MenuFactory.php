<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    protected $model = \App\Models\Menu::class;

    public function definition()
    {
        $meals = ['朝食', '昼食', '夕食']; // 食事タイプ
        $days = ['月曜', '火曜', '水曜', '木曜', '金曜', '土曜', '日曜'];

        return [
            'code' => strtoupper($this->faker->unique()->lexify('MENU???')),
            'name' => $days[array_rand($days)] . ' ' . $meals[array_rand($meals)] . ' ' . $this->faker->word,
            'serving_time' => $this->faker->time('H:i'),
            'ingredients' => implode(', ', $this->faker->words(5)), // 材料をカンマ区切り
            'display_order' => $this->faker->numberBetween(1, 100),
            'disabled' => $this->faker->boolean(10),
        ];
    }
}


