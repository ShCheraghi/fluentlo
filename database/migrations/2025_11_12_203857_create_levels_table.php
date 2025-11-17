<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('کد سطح: A1, A2, B1, ...');
            $table->string('name_fa')->comment('نام فارسی سطح');
            $table->string('name_en')->comment('نام انگلیسی سطح');
            $table->text('description_fa')->nullable()->comment('توضیح فارسی (اختیاری)');
            $table->text('description_en')->nullable()->comment('توضیح انگلیسی (اختیاری)');
            $table->integer('order')->unique()->comment('ترتیب نمایش سطح (مثلاً 1 تا 6)');
            $table->string('icon')->nullable()->comment('آیکن یا ایموجی برای نمایش');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('levels');
    }
};
