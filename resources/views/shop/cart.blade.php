@extends('layouts.app')

@section('title', 'سبد خرید')
@section('page_title', 'سبد خرید')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-cart3 text-primary"></i> سبد خرید شما</h5>
    <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bag"></i> بازگشت به فروشگاه</a>
</div>

@if (empty($items))
    <x-card>
        <x-empty icon="bi-cart-x" title="سبد خرید شما خالی است"
                 message="برای خرید از فروشگاه آیتم‌ها را به سبد اضافه کنید."
                 actionLabel="رفتن به فروشگاه" actionUrl="{{ route('shop.index') }}"/>
    </x-card>
@else
    <div class="row g-3">
        <div class="col-lg-8">
            <x-card title="آیتم‌های سبد" icon="bi-list-ul">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>آیتم</th>
                                <th>نوع</th>
                                <th>قیمت واحد</th>
                                <th>تعداد</th>
                                <th>جمع</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="fw-bold">{{ $item['title'] }}</td>
                                    <td>
                                        @php $typeLabels = ['course' => 'دوره', 'plan' => 'اشتراک', 'product' => 'محصول']; @endphp
                                        <x-badge :status="$item['purchasable_type']" :labels="['course'=>'دوره','plan'=>'اشتراک','product'=>'محصول']"/>
                                    </td>
                                    <td>{{ money($item['price']) }}</td>
                                    <td>{{ fa_num($item['quantity']) }}</td>
                                    <td class="fw-bold">{{ money($item['price'] * $item['quantity']) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('shop.remove') }}">
                                            @csrf
                                            <input type="hidden" name="key" value="{{ $item['key'] }}">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="حذف از سبد">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="کد تخفیف" icon="bi-ticket-perforated">
                @if ($coupon)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge rounded-pill badge-soft-success fs-6">{{ $coupon->code }}</span>
                        <form method="POST" action="{{ route('shop.coupon') }}">
                            @csrf
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-x-lg"></i> حذف کد
                            </button>
                        </form>
                    </div>
                    <div class="small text-muted">
                        نوع تخفیف: {{ $coupon->type === 'percent' ? 'درصدی (' . fa_num($coupon->value) . '٪)' : 'مبلغ ثابت' }}
                        @if ($coupon->max_uses)
                            — استفاده‌شده: {{ fa_num($coupon->used_count) }} از {{ fa_num($coupon->max_uses) }}
                        @endif
                    </div>
                @else
                    <form method="POST" action="{{ route('shop.coupon') }}">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="code" class="form-control" placeholder="مثلاً KEY-10" value="{{ old('code') }}" maxlength="40">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> اعمال</button>
                        </div>
                        @error('code')
                            <div class="small text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </form>
                @endif
            </x-card>

            <x-card title="صورتحساب" icon="bi-receipt">
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>جمع کل</span>
                        <span class="fw-bold">{{ money($total) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>تخفیف</span>
                        <span class="fw-bold text-success">{{ $discount > 0 ? '− ' . money($discount) : money(0) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 fs-6">
                        <span class="fw-bold">مبلغ قابل پرداخت</span>
                        <span class="fw-bold text-primary">{{ money($payable) }}</span>
                    </li>
                </ul>
                <form method="POST" action="{{ route('shop.checkout') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-credit-card"></i> پرداخت و تکمیل خرید
                    </button>
                </form>
                <div class="form-text text-center mt-2">
                    پس از ثبت سفارش به درگاه پرداخت هدایت می‌شوید.
                </div>
            </x-card>
        </div>
    </div>
@endif
@endsection
