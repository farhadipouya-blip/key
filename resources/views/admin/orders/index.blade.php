@extends('layouts.app')

@section('title', 'سفارش‌ها و پرداخت‌ها')
@section('page_title', 'سفارش‌ها و پرداخت‌ها')

@section('content')
{{-- آمار کلی --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-lg-2">
        <x-stat icon="bi-receipt-cutoff" label="کل سفارش‌ها" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <x-stat icon="bi-check2-circle" label="پرداخت‌شده" value="{{ fa_num($stats['paid']) }}" color="success" url="{{ route('admin.orders.index', ['status' => 'paid']) }}"/>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <x-stat icon="bi-hourglass-split" label="در انتظار پرداخت" value="{{ fa_num($stats['pending']) }}" color="warning" url="{{ route('admin.orders.index', ['status' => 'pending']) }}"/>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <x-stat icon="bi-x-circle" label="ناموفق" value="{{ fa_num($stats['failed']) }}" color="danger" url="{{ route('admin.orders.index', ['status' => 'failed']) }}"/>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <x-stat icon="bi-slash-circle" label="ابطال‌شده" value="{{ fa_num($stats['canceled']) }}" color="secondary" url="{{ route('admin.orders.index', ['status' => 'canceled']) }}"/>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <x-stat icon="bi-cash-coin" label="درآمد پرداخت‌شده" value="{{ money($stats['revenue']) }}" color="info"/>
    </div>
</div>

{{-- فیلترها --}}
<x-card bodyClass="py-3">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small">جست‌وجو</label>
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="شماره سفارش، شرح، نام یا ایمیل خریدار...">
        </div>
        <div class="col-md-3">
            <label class="form-label small">وضعیت</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">همه وضعیت‌ها</option>
                @foreach (['pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت‌شده', 'failed' => 'ناموفق', 'canceled' => 'ابطال‌شده'] as $key => $label)
                    <option value="{{ $key }}" @if($status === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i> فیلتر</button>
        </div>
        <div class="col-md-2">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm w-100">حذف فیلترها</a>
        </div>
    </form>
</x-card>

<x-card title="فهرست سفارش‌ها" icon="bi-list-ul">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>شماره سفارش</th>
                    <th>خریدار</th>
                    <th>اقلام</th>
                    <th>جمع کل</th>
                    <th>تخفیف</th>
                    <th>قابل پرداخت</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="fw-bold" style="direction:ltr">{{ $order->number }}</td>
                        <td>
                            <div class="small fw-bold">{{ $order->user?->name }}</div>
                            <div class="small text-muted">{{ $order->user?->email }}</div>
                        </td>
                        <td>{{ fa_num($order->items_count) }} آیتم</td>
                        <td>{{ money($order->total) }}</td>
                        <td class="text-success">{{ $order->discount > 0 ? money($order->discount) : '—' }}</td>
                        <td class="fw-bold">{{ money($order->payable) }}</td>
                        <td>
                            <x-badge :status="$order->status"
                                     :labels="['pending'=>'در انتظار پرداخت','paid'=>'پرداخت‌شده','failed'=>'ناموفق','canceled'=>'ابطال‌شده']"/>
                        </td>
                        <td class="small text-muted">{{ fa_datetime($order->created_at) }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-primary btn-sm" title="مشاهده جزئیات">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">
                        <x-empty icon="bi-receipt-cutoff" title="سفارشی یافت نشد"
                                 message="هیچ سفارشی با این فیلترها ثبت نشده است."/>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($orders->hasPages())
    <div>{{ $orders->links() }}</div>
@endif
@endsection
