<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete()->comment('سطح مرتبط');

            $table->integer('sequence')->default(0)->comment('ترتیب واحد در سطح');
            $table->string('title_fa')->comment('عنوان فارسی واحد');
            $table->string('title_en')->comment('عنوان انگلیسی واحد');
            $table->text('description_fa')->nullable()->comment('توضیح فارسی (اختیاری)');
            $table->text('description_en')->nullable()->comment('توضیح انگلیسی (اختیاری)');

            $table->string('image_url')->nullable()->comment('آدرس تصویر واحد');
            $table->text('introduction_fa')->nullable()->comment('مقدمهٔ فارسی');
            $table->text('introduction_en')->nullable()->comment('مقدمهٔ انگلیسی');

            $table->integer('topics_count')->default(0)->comment('تعداد موضوعات (نگهداری‌شده برای سرعت)');
            $table->boolean('is_published')->default(true)->comment('منتشر شده یا خیر');

            $table->timestamps();

            $table->unique(['level_id', 'sequence']);
            $table->index('is_published');
        });
    }

    public function down(): void {
        Schema::dropIfExists('units');
    }
};
