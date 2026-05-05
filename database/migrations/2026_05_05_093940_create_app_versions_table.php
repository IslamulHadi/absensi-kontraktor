<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 16)->index();
            $table->string('version_name', 32);
            $table->unsignedInteger('version_code');
            $table->unsignedInteger('min_supported_version_code')->default(1);
            $table->string('download_url', 512)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'version_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
