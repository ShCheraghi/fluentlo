<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('provider');     // 'google', 'facebook', etc.
            $table->string('provider_id');  // Provider's unique user ID

            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();

            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->unsignedInteger('expires_in')->nullable();

            $table->timestamps();

            // Indexes and constraints
            $table->unique(['provider', 'provider_id']);
            $table->index(['user_id']);
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
