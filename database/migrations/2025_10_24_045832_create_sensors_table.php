<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();                        // 自動採番ID
            $table->string('code', 10)->unique(); // 測定機器コード（最大10文字、一意）
            $table->string('name');               // 測定機器名
            $table->string('model', 10);          // モデル名（最大10文字）
            $table->string('serial_number', 7)->unique(); // シリアルNO（最大7文字、一意）
            $table->timestamp('updated_at');      // 更新日時
            $table->boolean('disabled')->default(false);  // 無効フラグ
            $table->integer('display_order')->default(1); // 表示順
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensors'); // テーブルを削除
    }
};
