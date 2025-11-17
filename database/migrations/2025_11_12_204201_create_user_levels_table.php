<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('کاربر');
            $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete()->comment('سطح');

            // وضعیت
            $table->boolean('is_current')->default(false)->comment('آیا این سطح فعلی کاربر است؟');
            $table->boolean('is_completed')->default(false)->comment('آیا سطح تکمیل شده است؟');

            // گزینهٔ اختیاری: واحد جاری کاربر در این سطح
            $table->foreignId('current_unit_id')->nullable()->constrained('units')->nullOnDelete()->comment('واحد جاری (اختیاری)');

            // زمان‌ها
            $table->dateTime('started_at')->useCurrent()->comment('زمان شروع این سطح');
            $table->dateTime('completed_at')->nullable()->comment('زمان تکمیل');
            $table->dateTime('last_activity_at')->useCurrent()->comment('آخرین فعالیت');

            $table->timestamps();

            $table->unique(['user_id', 'level_id']);
            $table->index('is_current');
            $table->index('is_completed');
        });
    }

    public function down(): void {
        Schema::dropIfExists('user_levels');
    }
};
