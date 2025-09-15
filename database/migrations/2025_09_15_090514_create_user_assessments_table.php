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
        Schema::create('user_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('target_language'); // زبان هدف
            $table->string('native_language'); // زبان مادری
            $table->json('motivations'); // انگیزه‌ها (آرایه)
            $table->json('topics'); // موضوعات علاقه (آرایه)
            $table->string('self_level'); // سطح خودارزیابی
            $table->json('improve_areas'); // حوزه‌های بهبود (آرایه)
            $table->string('timeline'); // بازه زمانی
            $table->string('daily_words'); // کلمات روزانه
            $table->timestamps();

            // ایندکس برای بهبود عملکرد
            $table->index(['user_id', 'target_language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_assessments');
    }
};
