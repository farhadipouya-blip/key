<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('type', 20)->default('timed'); // practice=تمرین | timed=زمان‌دار | simulator=شبیه‌ساز | homework=تکلیف
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('duration_minutes')->nullable(); // null = بدون محدودیت زمان
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->boolean('negative_marking')->default(false);
            $table->decimal('negative_value', 4, 2)->nullable(); // مثل 0.25
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('is_public')->default(false);
            $table->string('status', 20)->default('draft'); // draft|published|closed
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->decimal('pass_percent', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'is_public']);
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->decimal('marks', 6, 2)->default(1);
            $table->unique(['exam_id', 'question_id']);
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress|finished|abandoned
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('percent', 5, 2)->nullable();
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->unsignedInteger('blank_count')->default(0);
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'exam_id']);
            $table->index('status');
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->text('text_answer')->nullable(); // پاسخ تشریحی
            $table->boolean('is_correct')->nullable(); // null = نزده
            $table->decimal('marks', 6, 2)->default(1);
            $table->decimal('score', 6, 2)->default(0);
            $table->boolean('flagged')->default(false); // علامت‌گذاری برای مرور بعداً
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['attempt_id', 'question_id']);

            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
    }
};
