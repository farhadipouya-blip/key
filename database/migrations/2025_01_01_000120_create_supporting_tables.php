<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تخصیص دانشجو به مشاور
        Schema::create('counselor_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active'); // active|archived
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['counselor_id', 'student_id']);
        });

        Schema::create('counseling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('mode', 20)->default('online'); // online|phone|in_person
            $table->text('summary')->nullable(); // خلاصه جلسه
            $table->text('recommendations')->nullable(); // توصیه‌ها
            $table->string('status', 20)->default('scheduled'); // scheduled|done|canceled
            $table->timestamps();

            $table->index(['counselor_id', 'scheduled_at']);
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('note');
            $table->date('next_date')->nullable();
            $table->string('status', 20)->default('open'); // open|done
            $table->timestamps();
        });

        // تخمین رتبه و انتخاب رشته
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('city', 80)->nullable();
            $table->string('type', 30)->default('state'); // state|azad|payam|nonprofit|international
            $table->string('website', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('code', 30)->nullable();
            $table->string('group', 30)->default('math'); // math|experimental|humanities|arts|language
            $table->string('degree', 30)->default('bachelor'); // associate|bachelor|master|phd
            $table->timestamps();
        });

        // حدنصاب قبولی هر سال
        Schema::create('major_university', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('last_rank_min')->nullable();
            $table->unsignedInteger('last_rank_max')->nullable(); // آخرین نفر قبولی (رتبه کشوری)
            $table->string('tuition_type', 30)->default('daily'); // daily=روزانه|night=شبانه|self=خودگردان|full=پرداخت‌محور
            $table->timestamps();

            $table->unique(['university_id', 'major_id', 'year', 'tuition_type'], 'mu_unique');
        });

        Schema::create('rank_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('exam_attempt_id')->nullable();
            $table->json('input')->nullable(); // درصدهای دروس
            $table->unsignedInteger('estimated_rank')->nullable();
            $table->decimal('percentile', 5, 2)->nullable();
            $table->json('scenario')->nullable(); // best|expected|worst
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('field_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200)->nullable();
            $table->json('items')->nullable(); // [{major_university_id, priority, chance}]
            $table->json('analysis')->nullable();
            $table->string('status', 20)->default('draft'); // draft|final
            $table->timestamps();
        });

        // اطلاعات تاریخی رتبه و کارنامه
        Schema::create('historical_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('exam_name', 100)->nullable();
            $table->decimal('total_percent', 5, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->json('subjects')->nullable(); // [{"title":"ریاضی","percent":85,"count":60,"wrong":10,"blank":5}]
            $table->string('source', 20)->default('manual'); // manual|import
            $table->unsignedBigInteger('file_import_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'year']);
        });

        // کتابخانه رسانه
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name', 200);
            $table->string('file_name', 300);
            $table->string('path', 500);
            $table->string('disk', 30)->default('local');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('type', 20)->default('other'); // image|video|audio|pdf|document|archive|other
            $table->string('access', 20)->default('private'); // private|public|staff
            $table->json('meta')->nullable();
            $table->boolean('is_scanned')->default(false);
            $table->string('scan_result', 40)->nullable(); // ok|blocked
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamps();

            $table->index(['type', 'access']);
        });

        // ورود گروهی اطلاعات (Excel/Word)
        Schema::create('file_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('questions'); // questions|historical
            $table->string('file_path', 500);
            $table->string('original_name', 200)->nullable();
            $table->string('status', 20)->default('pending'); // pending|preview|completed|failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->json('errors')->nullable();
            $table->json('stats')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // هوش مصنوعی
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200)->nullable();
            $table->string('context_type', 40)->nullable(); // coach|ticket|report
            $table->unsignedBigInteger('context_id')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20); // user|assistant|system
            $table->longText('content');
            $table->unsignedInteger('tokens')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('file_imports');
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_folders');
        Schema::dropIfExists('historical_results');
        Schema::dropIfExists('field_selections');
        Schema::dropIfExists('rank_estimates');
        Schema::dropIfExists('major_university');
        Schema::dropIfExists('majors');
        Schema::dropIfExists('universities');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('counseling_sessions');
        Schema::dropIfExists('counselor_student');
    }
};
