<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('topic_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('کاربر');
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete()->comment('موضوع / درس');

            // جواب و مرجع
            $table->text('user_answer')->nullable()->comment('جواب کاربر (متن یا متن تبدیل‌شده از صوت)');
            $table->text('expected_answer')->nullable()->comment('جواب هدف/انتظار رفته (برای مرجع)');

            // ارزیابی
            $table->integer('similarity_score')->default(0)->comment('درصد تطابق 0-100');
            $table->enum('status', ['pass','fail'])->default('fail')->comment('نتیجهٔ تلاش');

            // تلاش و زمان
            $table->integer('attempt_number')->default(1)->comment('شماره تلاش برای این موضوع');
            $table->boolean('is_first_try_correct')->default(false)->comment('آیا تلاش اول صحیح بود؟');
            $table->integer('time_spent_seconds')->default(0)->comment('زمان صرف شده (ثانیه)');

            // بازخورد و تحلیل
            $table->text('feedback_fa')->nullable()->comment('بازخورد فارسی');
            $table->text('feedback_en')->nullable()->comment('بازخورد انگلیسی');
            $table->text('analysis')->nullable()->comment('تحلیل خطا یا JSON تحلیلی');

            $table->timestamps();

            // اندیس‌های پرکاربرد
            $table->index(['user_id', 'created_at']);
            $table->index(['topic_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('topic_attempts');
    }
};
