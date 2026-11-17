<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // کلاس آنلاین (اتصال به اسکای‌روم / الوکام)
        Schema::create('online_classes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('platform', 20)->default('skyroom'); // skyroom|alokam|other
            $table->string('meeting_id', 100)->nullable();
            $table->string('join_url', 500)->nullable();
            $table->string('host_url', 500)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('scheduled'); // scheduled|live|finished|canceled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('class_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('online_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('status', 20)->default('absent'); // joined|absent
            $table->timestamps();

            $table->unique(['class_id', 'user_id']);
        });

        // تکلیف و ارزیابی
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->longText('description');
            $table->timestamp('due_at')->nullable();
            $table->decimal('max_score', 6, 2)->default(100);
            $table->unsignedBigInteger('media_id')->nullable(); // فایل پیوست تکلیف
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('course_id');
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('text_answer')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 20)->default('submitted'); // submitted|graded|returned
            $table->decimal('score', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('graded_at')->nullable();
            $table->unique(['assignment_id', 'user_id']);
        });

        // گواهینامه
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('exam_attempt_id')->nullable();
            $table->string('type', 10)->default('fa'); // fa|en
            $table->string('uuid', 36)->unique();
            $table->json('data')->nullable(); // نام، دوره، امتیاز و...
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->string('template', 30)->default('default'); // default|classic
            $table->timestamps();

            $table->index('user_id');
        });

        // مطالعه PDF
        Schema::create('pdf_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_page')->default(1);
            $table->unsignedInteger('total_pages')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('last_read_at')->nullable();
            $table->unique(['media_id', 'user_id']);
        });

        // هایلایت و یادداشت PDF
        Schema::create('highlights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page');
            $table->text('text')->nullable();
            $table->text('note')->nullable();
            $table->string('color', 20)->default('yellow');
            $table->timestamps();

            $table->index(['media_id', 'user_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('highlights');
        Schema::dropIfExists('pdf_reads');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('class_attendances');
        Schema::dropIfExists('online_classes');
    }
};
