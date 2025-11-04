<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->after('id')->index();
        });
        DB::table('users')->update(['tenant_id' => 1]);

        // menus
        Schema::table('menus', function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->after('id')->index();
        });
        DB::table('menus')->update(['tenant_id' => 1]);

        // operators
        Schema::table('operators', function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->after('id')->index();
        });
        DB::table('operators')->update(['tenant_id' => 1]);

        // devices
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->after('id')->index();
        });
        DB::table('devices')->update(['tenant_id' => 1]);

        // sensors
        Schema::table('sensors', function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->after('id')->index();
        });
        DB::table('sensors')->update(['tenant_id' => 1]);

        // ★ Spatie 用テーブルはまだ存在しないので後で追加
        // roles / permissions には tenant_id を付ける予定（Cステップで実施）
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('sensors', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};


