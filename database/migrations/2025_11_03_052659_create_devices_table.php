<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ユニーク制約
            $table->string('name');
            $table->string('process');       // 製造工程やプロセス名
            $table->string('measurement');   // 測定方法など
            $table->boolean('disabled')->default(false);
            $table->integer('display_order')->default(1);
            $table->timestamps();            // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

