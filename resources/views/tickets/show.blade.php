@extends('layouts.app')

@section('title', 'تیکت: ' . $ticket->subject)
@section('page_title', 'جزئیات تیکت')

@php
    $statusLabels = ['open' => 'باز', 'answered' => 'پاسخ داده شد', 'pending' => 'در انتظار پاسخ', 'closed' => 'بسته‌شده'];
    $statusColors = ['open' => 'primary', 'answered' => 'success', 'pending' => 'warning', 'closed' => 'secondary'];
    $priorityLabels = ['low' => 'کم', 'normal' => 'معمولی', 'high' => 'بالا', 'urgent' => 'فوری'];
    $priorityColors = ['low' => 'secondary', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
    $steps = [
        'open' => ['label' => 'ثبت درخواست', 'icon' => 'bi-file-earmark-plus'],
        'answered' => ['label' => 'پاسخ پشتیبانی', 'icon' => 'bi-headset'],
        'pending' => ['label' => 'در انتظار پاسخ شما', 'icon' => 'bi-hourglass-split'],
        'closed' => ['label' => 'بسته‌شدن', 'icon' => 'bi-check2-circle'],
    ];
    $currentIdx = array_search($ticket->status, array_keys($steps), true);
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right"></i></a>
        <h5 class="fw-bold mb-0 text-break">{{ $ticket->subject }}</h5>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <x-badge :status="$ticket->priority" :labels="$priorityLabels" :colors="$priorityColors"/>
        <x-badge :status="$ticket->status" :labels="$statusLabels" :colors="$statusColors"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8 order-lg-1 order-2">
        {{-- رشته پیام‌ها --}}
        <x-card title="گفتگو" icon="bi-chat-left-text" bodyClass="p-0">
            <div class="p-3">
                @foreach ($ticket->messages as $msg)
                    <div class="d-flex mb-3 {{ $msg->is_staff ? 'justify-content-start' : 'justify-content-end' }}">
                        <div class="tkt-msg {{ $msg->is_staff ? 'tkt-staff' : 'tkt-user' }}">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-1 flex-wrap">
                                <strong class="small">
                                    <span class="avatar avatar-sm">{{ mb_substr($msg->user?->name ?? '؟', 0, 1) }}</span>
                                    {{ $msg->user?->name ?? 'کاربر حذف‌شده' }}
                                    @if ($msg->is_staff)
                                        <span class="badge rounded-pill badge-soft-primary">پشتیبانی</span>
                                    @elseif ($msg->user_id === $ticket->user_id)
                                        <span class="badge rounded-pill badge-soft-secondary">ثبت‌کننده</span>
                                    @endif
                                </strong>
                                <span class="small text-muted">{{ fa_datetime($msg->created_at) }}</span>
                            </div>
                            <div class="msg-body">{{ nl2br(e($msg->body)) }}</div>
                            @if ($msg->attachment_path)
                                <a href="{{ storage_public_url($msg->attachment_path) }}" target="_blank" rel="noopener"
                                   class="d-inline-flex align-items-center gap-1 small mt-2 p-1 px-2 rounded border text-decoration-none">
                                    <i class="bi bi-paperclip"></i>
                                    {{ basename($msg->attachment_path) }}
                                    <i class="bi bi-download"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- فرم پاسخ --}}
            <div class="border-top p-3">
                @if ($ticket->status !== 'closed')
                    <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data">
                        @csrf
                        <textarea name="body" rows="4" class="form-control mb-2" required
                                  placeholder="{{ $isSupport && $ticket->user_id !== auth()->id() ? 'پاسخ خود را به کاربر بنویسید...' : 'پاسخ خود را بنویسید...' }}">{{ old('body') }}</textarea>
                        @error('body')
                            <div class="small text-danger mb-2">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="flex-grow-1" style="max-width: 320px">
                                <input type="file" name="attachment" class="form-control form-control-sm">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-send"></i> ارسال پاسخ
                            </button>
                        </div>
                        @error('attachment')
                            <div class="small text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </form>
                @else
                    <div class="alert alert-secondary mb-0 d-flex gap-2 align-items-center">
                        <i class="bi bi-lock"></i>
                        این تیکت بسته شده است و امکان ارسال پاسخ وجود ندارد.
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <div class="col-lg-4 order-lg-2 order-1">
        {{-- وضعیت تیکت --}}
        <x-card title="وضعیت تیکت" icon="bi-diagram-3">
            <ul class="tkt-timeline list-unstyled mb-0">
                @foreach ($steps as $key => $step)
                    @php
                        $idx = array_search($key, array_keys($steps), true);
                        $done = $idx < $currentIdx || $ticket->status === 'closed';
                        $active = $ticket->status === $key;
                    @endphp
                    <li class="{{ $done || $active ? 'reached' : '' }} {{ $active ? 'current' : '' }}">
                        <span class="tkt-dot"><i class="bi {{ $step['icon'] }}"></i></span>
                        <span class="small {{ $active ? 'fw-bold' : '' }}">{{ $step['label'] }}</span>
                        @if ($active)
                            <span class="badge rounded-pill badge-soft-primary ms-1">اکنون</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-card>

        {{-- مشخصات --}}
        <x-card title="مشخصات تیکت" icon="bi-info-circle">
            <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                <li><i class="bi bi-person text-primary"></i> ثبت‌کننده: <strong>{{ $ticket->user?->name }}</strong>
                    @if ($isSupport && $ticket->user?->email)
                        <span class="text-muted d-block me-4">{{ $ticket->user->email }}</span>
                    @endif
                </li>
                @if ($ticket->category)
                    <li><i class="bi bi-tag text-primary"></i> دسته: {{ $ticket->category }}</li>
                @endif
                <li><i class="bi bi-flag text-primary"></i> اولویت: {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</li>
                <li><i class="bi bi-person-badge text-primary"></i> کارشناس: {{ $ticket->assignee?->name ?? 'بدون ارجاع' }}</li>
                <li><i class="bi bi-calendar-plus text-primary"></i> تاریخ ثبت: {{ fa_datetime($ticket->created_at) }}</li>
                <li><i class="bi bi-clock-history text-primary"></i> آخرین پاسخ: {{ fa_datetime($ticket->last_reply_at) }}</li>
                @if ($ticket->closed_at)
                    <li><i class="bi bi-calendar-x text-danger"></i> تاریخ بستن: {{ fa_datetime($ticket->closed_at) }}</li>
                @endif
            </ul>

            @if ($ticket->status !== 'closed')
                <div class="d-flex flex-column gap-2 mt-3">
                    @if ($isSupport && $ticket->user_id !== auth()->id())
                        <form method="POST" action="{{ route_if('support.tickets.assign', $ticket) }}">
                            @csrf
                            <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                            @if ($ticket->assigned_to && $ticket->assigned_to !== auth()->id())
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-person-plus"></i> ارجاع تیکت به من
                                </button>
                            @else
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-hand-thumbs-up"></i> رسیدگی به این تیکت
                                </button>
                            @endif
                        </form>
                    @endif
                    <form method="POST" action="{{ route('tickets.close', $ticket) }}"
                          onsubmit="return confirm('آیا از بستن این تیکت مطمئن هستید؟')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-x-circle"></i> بستن تیکت
                        </button>
                    </form>
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tkt-msg { max-width: 85%; padding: .75rem 1rem; border-radius: 14px; font-size: .9rem; }
    .tkt-staff { background: rgba(47, 91, 234, .07); border: 1px solid rgba(47, 91, 234, .18); }
    .tkt-user { background: rgba(22, 163, 74, .06); border: 1px solid rgba(22, 163, 74, .18); }
    .msg-body { white-space: normal; line-height: 1.9; }
    .avatar-sm { display: inline-flex; width: 1.6rem; height: 1.6rem; font-size: .75rem; align-items: center; justify-content: center; vertical-align: middle; }
    .tkt-timeline li { position: relative; padding-inline-start: 2.2rem; padding-bottom: 1.25rem; color: var(--key-muted, #8a93a6); }
    .tkt-timeline li:last-child { padding-bottom: 0; }
    .tkt-timeline li::before { content: ''; position: absolute; inset-inline-start: .69rem; top: 1.5rem; bottom: 0; width: 2px; background: var(--key-border, #e5e9f2); }
    .tkt-timeline li:last-child::before { display: none; }
    .tkt-timeline li.reached { color: var(--bs-body-color); }
    .tkt-timeline li.reached::before { background: var(--bs-primary); }
    .tkt-dot { position: absolute; inset-inline-start: 0; top: 0; width: 1.6rem; height: 1.6rem; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; background: var(--key-border, #e5e9f2); color: var(--key-muted, #8a93a6); }
    .tkt-timeline li.reached .tkt-dot { background: var(--bs-primary); color: #fff; }
    .tkt-timeline li.current .tkt-dot { box-shadow: 0 0 0 4px rgba(47, 91, 234, .18); }
</style>
@endpush
