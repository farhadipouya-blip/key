<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // نقش‌های سامانه: مدیر کل، مدیر، کارشناس، مشاور، مدرس، تولیدکننده محتوا، دانشجو
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60); // نام فارسی
            $table->string('slug', 40)->unique(); // superadmin, admin, expert, counselor, teacher, content_creator, student
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // نام فارسی
            $table->string('slug', 80)->unique(); // مثل exams.manage
            $table->string('group', 40)->default('general'); // گروه ماژول
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->unique(['role_id', 'user_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('text'); // text|number|bool|json
            $table->string('group', 40)->default('general');
            $table->string('label', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });

        // مدیریت فونت
        Schema::create('fonts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('family', 100); // نام خانواده CSS
            $table->string('slug', 100)->unique();
            $table->json('weights')->nullable(); // [400, 700]
            $table->string('file_path');
            $table->string('format', 10)->default('woff2'); // ttf|woff|woff2
            $table->string('license_name', 120)->nullable();
            $table->text('license_note')->nullable();
            $table->string('status', 20)->default('installed'); // installed|uninstalled
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // اعلان‌های درون‌برنامه‌ای (کانال دیتابیس Laravel)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('fonts');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
