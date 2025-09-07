<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('onboarding_screens', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->string('background_color', 7)->nullable();
            $table->string('text_color', 7)->default('#000000');
            $table->string('button_color', 7)->default('#007AFF');
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_screens');
    }
};
