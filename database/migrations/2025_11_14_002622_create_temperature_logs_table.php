<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temperature_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');

            // 献立
            $table->unsignedBigInteger('menu_id')->nullable();

            // 工程
            $table->unsignedBigInteger('process_id')->nullable();

            // 測定機器
            $table->unsignedBigInteger('device_id')->nullable();

            // 作業者
            $table->unsignedBigInteger('operator_id')->nullable();

            // 温度
            $table->float('temperature')->nullable();

            // 測定日時
            $table->timestamp('measured_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_logs');
    }
};

