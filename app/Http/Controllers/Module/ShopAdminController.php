<?php

namespace App\Http\Controllers\Module;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Services\CommerceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * مدیریت فروش: بسته‌های اشتراک، سفارش‌ها و پرداخت‌ها، کوپن‌ها، محصولات
 */
class ShopAdminController extends Controller
{
    public function __construct(private CommerceService $commerce) {}

    // ------------------------------------------------------------------
    // بسته‌های اشتراک
    // ------------------------------------------------------------------

    public function plansIndex()
    {
        $plans = SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->orderBy('sort')->orderBy('id')
            ->paginate(20);

        return view('admin.plans.index', compact('plans'));
    }

    public function plansCreate()
    {
        return view('admin.plans.form', ['plan' => new SubscriptionPlan(['is_active' => true, 'sort' => 0, 'duration_days' => 30])]);
    }

    public function plansStore(Request $request): RedirectResponse
    {
        $data = $this->validatePlan($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title'], 'subscription_plans', $request->input('slug'));
        $plan = SubscriptionPlan::create($data);

        AuditLog::record(Auth::user(), 'plan.created', $plan, ['title' => $plan->title, 'price' => $plan->price]);

        return redirect()->route('admin.plans.index')->with('success', 'بسته اشتراک «' . $plan->title . '» ایجاد شد.');
    }

    public function plansEdit(SubscriptionPlan $plan)
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function plansUpdate(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $this->validatePlan($request);

        if ($request->filled('slug') && $request->slug !== $plan->slug) {
            $data['slug'] = $this->uniqueSlug($request->slug, $data['title'], 'subscription_plans', $request->slug, $plan->id);
        }

        $plan->update($data);
        AuditLog::record(Auth::user(), 'plan.updated', $plan, ['title' => $plan->title]);

        return redirect()->route('admin.plans.index')->with('success', 'بسته اشتراک به‌روزرسانی شد.');
    }

    public function plansDestroy(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'این بسته دارای اشتراک ثبت‌شده است و قابل حذف نیست؛ می‌توانید آن را غیرفعال کنید.');
        }

        AuditLog::record(Auth::user(), 'plan.deleted', $plan, ['title' => $plan->title]);
        $plan->delete();

