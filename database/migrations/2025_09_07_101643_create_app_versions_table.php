<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform')->comment('ios, android');
            $table->string('version')->comment('1.0.0');
            $table->integer('build_number')->comment('1, 2, 3, ...');
            $table->boolean('force_update')->default(false);
            $table->string('title')->nullable();
            $table->text('description')->nullable()->comment('HTML content');
            $table->json('store_links')->nullable()->comment('{"app_store": "https://...", "google_play": "https://..."}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['platform', 'version', 'build_number']);
            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
