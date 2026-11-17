@extends('layouts.app')

@section('title', 'تیکت‌های پشتیبانی')
@section('page_title', 'تیکت‌های پشتیبانی')

@php
    $statusLabels = ['open' => 'باز', 'answered' => 'پاسخ داده شد', 'pending' => 'در انتظار پاسخ', 'closed' => 'بسته‌شده'];
    $statusColors = ['open' => 'primary', 'answered' => 'success', 'pending' => 'warning', 'closed' => 'secondary'];
    $priorityLabels = ['low' => 'کم', 'normal' => 'معمولی', 'high' => 'بالا', 'urgent' => 'فوری'];
    $priorityColors = ['low' => 'secondary', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1">تیکت‌های من</h5>
        <span class="text-muted small">پیگیری درخواست‌ها و گفتگو با تیم پشتیبانی</span>
    </div>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> ثبت تیکت جدید
    </a>
</div>

{{-- فیلتر وضعیت --}}
<x-card bodyClass="py-3">
    <form method="GET" action="{{ route('tickets.index') }}" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small">وضعیت تیکت</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">همه وضعیت‌ها</option>
                @foreach ($statusLabels as $key => $label)
                    <option value="{{ $key }}" @if($status === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-funnel"></i> اعمال فیلتر</button>
        </div>
        @if ($status !== '')
            <div class="col-md-4 text-md-start">
                <a href="{{ route('tickets.index') }}" class="btn btn-link btn-sm text-decoration-none text-muted">
                    <i class="bi bi-x-circle"></i> حذف فیلتر
                </a>
            </div>
        @endif
    </form>
</x-card>

<x-card title="فهرست تیکت‌ها" icon="bi-life-preserver" bodyClass="p-0">
    @forelse ($tickets as $ticket)
        <a href="{{ route('tickets.show', $ticket) }}" class="d-block text-decoration-none text-body border-bottom p-3 ticket-row">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="fw-bold mb-1">
                        <i class="bi bi-chat-right-text text-primary"></i>
                        {{ $ticket->subject }}
                    </div>
                    <div class="small text-muted d-flex flex-wrap gap-3">
                        @if ($ticket->category)
                            <span><i class="bi bi-tag"></i> {{ $ticket->category }}</span>
                        @endif
                        <span><i class="bi bi-chat-dots"></i> {{ fa_num($ticket->messages->count()) }} پیام</span>
                        @if ($ticket->assignee)
                            <span><i class="bi bi-person-check"></i> کارشناس: {{ $ticket->assignee->name }}</span>
                        @endif
                        <span><i class="bi bi-clock-history"></i> آخرین پاسخ: {{ fa_datetime($ticket->last_reply_at) }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <x-badge :status="$ticket->priority" :labels="$priorityLabels" :colors="$priorityColors"/>
                    <x-badge :status="$ticket->status" :labels="$statusLabels" :colors="$statusColors"/>
                    <i class="bi bi-chevron-left text-muted small"></i>
                </div>
            </div>
        </a>
    @empty
        <x-empty icon="bi-life-preserver" title="هنوز تیکتی ثبت نکرده‌اید"
                 message="برای طرح سؤال یا مشکل خود، تیکت جدیدی ثبت کنید تا کارشناسان پاسخ دهند."
                 actionLabel="ثبت تیکت جدید" actionUrl="{{ route('tickets.create') }}"/>
    @endforelse
</x-card>

<div class="mt-3">{{ $tickets->links() }}</div>
@endsection

@push('styles')
<style>
    .ticket-row:hover { background: rgba(47, 91, 234, .04); }
</style>
@endpush