        return back()->with('success', 'بسته اشتراک حذف شد.');
    }

    private function validatePlan(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9\-_]*$/i'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'features_text' => ['nullable', 'string', 'max:4000'],
            'badge' => ['nullable', 'string', 'max:40'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'عنوان بسته الزامی است',
            'price.required' => 'قیمت بسته الزامی است',
            'price.integer' => 'قیمت باید عدد صحیح (تومان) باشد',
            'duration_days.required' => 'مدت اشتراک (روز) الزامی است',
            'duration_days.min' => 'مدت اشتراک باید حداقل ۱ روز باشد',
            'slug.regex' => 'نامک فقط می‌تواند شامل حروف لاتین، عدد و خط تیره باشد',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['features'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')))
            ->map(fn ($f) => trim($f))->filter()->values()->all();
        unset($data['features_text']);

        return $data;
    }

    // ------------------------------------------------------------------
    // سفارش‌ها و پرداخت‌ها
    // ------------------------------------------------------------------

    public function ordersIndex(Request $request)
    {
        $query = Order::query()->with('user')->withCount('items')->latest('id');

        $status = $request->input('status');
        if (in_array($status, ['pending', 'paid', 'failed', 'canceled'], true)) {
            $query->where('status', $status);
        }

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('number', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
            });
        }

        $orders = $query->paginate(20)->withQueryString();
        $stats = [
            'all' => Order::count(),
            'paid' => Order::where('status', 'paid')->count(),
            'pending' => Order::where('status', 'pending')->count(),
            'failed' => Order::where('status', 'failed')->count(),
            'canceled' => Order::where('status', 'canceled')->count(),
            'revenue' => (float) Order::where('status', 'paid')->sum('payable'),
        ];

        return view('admin.orders.index', compact('orders', 'stats', 'status', 'q'));
    }

    public function ordersShow(Order $order)
    {
        $order->load(['items', 'user', 'coupon', 'transactions' => fn ($t) => $t->latest('id')]);

        return view('admin.orders.show', compact('order'));
    }

    /** تغییر وضعیت دستی: تأیید پرداخت دستی (markPaid با درگاه manual) یا ابطال */
    public function ordersStatus(Request $request, Order $order): RedirectResponse
    {
        $action = $request->validate([
            'action' => ['required', Rule::in(['mark_paid', 'cancel'])],
        ], ['action.required' => 'عملیات نامشخص است'])['action'];

        if ($action === 'mark_paid') {
            if ($order->status === 'paid') {
                return back()->with('info', 'این سفارش قبلاً پرداخت شده است.');
            }

            $this->commerce->markPaid($order, 'manual', 'MANUAL-' . strtoupper(Str::random(8)));

            AuditLog::record(Auth::user(), 'order.manual_paid', $order, [
                'order_number' => $order->number,
                'payable' => $order->payable,
            ]);

            return back()->with('success', 'پرداخت سفارش ' . $order->number . ' به‌صورت دستی تأیید و دسترسی‌ها فعال شد.');
        }

        if ($order->status === 'paid') {
            return back()->with('error', 'سفارش پرداخت‌شده قابل ابطال نیست؛ از بخش کیف پول برای بازگشت وجه استفاده کنید.');
        }

        $order->forceFill(['status' => 'canceled'])->save();

        // تراکنش‌های در انتظار این سفارش نیز ناموفق شوند تا از درگاه قابل پرداخت نباشند
        $order->transactions()
            ->where('type', 'payment')->where('status', 'pending')->get()
            ->each(function ($t) {
                $t->forceFill([
                    'status' => 'failed',
                    'meta' => array_merge($t->meta ?? [], ['reason' => 'سفارش توسط مدیریت ابطال شد']),
                ])->save();
            });

        AuditLog::record(Auth::user(), 'order.canceled', $order, ['order_number' => $order->number]);

        return back()->with('success', 'سفارش ابطال شد.');
    }

    // ------------------------------------------------------------------
    // کوپن‌ها
    // ------------------------------------------------------------------

    public function couponsIndex()
    {
        $coupons = Coupon::query()->latest('id')->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function couponsCreate()
    {
        return view('admin.coupons.form', ['coupon' => new Coupon(['type' => 'percent', 'is_active' => true])]);
    }

    public function couponsStore(Request $request): RedirectResponse
    {
        $data = $this->validateCoupon($request);
        Coupon::create($data);
        AuditLog::record(Auth::user(), 'coupon.created', Coupon::where('code', $data['code'])->first(), ['code' => $data['code'], 'type' => $data['type'], 'value' => $data['value']]);

        return redirect()->route('admin.coupons.index')->with('success', 'کوپن «' . $data['code'] . '» ایجاد شد.');
    }

    public function couponsEdit(Coupon $coupon)
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    public function couponsUpdate(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validateCoupon($request, $coupon->id);
        $coupon->update($data);
        AuditLog::record(Auth::user(), 'coupon.updated', $coupon, ['code' => $coupon->code]);

        return redirect()->route('admin.coupons.index')->with('success', 'کوپن به‌روزرسانی شد.');
    }

    public function couponsDestroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->used_count > 0) {
            return back()->with('error', 'این کوپن قبلاً استفاده شده و قابل حذف نیست؛ آن را غیرفعال کنید.');
        }

        AuditLog::record(Auth::user(), 'coupon.deleted', $coupon, ['code' => $coupon->code]);
        $coupon->delete();

        return back()->with('success', 'کوپن حذف شد.');
    }

    private function validateCoupon(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')->ignore($ignoreId)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'min_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'کد کوپن الزامی است',
            'code.unique' => 'این کد کوپن قبلاً ثبت شده است',
            'value.required' => 'مقدار تخفیف الزامی است',
            'value.max' => 'برای تخفیف درصدی حداکثر ۱۰۰ مجاز است',
            'expires_at.after' => 'تاریخ انقضا باید در آینده باشد',
        ]);

        $data['code'] = mb_strtoupper(trim($data['code']));

        if ($data['type'] === 'percent' && $data['value'] > 100) {
            abort(422, 'درصد تخفیف نمی‌تواند بیش از ۱۰۰ باشد.');
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['expires_at'] = $data['expires_at'] ?? null;

        return $data;
    }

    // ------------------------------------------------------------------
    // محصولات
    // ------------------------------------------------------------------

    public function productsIndex()
    {
        $products = Product::query()->latest('id')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function productsCreate()
    {
        return view('admin.products.form', ['product' => new Product(['is_published' => true])]);
    }

    public function productsStore(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);
        $data['slug'] = $this->uniqueSlug(null, $data['title'], 'products', $request->input('slug'));
        Product::create($data);
        AuditLog::record(Auth::user(), 'product.created', Product::where('slug', $data['slug'])->first(), ['title' => $data['title']]);

        return redirect()->route('admin.products.index')->with('success', 'محصول «' . $data['title'] . '» ایجاد شد.');
    }

    public function productsEdit(Product $product)
    {
        return view('admin.products.form', compact('product'));
    }

    public function productsUpdate(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product->id);

        if ($request->filled('slug') && $request->slug !== $product->slug) {
            $data['slug'] = $this->uniqueSlug($request->slug, $data['title'], 'products', $request->slug, $product->id);
        }

        $product->update($data);
        AuditLog::record(Auth::user(), 'product.updated', $product, ['title' => $product->title]);

        return redirect()->route('admin.products.index')->with('success', 'محصول به‌روزرسانی شد.');
    }

    public function productsDestroy(Product $product): RedirectResponse
    {
        $hasOrders = OrderItem::query()
            ->where('purchasable_type', 'product')
            ->where('purchasable_id', $product->id)
            ->exists();
        if ($hasOrders) {
            return back()->with('error', 'این محصول در سفارش‌ها استفاده شده و قابل حذف نیست؛ آن را غیرمنتشر کنید.');
        }

        AuditLog::record(Auth::user(), 'product.deleted', $product, ['title' => $product->title]);
        $product->delete();

        return back()->with('success', 'محصول حذف شد.');
    }

    private function validateProduct(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'regex:/^[a-z0-9\-_]*$/i'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover_path' => ['nullable', 'string', 'max:300'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'عنوان محصول الزامی است',
            'price.required' => 'قیمت محصول الزامی است',
            'slug.regex' => 'نامک فقط می‌تواند شامل حروف لاتین، عدد و خط تیره باشد',
        ]);

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    // ------------------------------------------------------------------
    // کمکی
    // ------------------------------------------------------------------

    private function uniqueSlug(?string $requested, string $title, string $table, ?string $input = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($input ?: $title) ?: Str::slug('key-' . Str::random(6));
        $slug = $base;
        $i = 1;

        while (
            DB::table($table)->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
