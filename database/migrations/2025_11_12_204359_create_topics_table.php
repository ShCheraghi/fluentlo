<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete()->comment('واحد مرتبط');

            $table->integer('sequence')->default(0)->comment('ترتیب موضوع در واحد');
            $table->string('title_fa')->comment('عنوان فارسی موضوع');
            $table->string('title_en')->comment('عنوان انگلیسی موضوع');

            // سناریو و مکالمه — از longText برای امنیت بیشتر استفاده شده
            $table->text('scenario_fa')->nullable()->comment('سناریو یا توضیح فارسی (اختیاری)');
            $table->text('scenario_en')->nullable()->comment('سناریو یا توضیح انگلیسی (اختیاری)');
            $table->longText('conversation_en')->comment('مکالمهٔ انگلیسی کامل (longText)');
            $table->longText('conversation_fa')->comment('ترجمهٔ فارسی مکالمه (longText)');

            // رسانه و هینت‌ها
            $table->string('image_url')->nullable()->comment('آدرس تصویر موضوع');
            $table->string('audio_url')->nullable()->comment('آدرس فایل صوتی (اختیاری)');
            $table->text('hint_1')->nullable()->comment('هینت 1 - نرم');
            $table->text('hint_2')->nullable()->comment('هینت 2 - میانی');
            $table->text('hint_3')->nullable()->comment('هینت 3 - جواب کامل');

            $table->text('explanation_fa')->nullable()->comment('توضیح فارسی (اختیاری)');
            $table->text('explanation_en')->nullable()->comment('توضیح انگلیسی (اختیاری)');

            $table->boolean('is_published')->default(true)->comment('منتشر شده یا خیر');

            $table->timestamps();

            $table->unique(['unit_id', 'sequence']);
            $table->index('is_published');
        });
    }

    public function down(): void {
        Schema::dropIfExists('topics');
    }
};
