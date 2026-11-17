<?php

use App\Http\Controllers\Module\ShopAdminController;
use App\Http\Controllers\Module\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ماژول فروشگاه، سفارش، پرداخت و اشتراک
|--------------------------------------------------------------------------
| فروشگاه: shop.buy — مدیریت فروش: shop.manage
| درگاه پرداخت آزمایشی با callback داخلی و فعال‌سازی خودکار دسترسی.
*/

// ---------------- فروشگاه دانشجو ----------------
Route::middleware('permission:shop.buy')->prefix('shop')->name('shop.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::post('/cart/add', [ShopController::class, 'add'])->name('add');
    Route::post('/cart/remove', [ShopController::class, 'remove'])->name('remove');
    Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
    Route::post('/cart/coupon', [ShopController::class, 'coupon'])->name('coupon');
    Route::post('/checkout', [ShopController::class, 'checkout'])->name('checkout');

    Route::get('/plans/{plan}/subscribe', [ShopController::class, 'subscribe'])->name('subscribe');
    Route::post('/buy-now', [ShopController::class, 'buyNow'])->name('buy-now');

    // درگاه پرداخت (آزمایشی) و بازگشت
    Route::get('/pay/{transaction}', [ShopController::class, 'gateway'])->name('gateway');
    Route::get('/pay/{transaction}/callback', [ShopController::class, 'gatewayCallback'])->name('gateway.callback');
    Route::get('/pay/{transaction}/result', [ShopController::class, 'payResult'])->name('pay.result');

    // سفارش‌ها و فاکتور
    Route::get('/orders', [ShopController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}/invoice', [ShopController::class, 'invoice'])->name('invoice');
    Route::post('/orders/{order}/retry', [ShopController::class, 'retry'])->name('retry');

    // کیف پول
    Route::get('/wallet', [ShopController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/charge', [ShopController::class, 'walletCharge'])->name('wallet.charge');
});

// ---------------- مدیریت فروش ----------------
Route::middleware('permission:shop.manage')->prefix('admin/shop')->name('admin.')->group(function () {
    // اشتراک‌ها (پلن‌ها)
    Route::get('/plans', [ShopAdminController::class, 'plansIndex'])->name('plans.index');
    Route::get('/plans/create', [ShopAdminController::class, 'plansCreate'])->name('plans.create');
    Route::post('/plans', [ShopAdminController::class, 'plansStore'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [ShopAdminController::class, 'plansEdit'])->name('plans.edit');
    Route::put('/plans/{plan}', [ShopAdminController::class, 'plansUpdate'])->name('plans.update');
    Route::delete('/plans/{plan}', [ShopAdminController::class, 'plansDestroy'])->name('plans.destroy');

    // سفارش‌ها
    Route::get('/orders', [ShopAdminController::class, 'ordersIndex'])->name('orders.index');
    Route::get('/orders/{order}', [ShopAdminController::class, 'ordersShow'])->name('orders.show');
    Route::post('/orders/{order}/status', [ShopAdminController::class, 'ordersStatus'])->name('orders.status');

    // کوپن‌ها
    Route::get('/coupons', [ShopAdminController::class, 'couponsIndex'])->name('coupons.index');
    Route::get('/coupons/create', [ShopAdminController::class, 'couponsCreate'])->name('coupons.create');
    Route::post('/coupons', [ShopAdminController::class, 'couponsStore'])->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [ShopAdminController::class, 'couponsEdit'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [ShopAdminController::class, 'couponsUpdate'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [ShopAdminController::class, 'couponsDestroy'])->name('coupons.destroy');

    // محصولات
    Route::get('/products', [ShopAdminController::class, 'productsIndex'])->name('products.index');
    Route::get('/products/create', [ShopAdminController::class, 'productsCreate'])->name('products.create');
    Route::post('/products', [ShopAdminController::class, 'productsStore'])->name('products.store');
    Route::get('/products/{product}/edit', [ShopAdminController::class, 'productsEdit'])->name('products.edit');
    Route::put('/products/{product}', [ShopAdminController::class, 'productsUpdate'])->name('products.update');
    Route::delete('/products/{product}', [ShopAdminController::class, 'productsDestroy'])->name('products.destroy');
});
