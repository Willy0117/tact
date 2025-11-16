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
            $table->unsignedBigInteger('tenant_id');      // 給食センター
            $table->unsignedBigInteger('menu_id')->nullable();     // 献立メニュー
            $table->unsignedBigInteger('process_id')->nullable();  // 作業工程
            $table->unsignedBigInteger('device_id')->nullable();   // 調理器具
            $table->unsignedBigInteger('operator_id')->nullable(); // 作業者

            $table->double('temperature')->nullable();    // 温度
            $table->timestamp('measured_at');             // 計測日時（端末の時刻）

            $table->timestamps(); // created_at = registration_date, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_logs');
    }
};

