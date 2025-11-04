<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // roles
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->index();
        });

        // permissions
        Schema::table('permissions', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->index();
        });

        // model_has_roles, model_has_permissions は tenant_id が不要
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};

