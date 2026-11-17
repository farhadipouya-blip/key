@extends('layouts.app')

@section('title', 'میز پشتیبانی تیکت‌ها')
@section('page_title', 'میز پشتیبانی تیکت‌ها')

@php
    $statusLabels = ['open' => 'باز', 'answered' => 'پاسخ داده شد', 'pending' => 'در انتظار پاسخ', 'closed' => 'بسته‌شده'];
    $statusColors = ['open' => 'primary', 'answered' => 'success', 'pending' => 'warning', 'closed' => 'secondary'];
    $priorityLabels = ['low' => 'کم', 'normal' => 'معمولی', 'high' => 'بالا', 'urgent' => 'فوری'];
    $priorityColors = ['low' => 'secondary', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
@endphp

@section('content')
<div class="row g-3 mb-3">
    @foreach ($counts as $key => $count)
        <div class="col-6 col-md-3">
            <x-stat
                icon="{{ ['open' => 'bi-envelope-exclamation', 'pending' => 'bi-hourglass-split', 'answered' => 'bi-check2-circle', 'closed' => 'bi-archive'][$key] }}"
                label="{{ $statusLabels[$key] }}"
                value="{{ fa_num($count) }}"
                color="{{ $statusColors[$key] }}"
                url="{{ route('support.tickets.index', ['status' => $key]) }}"/>
        </div>
    @endforeach
</div>

{{-- فیلترها --}}
<x-card bodyClass="py-3">
    <form method="GET" action="{{ route('support.tickets.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">وضعیت</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">همه وضعیت‌ها</option>
                @foreach ($statusLabels as $key => $label)
                    <option value="{{ $key }}" @if($status === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">اولویت</label>
            <select name="priority" class="form-select form-select-sm">
                <option value="">همه اولویت‌ها</option>
                @foreach ($priorityLabels as $key => $label)
                    <option value="{{ $key }}" @if($priority === $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">وضعیت ارجاع</label>
            <select name="assigned" class="form-select form-select-sm">
                <option value="">همه تیکت‌ها</option>
                <option value="mine" @if($assigned === 'mine') selected @endif>ارجاع‌شده به من</option>
                <option value="unassigned" @if($assigned === 'unassigned') selected @endif>بدون کارشناس</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-funnel"></i> اعمال فیلتر</button>
        </div>
    </form>
</x-card>

<x-card title="فهرست همه تیکت‌ها" icon="bi-headset" bodyClass="p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr class="small text-muted">
                    <th>موضوع</th>
                    <th>ثبت‌کننده</th>
                    <th>وضعیت</th>
                    <th>اولویت</th>
                    <th>کارشناس</th>
                    <th>پیام‌ها</th>
                    <th>آخرین پاسخ</th>
                    <th class="text-nowrap">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td>
                            <a href="{{ route('tickets.show', $ticket) }}" class="fw-bold text-decoration-none text-body d-block">
                                {{ $ticket->subject }}
                            </a>
                            @if ($ticket->category)
                                <span class="badge badge-soft-info mt-1">{{ $ticket->category }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="d-block">{{ $ticket->user?->name }}</span>
                            <span class="small text-muted">{{ $ticket->user?->email }}</span>
                        </td>
                        <td><x-badge :status="$ticket->status" :labels="$statusLabels" :colors="$statusColors"/></td>
                        <td><x-badge :status="$ticket->priority" :labels="$priorityLabels" :colors="$priorityColors"/></td>
                        <td>
                            @if ($ticket->assignee)
                                {{ $ticket->assignee->name }}
                                @if ($ticket->assigned_to === auth()->id())
                                    <span class="badge badge-soft-primary">شما</span>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>{{ fa_num($ticket->messages_count) }}</td>
                        <td class="small text-muted">{{ fa_datetime($ticket->last_reply_at) }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('tickets.show', $ticket) }}" class="btn btn-sm btn-outline-secondary" title="مشاهده و پاسخ">
                                <i class="bi bi-box-arrow-in-down-left"></i> مشاهده
                            </a>
                            <form method="POST" action="{{ route('support.tickets.assign', $ticket) }}" class="d-inline-flex gap-1 align-items-center mt-1">
                                @csrf
                                <select name="assigned_to" class="form-select form-select-sm" style="min-width: 130px" required>
                                    <option value="" disabled>ارجاع به...</option>
                                    @foreach ($experts as $expert)
                                        <option value="{{ $expert->id }}" @if($ticket->assigned_to === $expert->id) selected @endif>
                                            {{ $expert->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="ثبت ارجاع">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-empty icon="bi-inbox" title="تیکتی یافت نشد"
                                     message="با فیلترهای فعلی تیکتی وجود ندارد."/>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<div class="mt-3">{{ $tickets->links() }}</div>
@endsection
