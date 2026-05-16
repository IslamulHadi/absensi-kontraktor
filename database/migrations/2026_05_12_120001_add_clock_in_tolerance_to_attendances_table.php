<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('clock_in_on_time_at')->nullable()->after('clock_in_at');
            $table->unsignedSmallInteger('clock_in_tolerance_minutes')->nullable()->after('clock_in_on_time_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['clock_in_on_time_at', 'clock_in_tolerance_minutes']);
        });
    }
};
