<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('client_clock_in_request_id', 64)->nullable()->after('notes');
            $table->string('client_clock_out_request_id', 64)->nullable()->after('client_clock_in_request_id');
            $table->unique('client_clock_in_request_id');
            $table->unique('client_clock_out_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['client_clock_in_request_id']);
            $table->dropUnique(['client_clock_out_request_id']);
            $table->dropColumn(['client_clock_in_request_id', 'client_clock_out_request_id']);
        });
    }
};
