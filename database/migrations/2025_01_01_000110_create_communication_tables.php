<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 200);
            $table->string('category', 80)->nullable(); // فنی|آموزشی|مالی|پیشنهاد
            $table->string('priority', 20)->default('normal'); // low|normal|high|urgent
            $table->string('status', 20)->default('open'); // open|answered|pending|closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->boolean('is_staff')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        // چت آنلاین
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('private'); // private|group|class
            $table->string('name', 120)->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete(); // اتاق کلاس
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_room_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained('chat_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->unique(['chat_room_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained('chat_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = پیام سیستمی
            $table->string('type', 20)->default('text'); // text|file|image|audio|poll|system
            $table->text('body')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 200)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable(); // پیام صوتی
            $table->json('poll_options')->nullable(); // ["گزینه۱","گزینه۲"]
            $table->unsignedBigInteger('reply_to')->nullable();
            $table->timestamps();

            $table->index(['chat_room_id', 'id']);
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('option_index');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['chat_message_id', 'user_id']);
        });

        // پیامک
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('body', 500);
            $table->string('template', 60)->nullable(); // otp|purchase|exam|reminder...
            $table->string('provider', 40)->nullable();
            $table->string('status', 20)->default('pending'); // pending|sent|failed|simulated
            $table->string('provider_ref', 100)->nullable();
            $table->string('error', 300)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_room_user');
        Schema::dropIfExists('chat_rooms');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
