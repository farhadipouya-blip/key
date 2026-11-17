<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // بسته‌های اشتراک
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 0)->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->json('features')->nullable(); // ["آزمون نامحدود", ...]
            $table->string('badge', 40)->nullable(); // پیشنهاد ویژه
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 20)->default('active'); // active|expired|canceled
            $table->string('source', 20)->default('purchase'); // purchase|grant|trial
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 10)->default('percent'); // percent|fixed
            $table->decimal('value', 12, 0)->default(0);
            $table->decimal('min_amount', 12, 0)->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // محصولات آموزشی متفرقه
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->decimal('price', 12, 0)->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique(); // مثل KEY-1403-000123
            $table->decimal('total', 12, 0)->default(0);
            $table->decimal('discount', 12, 0)->default(0);
            $table->decimal('payable', 12, 0)->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending|paid|failed|canceled
            $table->text('description')->nullable();
            $table->string('gateway', 40)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('purchasable_type', 40); // course|plan|product
            $table->unsignedBigInteger('purchasable_id');
            $table->string('title', 200); // اسنپ‌شات عنوان
            $table->decimal('price', 12, 0)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 0);
            $table->string('type', 20)->default('payment'); // payment|refund|wallet_charge|cashback
            $table->string('status', 20)->default('pending'); // pending|success|failed
            $table->string('gateway', 40)->nullable();
            $table->string('gateway_ref', 100)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 0); // مثبت/منفی
            $table->decimal('balance_after', 12, 0)->default(0);
            $table->string('type', 20); // deposit|purchase|cashback|refund|manual
            $table->string('description', 300)->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
