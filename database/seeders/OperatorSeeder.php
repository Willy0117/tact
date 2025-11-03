<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Operator;

class OperatorSeeder extends Seeder
{
    public function run()
    {
        $operators = [
            ['code' => 'S0001', 'name' => '山田 太郎', 'disabled' => false, 'display_order' => 1],
            ['code' => 'S0002', 'name' => '鈴木 次郎', 'disabled' => false, 'display_order' => 2],
            ['code' => 'S0003', 'name' => '佐藤 花子', 'disabled' => false, 'display_order' => 3],
            ['code' => 'S0004', 'name' => '田中 一郎', 'disabled' => false, 'display_order' => 4],
            ['code' => 'S0005', 'name' => '高橋 美咲', 'disabled' => false, 'display_order' => 5],
            ['code' => 'S0006', 'name' => '伊藤 大輔', 'disabled' => false, 'display_order' => 6],
            ['code' => 'S0007', 'name' => '渡辺 さくら', 'disabled' => false, 'display_order' => 7],
            ['code' => 'S0008', 'name' => '山本 健', 'disabled' => false, 'display_order' => 8],
            ['code' => 'S0009', 'name' => '中村 彩', 'disabled' => false, 'display_order' => 9],
            ['code' => 'S0010', 'name' => '小林 拓也', 'disabled' => false, 'display_order' => 10],
            ['code' => 'S0011', 'name' => '加藤 美穂', 'disabled' => false, 'display_order' => 11],
            ['code' => 'S0012', 'name' => '吉田 勇', 'disabled' => false, 'display_order' => 12],
            ['code' => 'S0013', 'name' => '山崎 彩香', 'disabled' => false, 'display_order' => 13],
            ['code' => 'S0014', 'name' => '松本 健太', 'disabled' => false, 'display_order' => 14],
            ['code' => 'S0015', 'name' => '井上 真由', 'disabled' => false, 'display_order' => 15],
            ['code' => 'S0016', 'name' => '木村 拓海', 'disabled' => false, 'display_order' => 16],
            ['code' => 'S0017', 'name' => '林 由美子', 'disabled' => false, 'display_order' => 17],
            ['code' => 'S0018', 'name' => '斎藤 航', 'disabled' => false, 'display_order' => 18],
            ['code' => 'S0019', 'name' => '森 恵', 'disabled' => false, 'display_order' => 19],
            ['code' => 'S0020', 'name' => '池田 陽介', 'disabled' => false, 'display_order' => 20],
        ];

        foreach ($operators as $operator) {
            Operator::create($operator);
        }
    }
}
