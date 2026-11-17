@extends('layouts.app')

@section('title', 'جزئیات سفارش ' . $order->number)
@section('page_title', 'جزئیات سفارش ' . $order->number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-primary"></i> سفارش <span style="direction:ltr">{{ $order->number }}</span></h5>
    <div class="d-flex gap-2">
        <a href="{{ route('shop.invoice', $order) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> فاکتور</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right"></i> بازگشت به فهرست</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="اقلام سفارش" icon="bi-list-ul">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>آیتم</th>
                            <th>نوع</th>
                            <th>قیمت واحد</th>
                            <th>تعداد</th>
                            <th>جمع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->items as $item)
                            <tr>
                                <td class="fw-bold">{{ $item->title }}</td>
                                <td><x-badge :status="$item->purchasable_type" :labels="['course'=>'دوره','plan'=>'اشتراک','product'=>'محصول']"/></td>
                                <td>{{ money($item->price) }}</td>
                                <td>{{ fa_num($item->quantity) }}</td>
                                <td class="fw-bold">{{ money($item->price * $item->quantity) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">آیتمی ثبت نشده است.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <ul class="list-group list-group-flush mt-2">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>جمع کل</span><span class="fw-bold">{{ money($order->total) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>تخفیف {{ $order->coupon ? '(کوپن ' . $order->coupon->code . ')' : '' }}</span>
                    <span class="fw-bold text-success">{{ $order->discount > 0 ? '− ' . money($order->discount) : money(0) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="fw-bold">مبلغ نهایی</span><span class="fw-bold text-primary">{{ money($order->payable) }}</span>
                </li>
            </ul>
        </x-card>

        <x-card title="تراکنش‌های سفارش" icon="bi-credit-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>درگاه</th>
                            <th>مبلغ</th>
                            <th>وضعیت</th>
                            <th>کد پیگیری</th>
                            <th>تاریخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->transactions as $t)
                            <tr>
                                <td>{{ strtoupper($t->gateway ?: '—') }}</td>
                                <td>{{ money($t->amount) }}</td>
                                <td><x-badge :status="$t->status" :labels="['pending'=>'در انتظار','success'=>'موفق','failed'=>'ناموفق']"/></td>
                                <td style="direction:ltr">{{ $t->gateway_ref ?: '—' }}</td>
                                <td class="small text-muted">{{ $t->paid_at ? fa_datetime($t->paid_at) : fa_datetime($t->created_at) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">تراکنشی ثبت نشده است.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="اطلاعات سفارش" icon="bi-info-circle">
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted small">وضعیت</span>
                    <x-badge :status="$order->status" :labels="['pending'=>'در انتظار پرداخت','paid'=>'پرداخت‌شده','failed'=>'ناموفق','canceled'=>'ابطال‌شده']"/>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted small">خریدار</span>
                    <span class="small fw-bold">{{ $order->user?->name }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted small">ایمیل</span>
                    <span class="small">{{ $order->user?->email }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted small">تاریخ ثبت</span>
                    <span class="small">{{ fa_datetime($order->created_at) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted small">درگاه</span>
                    <span class="small">{{ strtoupper($order->gateway ?: '—') }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted small">تاریخ پرداخت</span>
                    <span class="small">{{ $order->paid_at ? fa_datetime($order->paid_at) : '—' }}</span>
                </li>
            </ul>
            @if ($order->description)
                <div class="mt-3">
                    <div class="small text-muted mb-1">شرح سفارش</div>
                    <p class="small mb-0">{{ $order->description }}</p>
                </div>
            @endif
        </x-card>

        <x-card title="عملیات وضعیت" icon="bi-gear">
            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                @csrf
                <div class="d-grid gap-2">
                    @unless ($order->status === 'paid')
                        <button type="submit" name="action" value="mark_paid" class="btn btn-success"
                                onclick="return confirm('تأیید پرداخت به‌صورت دستی و فعال‌سازی دسترسی‌ها انجام شود؟')">
                            <i class="bi bi-check2-circle"></i> تأیید پرداخت دستی
                        </button>
                    @endunless
                    @unless (in_array($order->status, ['paid', 'canceled'], true))
                        <button type="submit" name="action" value="cancel" class="btn btn-outline-danger"
                                onclick="return confirm('این سفارش ابطال شود؟')">
                            <i class="bi bi-slash-circle"></i> ابطال سفارش
                        </button>
                    @endunless
                </div>
            </form>
            @if ($order->status === 'paid')
                <div class="form-text">این سفارش پرداخت شده است؛ برای بازگشت وجه از بخش کیف پول استفاده کنید.</div>
            @elseif ($order->status === 'canceled')
                <div class="form-text">این سفارش ابطال شده است.</div>
            @endif
        </x-card>
    </div>
</div>
@endsection
