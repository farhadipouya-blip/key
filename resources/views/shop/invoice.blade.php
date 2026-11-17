@extends('layouts.app')

@section('title', 'فاکتور سفارش ' . $order->number)
@section('page_title', 'فاکتور سفارش')

@php
    $statusLabels = ['pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت‌شده', 'failed' => 'ناموفق', 'canceled' => 'ابطال‌شده'];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="fw-bold mb-0"><i class="bi bi-receipt text-primary"></i> فاکتور سفارش</h5>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> چاپ فاکتور</button>
        <a href="{{ route('shop.orders') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right"></i> سفارش‌های من</a>
    </div>
</div>

<x-card bodyClass="p-4">
    {{-- سربرگ فاکتور --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 pb-3 mb-3 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width:52px;height:52px">K</div>
            <div>
                <div class="fw-bold fs-5">کلید — سامانه جامع آموزشی</div>
                <div class="small text-muted">فاکتور فروش خدمات و محصولات آموزشی</div>
            </div>
        </div>
        <div class="text-start small">
            <div class="mb-1">شماره سفارش: <span class="fw-bold" style="direction:ltr">{{ $order->number }}</span></div>
            <div class="mb-1">تاریخ صدور: <span class="fw-bold">{{ fa_datetime($order->created_at) }}</span></div>
            <div>وضعیت: <x-badge :status="$order->status" :labels="['pending'=>'در انتظار پرداخت','paid'=>'پرداخت‌شده','failed'=>'ناموفق','canceled'=>'ابطال‌شده']"/></div>
        </div>
    </div>

    {{-- اطلاعات خریدار --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100">
                <div class="fw-bold small mb-2"><i class="bi bi-person"></i> مشخصات خریدار</div>
                <div class="small mb-1">نام: {{ $order->user->name }}</div>
                <div class="small mb-1">ایمیل: {{ $order->user->email }}</div>
                @if ($order->user->phone)
                    <div class="small">شماره تماس: {{ fa_num($order->user->phone) }}</div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100">
                <div class="fw-bold small mb-2"><i class="bi bi-info-circle"></i> اطلاعات پرداخت</div>
                <div class="small mb-1">درگاه: {{ $order->gateway ? fa_num($order->gateway) : '—' }}</div>
                <div class="small mb-1">تاریخ پرداخت: {{ $order->paid_at ? fa_datetime($order->paid_at) : '—' }}</div>
                <div class="small">کد تخفیف: {{ $order->coupon?->code ?? 'بدون کد تخفیف' }}</div>
            </div>
        </div>
    </div>

    {{-- اقلام سفارش --}}
    <h6 class="fw-bold mb-2">اقلام سفارش</h6>
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:60px">#</th>
                    <th>شرح آیتم</th>
                    <th>نوع</th>
                    <th>تعداد</th>
                    <th>قیمت واحد</th>
                    <th>جمع</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->items as $item)
                    <tr>
                        <td>{{ fa_num($loop->iteration) }}</td>
                        <td class="fw-bold">{{ $item->title }}</td>
                        <td><x-badge :status="$item->purchasable_type" :labels="['course'=>'دوره','plan'=>'اشتراک','product'=>'محصول']"/></td>
                        <td>{{ fa_num($item->quantity) }}</td>
                        <td>{{ money($item->price) }}</td>
                        <td>{{ money($item->price * $item->quantity) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">آیتمی برای این سفارش ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- جمع مبالغ --}}
    <div class="row justify-content-end mb-4">
        <div class="col-md-5">
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between">
                    <span>جمع کل</span><span class="fw-bold">{{ money($order->total) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>تخفیف</span><span class="fw-bold text-success">{{ $order->discount > 0 ? '− ' . money($order->discount) : money(0) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between bg-light-subtle">
                    <span class="fw-bold">مبلغ نهایی</span><span class="fw-bold text-primary fs-6">{{ money($order->payable) }}</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- تراکنش‌ها --}}
    <h6 class="fw-bold mb-2">تاریخچه پرداخت‌ها</h6>
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>درگاه</th>
                    <th>کد پیگیری</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->transactions as $t)
                    <tr>
                        <td>{{ strtoupper($t->gateway ?: '—') }}</td>
                        <td style="direction:ltr">{{ $t->gateway_ref ?: '—' }}</td>
                        <td>{{ money($t->amount) }}</td>
                        <td><x-badge :status="$t->status" :labels="['pending'=>'در انتظار','success'=>'موفق','failed'=>'ناموفق']"/></td>
                        <td>{{ $t->paid_at ? fa_datetime($t->paid_at) : fa_datetime($t->created_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">تراکنشی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="small text-muted border-top pt-3">
        این فاکتور به‌صورت سیستمی در سامانه کلید تولید شده است — <span style="direction:ltr">{{ $order->number }}</span>
    </div>
</x-card>
@endsection
