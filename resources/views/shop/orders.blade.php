@extends('layouts.app')

@section('title', 'سفارش‌های من')
@section('page_title', 'سفارش‌های من')

@section('content')
<x-card title="سفارش‌های من" icon="bi-receipt-cutoff"
        actions="<a href='{{ route('shop.index') }}' class='btn btn-primary btn-sm'><i class='bi bi-bag'></i> فروشگاه</a>">
    @forelse ($orders as $order)
        @php
            $statusLabels = [
                'pending' => 'در انتظار پرداخت',
                'paid' => 'پرداخت‌شده',
                'failed' => 'ناموفق',
                'canceled' => 'ابطال‌شده',
            ];
        @endphp
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <span class="fw-bold" style="direction:ltr">{{ $order->number }}</span>
                        <span class="small text-muted ms-2">{{ fa_datetime($order->created_at) }}</span>
                    </div>
                    <x-badge :status="$order->status" :labels="['pending'=>'در انتظار پرداخت','paid'=>'پرداخت‌شده','failed'=>'ناموفق','canceled'=>'ابطال‌شده']"/>
                </div>
                <p class="small text-muted mb-2">
                    <i class="bi bi-list-ul"></i> {{ fa_num($order->items_count) }} آیتم — {{ \Illuminate\Support\Str::limit($order->description, 90) }}
                </p>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small">
                        <span class="text-muted">جمع کل: {{ money($order->total) }}</span>
                        @if ((float) $order->discount > 0)
                            <span class="text-success ms-3">تخفیف: {{ money($order->discount) }}</span>
                        @endif
                        <span class="fw-bold text-primary ms-3">قابل پرداخت: {{ money($order->payable) }}</span>
                        @if ($order->paid_at)
                            <span class="text-muted ms-3">تاریخ پرداخت: {{ fa_datetime($order->paid_at) }}</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('shop.invoice', $order) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-receipt"></i> فاکتور
                        </a>
                        @if (in_array($order->status, ['pending', 'failed'], true))
                            <form method="POST" action="{{ route('shop.retry', $order) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="bi bi-arrow-repeat"></i> پرداخت مجدد
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <x-empty icon="bi-receipt-cutoff" title="سفارشی ثبت نشده است"
                 message="هنوز خریدی انجام نداده‌اید. از فروشگاه شروع کنید."
                 actionLabel="رفتن به فروشگاه" actionUrl="{{ route('shop.index') }}"/>
    @endforelse
</x-card>

@if ($orders->hasPages())
    <div class="mt-2">{{ $orders->links() }}</div>
@endif
@endsection
