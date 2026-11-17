<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\CommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_percent_coupon_discount(): void
    {
        $coupon = Coupon::create([
            'code' => 'TEST50',
            'type' => 'percent',
            'value' => 50,
            'is_active' => true,
        ]);

        $this->assertEquals(50000.0, $coupon->discountFor(100000));
        $this->assertEquals(750.0, $coupon->discountFor(1500));
    }

    public function test_fixed_coupon_discount_caps_at_amount(): void
    {
        $coupon = Coupon::create([
            'code' => 'TESTFIX',
            'type' => 'fixed',
            'value' => 30000,
            'is_active' => true,
        ]);

        $this->assertEquals(30000.0, $coupon->discountFor(100000));
        $this->assertEquals(5000.0, $coupon->discountFor(5000));
    }

    public function test_expired_coupon_has_no_discount(): void
    {
        $coupon = Coupon::create([
            'code' => 'TESTEXP',
            'type' => 'percent',
            'value' => 80,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertEquals(0.0, $coupon->discountFor(100000));
    }

    public function test_order_paid_activates_subscription(): void
    {
        $service = app(CommerceService::class);
        $user = User::create([
            'name' => 'خریدار تست',
            'email' => 'commerce-test@key.test',
            'password' => bcrypt('Admin@1234'),
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'اشتراک ماهانه تست',
            'title' => 'اشتراک ماهانه تست',
            'slug' => 'monthly-test',
            'code' => 'monthly-test',
            'price' => 290000,
            'duration_days' => 30,
            'is_active' => true,
            'sort' => 1,
        ]);

        $order = $service->createOrder($user, [
            [
                'purchasable_type' => 'plan',
                'purchasable_id' => $plan->id,
                'title' => $plan->title,
                'price' => (float) $plan->price,
                'quantity' => 1,
            ],
        ]);
        $this->assertNotEquals('paid', $order->status);

        $service->markPaid($order, 'test-gateway', 'TEST-REF-1');

        $order->refresh();
        $this->assertEquals('paid', $order->status);
        $this->assertTrue($user->hasActiveSubscription());
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'source' => 'purchase',
        ]);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'status' => 'success',
        ]);
    }
}
