@extends('layouts.app')

@section('title', 'گزارش‌ها و لاگ')
@section('page_title', 'لاگ فعالیت سامانه')

@php
    $actionTitles = [
        'admin.' => 'مدیریت',
        'media.' => 'رسانه',
        'fonts.' => 'فونت',
        'imports.' => 'ورود گروهی',
        'auth.' => 'احراز هویت',
    ];
@endphp

@section('content')
<div class="row g-3">
    <div class="col-lg-9">
        <x-card title="رخدادهای ثبت‌شده" icon="bi-list-check" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>زمان</th>
                            <th>کاربر</th>
                            <th>رخداد</th>
                            <th>موضوع</th>
                            <th>IP</th>
                            <th>جزئیات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small text-muted text-nowrap">{{ fa_datetime($log->created_at) }}</td>
                            <td class="small">
                                @if ($log->user)
                                    <span class="fw-bold">{{ $log->user->name }}</span>
                                    <div class="text-muted" style="font-size:.7rem">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-muted">سیستم</span>
                                @endif
                            </td>
                            <td class="small"><code dir="ltr">{{ $log->action }}</code></td>
                            <td class="small text-muted">
                                @if ($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ fa_num($log->subject_id) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-muted" dir="ltr">{{ $log->ip ?: '—' }}</td>
                            <td>
                                @if ($log->payload)
                                    <details>
                                        <summary class="small text-primary" style="cursor:pointer">مشاهده</summary>
                                        <pre class="small bg-light border rounded-3 p-2 mt-2 mb-0" dir="ltr" style="max-width:280px; white-space:pre-wrap">{{ json_encode($log->payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4"><x-empty icon="bi-clipboard-x" title="رخدادی ثبت نشده است"/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
        {{ $logs->links() }}
    </div>

    <div class="col-lg-3">
        <x-card title="فیلتر رخدادها" icon="bi-funnel">
            <form method="GET" action="{{ route('admin.audit') }}">
                <label class="form-label small">جست‌وجو (کاربر یا رخداد)</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control mb-3" placeholder="نام کاربر یا action...">
                <label class="form-label small">کاربر</label>
                <select name="user_id" class="form-select mb-3">
                    <option value="">همه کاربران</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @if ($userId === $u->id) selected @endif>{{ $u->name }}</option>
                    @endforeach
                </select>
                <label class="form-label small">نوع رخداد</label>
                <select name="action" class="form-select mb-3">
                    <option value="">همه رخدادها</option>
                    @foreach ($actions as $a)
                        <option value="{{ $a }}" @if ($action === $a) selected @endif>{{ $a }}</option>
                    @endforeach
                </select>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> اعمال</button>
                    <a href="{{ route('admin.audit') }}" class="btn btn-outline-secondary">پاک‌سازی</a>
                </div>
            </form>
        </x-card>

        <x-card title="آمار" icon="bi-graph-up">
            <div class="d-flex justify-content-between py-1 small">
                <span class="text-muted">نتیجه فعلی</span><strong>{{ fa_num($logs->total()) }} رخداد</strong>
            </div>
            <div class="d-flex justify-content-between py-1 small">
                <span class="text-muted">نوع رخدادهای ثبت‌شده</span><strong>{{ fa_num($actions->count()) }}</strong>
            </div>
        </x-card>
    </div>
</div>
@endsection
