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
            $table->string('name');                 // 献立名
            $table->time('serving_time');          // 提供時間
            $table->text('ingredients')->nullable(); // 材料
            $table->boolean('disabled')->default(false); // 有効/無効
            $table->integer('display_order')->default(1); // 表示順
            $table->timestamps();                   // created_at, updated_at
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

