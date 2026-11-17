<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('avatar_path');
            $table->string('national_code', 15)->nullable()->after('bio');
            $table->string('field_group', 30)->nullable()->after('national_code'); // گروه کنکور
            $table->string('grade', 30)->nullable()->after('field_group'); // پایه تحصیلی
            $table->string('city', 60)->nullable()->after('grade');
            $table->boolean('is_active')->default(true)->after('city');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->timestamp('last_seen_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'avatar_path', 'bio', 'national_code', 'field_group',
                'grade', 'city', 'is_active', 'last_login_at', 'last_seen_at',
            ]);
        });
    }
};
