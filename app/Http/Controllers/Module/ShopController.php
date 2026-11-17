<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\WalletTransaction;
use App\Notifications\WalletChargeNotification;
use App\Services\CommerceService;
use App\Services\CourseEnrollmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * فروشگاه، سبد خرید، پرداخت آزمایشی، سفارش‌ها و کیف پول
 */
class ShopController extends Controller
{
    public function __construct(private CommerceService $commerce) {}

    // ------------------------------------------------------------------
    // فروشگاه
    // ------------------------------------------------------------------

    public function index()
    {
        $user = Auth::user();

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)->orderBy('sort')->orderBy('id')->get();

        $paidCourses = Course::query()
            ->where('is_published', true)->where('is_free', false)
            ->with('teacher')->orderBy('id')->get();

        $freeCourses = Course::query()
            ->where('is_published', true)->where('is_free', true)
            ->with('teacher')->orderBy('id')->get();

        $products = Product::query()
            ->where('is_published', true)->orderBy('id')->get();

        return view('shop.index', compact('plans', 'paidCourses', 'freeCourses', 'products', 'user'));
    }

    // ------------------------------------------------------------------
    // سبد خرید (نشست)
    // ------------------------------------------------------------------

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchasable_type' => ['required', Rule::in(['course', 'plan', 'product'])],
            'purchasable_id' => ['required', 'integer', 'min:1'],
        ], [
            'purchasable_type.required' => 'نوع آیتم خرید الزامی است',
            'purchasable_type.in' => 'نوع آیتم خرید نامعتبر است',
            'purchasable_id.required' => 'شناسه آیتم خرید الزامی است',
        ]);

        $model = $this->resolvePurchasable($data['purchasable_type'], (int) $data['purchasable_id']);

        if (! $model) {
            return back()->with('error', 'آیتم موردنظر یافت نشد یا غیرفعال است.');
        }

        // دوره رایگان: ثبت‌نام مستقیم بدون عبور از سبد
        if ($data['purchasable_type'] === 'course' && $model->is_free) {
            if ($model->isEnrolled(Auth::user())) {
                return back()->with('info', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
            }
            CourseEnrollmentService::enroll(Auth::user(), $model, 'free');
            return back()->with('success', 'ثبت‌نام رایگان در دوره «' . $model->title . '» انجام شد.');
        }

        if ($data['purchasable_type'] === 'course' && $model->isEnrolled(Auth::user())) {
            return back()->with('info', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید و نیازی به خرید ندارید.');
        }

        $key = $data['purchasable_type'] . ':' . $model->id;
        $cart = session('cart', []);

        if (! isset($cart[$key])) {
            $cart[$key] = [
                'key' => $key,
                'purchasable_type' => $data['purchasable_type'],
                'purchasable_id' => $model->id,
                'title' => $this->titleOf($data['purchasable_type'], $model),
                'price' => (float) $model->price,
                'quantity' => 1,
            ];
            session(['cart' => $cart]);
        }

        return redirect()->route('shop.cart')->with('success', 'آیتم «' . $this->titleOf($data['purchasable_type'], $model) . '» به سبد خرید افزوده شد.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $request->validate(['key' => ['required', 'string', 'max:50']]);
        $cart = session('cart', []);

        if (isset($cart[$request->key])) {
            unset($cart[$request->key]);
            session(['cart' => $cart]);
            // اگر آیتمی باقی نماند کوپن هم پاک شود (حداقل مبلغ ممکن است برقرار نباشد)
            if ($cart === []) {
                session()->forget('cart_coupon');
            }
            return back()->with('success', 'آیتم از سبد خرید حذف شد.');
        }

        return back()->with('error', 'آیتم موردنظر در سبد یافت نشد.');
    }

    public function cart()
    {
        [$items, $dropped] = $this->freshCartItems();

        if ($dropped) {
            $this->saveCart($items);
            session()->flash('info', 'برخی از آیتم‌های سبد دیگر قابل خرید نبودند و حذف شدند.');
        }

        [$total, $discount, $coupon, $payable] = $this->totals($items);

        return view('shop.cart', compact('items', 'total', 'discount', 'coupon', 'payable'));
    }

    public function coupon(Request $request): RedirectResponse
    {
        $action = $request->input('action', 'apply');

        if ($action === 'remove') {
            session()->forget('cart_coupon');
            return back()->with('info', 'کد تخفیف از سبد حذف شد.');
        }

        $request->validate(['code' => ['required', 'string', 'max:40']], [
            'code.required' => 'وارد کردن کد تخفیف الزامی است',
        ]);

        $cart = $this->freshCartItems()[0];
        $total = collect($cart)->sum(fn ($i) => $i['price'] * $i['quantity']);

        if ($total <= 0) {
            return back()->with('error', 'سبد خرید شما خالی است.');
        }

        $coupon = Coupon::query()->where('code', mb_strtoupper(trim($request->code)))->first();

        if (! $coupon || ! $coupon->isValid()) {
            return back()->with('error', 'کد تخفیف نامعتبر است یا منقضی/تکمیل‌ظرفیت شده است.');
        }

        $discount = $coupon->discountFor($total);

        if ($discount <= 0) {
            return back()->with('error', 'این کد تخفیف برای مبلغ سبد فعلی قابل اعمال نیست (حداقل مبلغ: ' . money($coupon->min_amount ?: 0) . ').');
        }

        session(['cart_coupon' => $coupon->id]);

        return back()->with('success', 'کد تخفیف «' . $coupon->code . '» اعمال شد — تخفیف: ' . money($discount));
    }

    // ------------------------------------------------------------------
    // تسویه حساب و درگاه پرداخت آزمایشی
    // ------------------------------------------------------------------

    public function checkout(): RedirectResponse
    {
        [$items, $dropped] = $this->freshCartItems();

        if ($items === []) {
            return redirect()->route('shop.cart')->with('error', 'سبد خرید شما خالی است.');
        }

        if ($dropped) {
            $this->saveCart($items);
            session()->flash('info', 'برخی آیتم‌های سبد دیگر قابل خرید نبودند و حذف شدند. لطفاً مجدداً تسویه کنید.');
            return redirect()->route('shop.cart');
        }

        $coupon = $this->sessionCoupon();

        return $this->placeCheckout(Auth::user(), $items, $coupon, true);
    }

    /** خرید مستقیم اشتراک (سبد تک‌آیتمی → تسویه) */
    public function subscribe(SubscriptionPlan $plan): RedirectResponse
    {
        if (! $plan->is_active) {
            return back()->with('error', 'این بسته اشتراک در حال حاضر فعال نیست.');
        }

        session([
            'cart' => [
                'plan:' . $plan->id => [
                    'key' => 'plan:' . $plan->id,
                    'purchasable_type' => 'plan',
                    'purchasable_id' => $plan->id,
                    'title' => $plan->title,
                    'price' => (float) $plan->price,
                    'quantity' => 1,
                ],
            ],
            'cart_coupon' => null,
        ]);

        return $this->checkout();
    }

    /** خرید مستقیم دوره/محصول (بدون عبور از صفحه سبد) */
    public function buyNow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purchasable_type' => ['required', Rule::in(['course', 'plan', 'product'])],
            'purchasable_id' => ['required', 'integer', 'min:1'],
        ], [
            'purchasable_type.in' => 'نوع آیتم خرید نامعتبر است',
        ]);

        $model = $this->resolvePurchasable($data['purchasable_type'], (int) $data['purchasable_id']);

        if (! $model) {
            return back()->with('error', 'آیتم موردنظر یافت نشد یا غیرفعال است.');
        }

        if ($data['purchasable_type'] === 'course') {
            if ($model->is_free) {
                if (! $model->isEnrolled(Auth::user())) {
                    CourseEnrollmentService::enroll(Auth::user(), $model, 'free');
                    return back()->with('success', 'ثبت‌نام رایگان در دوره «' . $model->title . '» انجام شد.');
                }
                return back()->with('info', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
            }
            if ($model->isEnrolled(Auth::user())) {
                return back()->with('info', 'شما قبلاً در این دوره ثبت‌نام کرده‌اید.');
            }
        }

        session([
            'cart' => [
                $data['purchasable_type'] . ':' . $model->id => [
                    'key' => $data['purchasable_type'] . ':' . $model->id,
                    'purchasable_type' => $data['purchasable_type'],
                    'purchasable_id' => $model->id,
                    'title' => $this->titleOf($data['purchasable_type'], $model),
                    'price' => (float) $model->price,
                    'quantity' => 1,
                ],
            ],
            'cart_coupon' => null,
        ]);

        return $this->checkout();
    }

    /** ساخت سفارش + تراکنش در انتظار و هدایت به درگاه */
    private function placeCheckout($user, array $items, ?Coupon $coupon, bool $clearCart): RedirectResponse
    {
        $order = $this->commerce->createOrder(
            $user,
            $items,
            $coupon?->id,
            $coupon ? $coupon->discountFor(collect($items)->sum(fn ($i) => $i['price'] * $i['quantity'])) : 0
        );

        AuditLog::record($user, 'order.created', $order, [
            'total' => $order->total,
            'discount' => $order->discount,
            'payable' => $order->payable,
            'items' => collect($items)->count(),
        ]);

        $transaction = $this->startTransaction($user->id, $order->id, (float) $order->payable, 'payment', [
            'purpose' => 'order_payment',
            'order_number' => $order->number,
            'description' => $order->description,
        ]);

        session()->forget(['cart', 'cart_coupon']);

        return redirect()->route('shop.gateway', $transaction)
            ->with('success', 'سفارش ' . $order->number . ' ثبت شد. برای تکمیل خرید، پرداخت را در درگاه انجام دهید.');
    }

    /** صفحه شبیه‌سازی درگاه پرداخت */
    public function gateway(Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);

        if ($transaction->status !== 'pending') {
            return redirect()->route('shop.pay.result', $transaction);
        }

        $order = $transaction->type === 'payment' ? $transaction->order : null;

        return view('shop.gateway', [
            'transaction' => $transaction,
            'order' => $order,
            'isWallet' => $transaction->type === 'wallet_charge',
        ]);
    }

    /** بازگشت از درگاه: موفق یا ناموفق */
    public function gatewayCallback(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransaction($transaction);

        $data = $request->validate([
            'result' => ['required', Rule::in(['success', 'failed'])],
        ], [
            'result.in' => 'وضعیت بازگشت از درگاه نامعتبر است',
        ]);

        if ($transaction->status !== 'pending') {
            return redirect()->route('shop.pay.result', $transaction)
                ->with('info', 'این تراکنش قبلاً پردازش شده است.');
        }

        $user = Auth::user();
        $ref = 'MOCK-' . strtoupper(bin2hex(random_bytes(5)));

        if ($data['result'] === 'success') {
            if ($transaction->type === 'payment') {
                $order = $transaction->order;

                if (! $order) {
                    return redirect()->route('shop.cart')->with('error', 'سفارش مرتبط با این تراکنش یافت نشد.');
                }

                if ($order->status === 'paid') {
                    $transaction->forceFill(['meta' => array_merge($transaction->meta ?? [], ['note' => 'سفارش قبلاً پرداخت شده بود'])])->save();
                    return redirect()->route('shop.pay.result', $transaction)->with('info', 'این سفارش قبلاً پرداخت شده است.');
                }

                $this->commerce->markPaid($order, (string) $transaction->gateway, $ref);

                $transaction->forceFill([
                    'meta' => array_merge($transaction->meta ?? [], ['result' => 'success', 'gateway_ref' => $ref]),
                ])->save();

                return redirect()->route('shop.pay.result', $transaction)
                    ->with('success', 'پرداخت با موفقیت انجام شد و دسترسی‌های خرید فعال گردید.');
            }

            // شارژ کیف پول
            DB::transaction(function () use ($transaction, $user, $ref) {
                $transaction->forceFill([
                    'status' => 'success',
                    'gateway_ref' => $ref,
                    'paid_at' => now(),
                    'meta' => array_merge($transaction->meta ?? [], ['result' => 'success']),
                ])->save();

                $this->commerce->addToWallet($user, (float) $transaction->amount, 'deposit', 'شارژ کیف پول از طریق درگاه آزمایشی (' . $ref . ')');

                AuditLog::record($user, 'wallet.charged', $transaction, [
                    'amount' => $transaction->amount,
                    'gateway' => $transaction->gateway,
                    'ref' => $ref,
                ]);

                $user->notify(new WalletChargeNotification((float) $transaction->amount, $ref));
            });

            return redirect()->route('shop.pay.result', $transaction)
                ->with('success', 'کیف پول شما با موفقیت شارژ شد.');
        }

        // پرداخت ناموفق / انصراف
        $transaction->forceFill([
            'status' => 'failed',
            'meta' => array_merge($transaction->meta ?? [], ['result' => 'failed', 'reason' => 'پرداخت ناموفق یا انصراف کاربر در درگاه']),
        ])->save();

        if ($transaction->type === 'payment' && $transaction->order) {
            $transaction->order->forceFill(['status' => 'failed'])->save();
            AuditLog::record($user, 'order.payment_failed', $transaction->order, ['gateway' => $transaction->gateway]);
        } else {
            AuditLog::record($user, 'wallet.charge_failed', $transaction, ['gateway' => $transaction->gateway]);
        }

        return redirect()->route('shop.pay.result', $transaction)
            ->with('error', 'پرداخت ناموفق بود یا از درگاه انصراف دادید.');
    }

    /** نتیجه پرداخت (بعد از بازگشت از درگاه) */
    public function payResult(Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);

        $order = $transaction->type === 'payment' ? $transaction->order : null;
        $receipt = null;

        if ($order) {
            $receipt = $order->transactions()->where('status', 'success')->latest('id')->first();
        }

        return view('shop.checkout-result', compact('transaction', 'order', 'receipt'));
    }

    // ------------------------------------------------------------------
    // سفارش‌های من و فاکتور
    // ------------------------------------------------------------------

    public function orders()
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->withCount('items')
            ->latest('id')
            ->paginate(15);

        return view('shop.orders', compact('orders'));
    }

    public function invoice(Order $order)
    {
        if ($order->user_id !== Auth::id() && ! Auth::user()->canAccess('shop.manage')) {
            abort(403, 'این سفارش متعلق به شما نیست.');
        }

        $order->load(['items', 'coupon', 'transactions' => fn ($q) => $q->latest('id'), 'user']);

        return view('shop.invoice', compact('order'));
    }

    /** پرداخت مجدد سفارش‌های در انتظار یا ناموفق */
    public function retry(Order $order): RedirectResponse
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'این سفارش متعلق به شما نیست.');
        }

        if (in_array($order->status, ['paid', 'canceled'], true)) {
            return back()->with('error', 'امکان پرداخت مجدد برای این سفارش وجود ندارد.');
        }

        // اگر تراکنش در انتظاری وجود دارد به همان درگاه برود
        $pending = $order->transactions()->where('status', 'pending')->latest('id')->first();
        if ($pending) {
            return redirect()->route('shop.gateway', $pending);
        }

        $transaction = $this->startTransaction(Auth::id(), $order->id, (float) $order->payable, 'payment', [
            'purpose' => 'order_payment_retry',
            'order_number' => $order->number,
            'description' => $order->description,
        ]);

        return redirect()->route('shop.gateway', $transaction)->with('info', 'برای سفارش ' . $order->number . ' به درگاه پرداخت هدایت شدید.');
    }

    // ------------------------------------------------------------------
    // کیف پول
    // ------------------------------------------------------------------

    public function wallet()
    {
        $user = Auth::user();
        $balance = $user->walletBalance();
        $transactions = $user->walletTransactions()->latest('id')->take(50)->get();

        return view('shop.wallet', compact('balance', 'transactions'));
    }

    public function walletCharge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:10000', 'max:500000000'],
        ], [
            'amount.required' => 'مبلغ شارژ الزامی است',
            'amount.integer' => 'مبلغ باید عدد صحیح باشد',
            'amount.min' => 'حداقل مبلغ شارژ ۱۰,۰۰۰ تومان است',
            'amount.max' => 'مبلغ شارژ بیش از حد مجاز است',
        ]);

        $transaction = $this->startTransaction(Auth::id(), null, (float) $data['amount'], 'wallet_charge', [
            'purpose' => 'wallet_charge',
        ]);

        return redirect()->route('shop.gateway', $transaction)
            ->with('info', 'برای شارژ کیف پول به درگاه پرداخت هدایت شدید.');
    }

    // ------------------------------------------------------------------
    // متدهای کمکی
    // ------------------------------------------------------------------

    /** یافتن مدل قابل خرید بر اساس نوع و شناسه (فقط آیتم‌های فعال/منتشرشده) */
    private function resolvePurchasable(string $type, int $id): ?Model
    {
        return match ($type) {
            'course' => Course::query()->where('is_published', true)->find($id),
            'plan' => SubscriptionPlan::query()->where('is_active', true)->find($id),
            'product' => Product::query()->where('is_published', true)->find($id),
            default => null,
        };
    }

    private function titleOf(string $type, Model $model): string
    {
        return match ($type) {
            'course' => 'دوره: ' . $model->title,
            'plan' => 'اشتراک: ' . $model->title,
            'product' => 'محصول: ' . $model->title,
            default => (string) $model->title,
        };
    }

    /** بازسازی سبد از روی پایگاه داده (قیمت و موجودی به‌روز — امنیت سمت سرور) */
    private function freshCartItems(): array
    {
        $cart = session('cart', []);
        $items = [];
        $dropped = false;

        foreach ($cart as $key => $entry) {
            $model = $this->resolvePurchasable($entry['purchasable_type'], (int) $entry['purchasable_id']);

            if (! $model) {
                $dropped = true;
                continue;
            }

            if ($entry['purchasable_type'] === 'course'
                && ! $model->is_free
                && $model->isEnrolled(Auth::user())) {
                $dropped = true; // قبلاً ثبت‌نام کرده؛ از سبد حذف شود
                continue;
            }

            $items[$key] = [
                'key' => $key,
                'purchasable_type' => $entry['purchasable_type'],
                'purchasable_id' => $model->id,
                'title' => $this->titleOf($entry['purchasable_type'], $model),
                'price' => (float) $model->price,
                'quantity' => max(1, (int) ($entry['quantity'] ?? 1)),
            ];
        }

        return [$items, $dropped];
    }

    private function saveCart(array $items): void
    {
        session(['cart' => $items]);
    }

    private function sessionCoupon(): ?Coupon
    {
        $coupon = Coupon::find(session('cart_coupon'));

        return ($coupon && $coupon->isValid()) ? $coupon : null;
    }

    /** [جمع کل، تخفیف، کوپن، قابل پرداخت] */
    private function totals(array $items): array
    {
        $total = collect($items)->sum(fn ($i) => $i['price'] * $i['quantity']);
        $coupon = $this->sessionCoupon();
        $discount = $coupon ? $coupon->discountFor($total) : 0.0;

        if ($coupon && $discount <= 0) {
            $coupon = null;
        }

        return [$total, $discount, $coupon, max(0, $total - $discount)];
    }

    private function startTransaction(int $userId, ?int $orderId, float $amount, string $type, array $meta = []): Transaction
    {
        return Transaction::create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'status' => 'pending',
            'gateway' => (string) setting('payments.gateway', 'mock'),
            'meta' => $meta ?: null,
        ]);
    }

    private function authorizeTransaction(Transaction $transaction): void
    {
        if ($transaction->user_id !== Auth::id() && ! Auth::user()->canAccess('shop.manage')) {
            abort(403, 'این تراکنش متعلق به شما نیست.');
        }
    }
}
