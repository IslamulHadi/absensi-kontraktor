<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->time('clock_in_on_time_at')->default('09:00:00');
            $table->unsignedSmallInteger('clock_in_tolerance_minutes')->default(15);
            $table->timestamps();
        });

        DB::table('attendance_settings')->insert([
            'clock_in_on_time_at' => '09:00:00',
            'clock_in_tolerance_minutes' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
