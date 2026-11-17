<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مرور فاصله‌دار (الگوریتم SM-2)
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reviewable_type', 60); // Question | Summary
            $table->unsignedBigInteger('reviewable_id');
            $table->string('status', 20)->default('active'); // active|completed
            $table->decimal('easiness', 4, 2)->default(2.50); // ضریب سهولت SM-2
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->unsignedInteger('lapses')->default(0); // فراموشی‌ها
            $table->unsignedInteger('total_reviews')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reviewable_type', 'reviewable_id']);
            $table->index(['user_id', 'status', 'due_at']);
        });

        Schema::create('review_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quality'); // 1=دوباره 2=سخت 3=خوب 4=آسان
            $table->unsignedInteger('interval_before')->default(0);
            $table->unsignedInteger('interval_after')->default(0);
            $table->decimal('easiness_after', 4, 2)->default(2.5);
            $table->timestamp('reviewed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_logs');
        Schema::dropIfExists('reviews');
    }
};
