<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\PaymentSuccessNotification;
use Illuminate\Support\Facades\DB;

/**
 * سرویس تجارت: سفارش، پرداخت، فعال‌سازی دسترسی، کیف پول و کش‌بک
 */
class CommerceService
{
    public function generateOrderNumber(): string
    {
        $jy = now()->format('y');
        $last = DB::table('orders')->max('id') ?? 0;
        return 'KEY-' . $jy . '-' . str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }

    /** ثبت سفارش از آیتم‌های سبد */
    public function createOrder(User $user, array $items, ?int $couponId = null, float $discount = 0): Order
    {
        return DB::transaction(function () use ($user, $items, $couponId, $discount) {
            $total = collect($items)->sum(fn ($i) => $i['price'] * $i['quantity']);
            $payable = max(0, $total - $discount);

            $order = Order::create([
                'user_id' => $user->id,
                'number' => $this->generateOrderNumber(),
                'total' => $total,
                'discount' => $discount,
                'payable' => $payable,
                'coupon_id' => $couponId,
                'status' => 'pending',
                'description' => collect($items)->pluck('title')->implode('، '),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'purchasable_type' => $item['purchasable_type'],
                    'purchasable_id' => $item['purchasable_id'],
                    'title' => $item['title'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'] ?? 1,
                ]);
            }

            return $order;
        });
    }

    /** پرداخت موفق: ثبت تراکنش، فعال‌سازی دسترسی‌ها، کش‌بک و اعلان */
    public function markPaid(Order $order, string $gateway, string $ref): Order
    {
        return DB::transaction(function () use ($order, $gateway, $ref) {
            if ($order->status === 'paid') {
                return $order; // جلوگیری از پرداخت تکراری
            }

            $order->forceFill(['status' => 'paid', 'gateway' => $gateway, 'paid_at' => now()])->save();

            // اگر درگاه تراکنشِ در انتظاری برای این سفارش آغاز کرده بود، همان تراکنش به موفق تبدیل می‌شود؛
            // وگرنه (مثلاً تأیید دستی مدیر) تراکنش موفق جدیدی ثبت می‌گردد.
            $pending = $order->transactions()
                ->where('type', 'payment')->where('status', 'pending')->latest('id')->first();

            if ($pending) {
                $pending->forceFill([
                    'amount' => $order->payable,
                    'status' => 'success',
                    'gateway' => $gateway,
                    'gateway_ref' => $ref,
                    'paid_at' => now(),
                ])->save();
            } else {
                $order->transactions()->create([
                    'user_id' => $order->user_id,
                    'amount' => $order->payable,
                    'type' => 'payment',
                    'status' => 'success',
                    'gateway' => $gateway,
                    'gateway_ref' => $ref,
                    'paid_at' => now(),
                ]);
            }

            if ($order->coupon_id) {
                $order->coupon()->increment('used_count');
            }

            foreach ($order->items as $item) {
                $this->activatePurchase($order->user, $item);
            }

            // کش‌بک
            $cashbackPercent = (float) setting('commerce.cashback_percent', 0);
            if ($cashbackPercent > 0) {
                $cashback = round($order->payable * $cashbackPercent / 100);
                if ($cashback > 0) {
                    $this->addToWallet($order->user, $cashback, 'cashback', 'کش‌بک خرید سفارش ' . $order->number, $order->id);
                }
            }

            $order->user->notify(new PaymentSuccessNotification($order));
            \App\Models\AuditLog::record($order->user, 'order.paid', $order, ['gateway' => $gateway, 'ref' => $ref]);

            return $order;
        });
    }

    /** فعال‌سازی خودکار دسترسی پس از پرداخت موفق */
    public function activatePurchase(User $user, OrderItem $item): void
    {
        if ($item->purchasable_type === 'course') {
            $course = Course::find($item->purchasable_id);
            if ($course) {
                CourseEnrollmentService::enroll($user, $course, 'purchase', $item->order_id);
            }
        } elseif ($item->purchasable_type === 'plan') {
            $plan = SubscriptionPlan::find($item->purchasable_id);
            if ($plan) {
                $this->grantSubscription($user, $plan, 'purchase', $item->order_id);
            }
        }
        // محصول فیزیکی نیاز به فعال‌سازی ندارد
    }

    public function grantSubscription(User $user, SubscriptionPlan $plan, string $source = 'grant', ?int $orderId = null): Subscription
    {
        $current = $user->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->first();
        $start = $current ? $current->ends_at : now();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays($plan->duration_days),
            'status' => 'active',
            'source' => $source,
            'order_id' => $orderId,
        ]);

        if ($current) {
            $current->forceFill(['status' => 'expired'])->save();
        }

        \App\Models\AuditLog::record($user, 'subscription.granted', $subscription, ['plan' => $plan->title, 'source' => $source]);

        return $subscription;
    }

    public function addToWallet(User $user, float $amount, string $type, string $description, ?int $orderId = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $description, $orderId) {
            $last = WalletTransaction::query()->where('user_id', $user->id)->latest('id')->lockForUpdate()->first();
            $balance = (float) ($last->balance_after ?? 0) + $amount;

            return WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $balance,
                'type' => $type,
                'description' => $description,
                'order_id' => $orderId,
            ]);
        });
    }
}
