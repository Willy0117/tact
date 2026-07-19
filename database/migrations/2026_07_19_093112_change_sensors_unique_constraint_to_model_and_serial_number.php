<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropUnique('sensors_serial_number_unique');
            $table->unique(['model', 'serial_number'], 'sensors_model_serial_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropUnique('sensors_model_serial_number_unique');
            $table->unique('serial_number', 'sensors_serial_number_unique');
        });
    }
};