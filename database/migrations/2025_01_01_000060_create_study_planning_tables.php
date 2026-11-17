<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active'); // active|completed|canceled
            $table->string('generated_by', 20)->default('manual'); // manual|smart|ai
            $table->unsignedInteger('weekly_target_minutes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('study_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('topic', 200)->nullable();
            $table->unsignedInteger('target_minutes')->default(60);
            $table->unsignedInteger('completed_minutes')->default(0);
            $table->string('status', 20)->default('pending'); // pending|done|partial|missed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->unsignedInteger('minutes');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period', 20)->default('monthly'); // daily|weekly|monthly
            $table->unsignedInteger('target_minutes')->nullable();
            $table->unsignedInteger('target_questions')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });

        // سطح تسلط در هر مبحث
        Schema::create('mastery_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('level', 5, 2)->default(0); // 0 تا 100
            $table->unsignedInteger('attempted')->default(0);
            $table->unsignedInteger('correct')->default(0);
            $table->decimal('retention', 5, 2)->nullable(); // ماندگاری مرور
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mastery_levels');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('study_sessions');
        Schema::dropIfExists('plan_items');
        Schema::dropIfExists('study_plans');
    }
};
