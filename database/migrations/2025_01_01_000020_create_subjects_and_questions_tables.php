<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // درخت دروس: درس > فصل > مبحث
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('subjects')->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('level', 20)->default('root'); // root=درس | chapter=فصل | lesson=مبحث
            $table->unsignedInteger('order')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'order']);
        });

        // بانک سؤال
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('multi'); // multi=تستی | descriptive=تشریحی
            $table->string('difficulty', 20)->default('medium'); // easy|medium|hard
            $table->longText('text'); // متن سؤال (قابل درج فرمول $LaTeX$)
            $table->string('image_path')->nullable();
            $table->text('answer_text')->nullable(); // پاسخ تشریحی
            $table->text('explanation')->nullable(); // توضیح/نکته
            $table->string('source', 150)->nullable(); // منبع سؤال
            $table->unsignedSmallInteger('year')->nullable(); // سال طرح سؤال
            $table->decimal('default_marks', 6, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_id', 'type']);
            $table->index('difficulty');
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('order'); // 1..4
            $table->text('text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->index(['question_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('subjects');
    }
};
