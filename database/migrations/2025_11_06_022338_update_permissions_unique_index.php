<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            // 既存のユニーク制約を削除
            $table->dropUnique(['name', 'guard_name']);

            // tenant_id を含むユニーク制約を追加
            $table->unique(['name', 'guard_name', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name', 'tenant_id']);
            $table->unique(['name', 'guard_name']);
        });
    }
};
?>
