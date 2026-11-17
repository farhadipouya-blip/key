@extends('layouts.app')

@section('title', 'نتیجه پرداخت')
@section('page_title', 'نتیجه پرداخت')

@php
    $status = $transaction->status;
    $ref = $receipt?->gateway_ref ?? $transaction->gateway_ref ?? $transaction->meta['gateway_ref'] ?? null;
    $reason = $transaction->meta['reason'] ?? null;
    $cashbackPercent = (float) setting('commerce.cashback_percent', 0);
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        @if ($status === 'success')
            <x-card>
                <div class="text-center py-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:3.4rem"></i>
                    <h4 class="fw-bold mt-3 mb-1">پرداخت با موفقیت انجام شد</h4>
                    <p class="text-muted mb-4">
                        {{ $order ? 'سفارش «' . $order->number . '» پرداخت شد و دسترسی‌های خریداری‌شده فعال گردید.' : 'کیف پول شما با موفقیت شارژ شد.' }}
                    </p>

                    <div class="row justify-content-center g-3 mb-2">
                        <div class="col-6 col-md-4">
                            <x-stat icon="bi-cash-coin" label="مبلغ پرداخت‌شده" value="{{ money($receipt?->amount ?? $transaction->amount) }}" color="success"/>
                        </div>
                        <div class="col-6 col-md-4">
                            <x-stat icon="bi-upc-scan" label="کد پیگیری درگاه" value="{{ $ref ?: '—' }}" color="info"/>
                        </div>
                        <div class="col-6 col-md-4">
                            <x-stat icon="bi-calendar-check" label="تاریخ پرداخت" value="{{ fa_datetime($receipt?->paid_at ?? $transaction->paid_at) }}" color="primary"/>
                        </div>
                    </div>

                    @if ($ref)
                        <div class="small text-muted mb-3">
                            کد پیگیری <span class="fw-bold" style="direction:ltr">{{ $ref }}</span> را برای پیگیری‌های احتمالی نگه دارید.
                        </div>
                    @endif

                    @if ($cashbackPercent > 0 && $order)
                        <div class="alert alert-success d-flex gap-2 align-items-center text-start">
                            <i class="bi bi-piggy-bank"></i>
                            <div>
                                کش‌بک {{ fa_num($cashbackPercent) }}٪ مبلغ قابل پرداخت به‌عنوان اعتبار به کیف پول شما واریز شد
                                (مبلغ کش‌بک: {{ money(round(($receipt?->amount ?? $transaction->amount) * $cashbackPercent / 100)) }}).
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>

            @if ($order)
                <x-card title="دسترسی‌های فعال‌شده" icon="bi-unlock">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>آیتم خریداری‌شده</th>
                                    <th>نوع</th>
                                    <th>مبلغ</th>
                                    <th>وضعیت دسترسی</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td class="fw-bold">{{ $item->title }}</td>
                                        <td><x-badge :status="$item->purchasable_type" :labels="['course'=>'دوره','plan'=>'اشتراک','product'=>'محصول']"/></td>
                                        <td>{{ money($item->price) }}</td>
                                        <td>
                                            @if ($item->purchasable_type === 'course')
                                                <span class="text-success"><i class="bi bi-check2-circle"></i> ثبت‌نام در دوره انجام شد</span>
                                            @elseif ($item->purchasable_type === 'plan')
                                                <span class="text-success"><i class="bi bi-check2-circle"></i> اشتراک فعال شد</span>
                                            @else
                                                <span class="text-muted">محصول ثبت شد</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

            <div class="d-flex justify-content-center gap-2 flex-wrap">
                @if ($order)
                    <a href="{{ route('shop.invoice', $order) }}" class="btn btn-primary"><i class="bi bi-receipt"></i> مشاهده فاکتور</a>
                @endif
                <a href="{{ route('shop.orders') }}" class="btn btn-outline-primary"><i class="bi bi-receipt-cutoff"></i> سفارش‌های من</a>
                <a href="{{ route('shop.wallet') }}" class="btn btn-outline-secondary"><i class="bi bi-wallet2"></i> کیف پول</a>
                <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary"><i class="bi bi-bag"></i> بازگشت به فروشگاه</a>
            </div>
        @elseif ($status === 'failed')
            <x-card>
                <div class="text-center py-3">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size:3.4rem"></i>
                    <h4 class="fw-bold mt-3 mb-1">پرداخت ناموفق بود</h4>
                    <p class="text-muted mb-3">{{ $reason ?: 'پرداخت انجام نشد یا از درگاه انصراف دادید.' }}</p>

                    <div class="row justify-content-center g-3 mb-3">
                        <div class="col-6 col-md-4">
                            <x-stat icon="bi-cash-coin" label="مبلغ تراکنش" value="{{ money($transaction->amount) }}" color="danger"/>
                        </div>
                        <div class="col-6 col-md-4">
                            <x-stat icon="bi-calendar-x" label="تاریخ" value="{{ fa_datetime($transaction->updated_at) }}" color="secondary"/>
                        </div>
                    </div>

                    @if ($order)
                        <p class="small text-muted mb-3">سفارش «{{ $order->number }}» در وضعیت ناموفق قرار گرفت؛ می‌توانید پرداخت را دوباره انجام دهید.</p>
                        <form method="POST" action="{{ route('shop.retry', $order) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> تلاش مجدد برای پرداخت</button>
                        </form>
                    @endif
                    <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary ms-2"><i class="bi bi-cart3"></i> بازگشت به سبد خرید</a>
                </div>
            </x-card>
        @else
            <x-card>
                <div class="text-center py-3">
                    <i class="bi bi-hourglass-split text-warning" style="font-size:3.4rem"></i>
                    <h4 class="fw-bold mt-3 mb-1">این تراکنش در انتظار پرداخت است</h4>
                    <p class="text-muted mb-3">پرداخت تکمیل نشده است. برای ادامه به درگاه پرداخت بروید.</p>
                    <a href="{{ route('shop.gateway', $transaction) }}" class="btn btn-primary"><i class="bi bi-credit-card"></i> رفتن به درگاه پرداخت</a>
                </div>
            </x-card>
        @endif
    </div>
</div>
@endsection
