<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // بانک خلاصه و نکته (خلاصه عمومی با user_id=null یا خلاصه شخصی)
        Schema::create('summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // null = عمومی
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->longText('content');
            $table->string('category', 80)->nullable(); // خلاصه|نکته|فرمول|خط‌کشی
            $table->json('tags')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'category']);
        });

        Schema::create('summary_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('summary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bookmarkable_type', 60);
            $table->unsignedBigInteger('bookmarkable_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('summary_notes');
        Schema::dropIfExists('summaries');
    }
};
