<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();

            $table->date('serving_date')->comment('配膳日');
            $table->string('serving_time')->comment('配膳時刻'); // 例: 朝食, 昼食, 夕食

            $table->string('dish_name')->comment('料理名');
            $table->string('process')->comment('工程'); // 例: 加熱、冷却
            $table->string('equipment_name')->comment('機器名'); // 例: IHテーブル2

            $table->string('measurement_device')->nullable()->comment('測定機器'); // 例: 中心温度計No.1

            $table->date('cooking_date')->comment('調理日');
            $table->dateTime('registered_at')->comment('登録日時');

            $table->timestamps(); // created_at / updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};

