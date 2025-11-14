<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // tenant_id を NULL 許容に変更
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // 元に戻す（NOT NULL に戻す）
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });
    }
};
