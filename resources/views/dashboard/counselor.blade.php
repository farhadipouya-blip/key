@extends('layouts.app')

@section('title', 'داشبورد مشاور')
@section('page_title', 'پنل مشاور')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-people" label="دانشجویان تحت پوشش" value="{{ fa_num($students->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-calendar-heart" label="جلسات پیش‌رو" value="{{ fa_num($upcoming->count()) }}" color="warning" url="{{ route_if('counselor.sessions') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-arrow-left-right" label="پیگیری‌های باز" value="{{ fa_num($openFollowups) }}" color="info" url="{{ route_if('counselor.followups') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-headset" label="تیکت‌های ارجاع‌شده" value="{{ fa_num($assignedTickets) }}" color="secondary" url="{{ route_if('support.tickets.index') }}"/>
    </div>
</div>

<x-card title="دانشجویان من" icon="bi-people" bodyClass="py-0"
        actions="<a href='{{ route_if('counselor.students') }}' class='btn btn-sm btn-primary'>مشاهده همه</a>">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>دانشجو</th><th>پایه</th><th>آخرین حضور</th><th>آزمون‌ها</th><th>عملکرد</th></tr>
            </thead>
            <tbody>
            @forelse ($students as $student)
                @php
                    $lastAttempt = $student->attempts()->where('status', 'finished')->latest('finished_at')->first();
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar" style="width:32px;height:32px;font-size:.85rem">{{ mb_substr($student->name, 0, 1) }}</span>
                            <div>
                                <div class="fw-bold small">{{ $student->name }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $student->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $student->grade ?: '—' }}</td>
                    <td class="small text-muted">{{ $student->last_seen_at ? fa_datetime($student->last_seen_at) : '—' }}</td>
                    <td class="small">{{ fa_num($student->attempts_count) }}</td>
                    <td>
                        @if ($lastAttempt)
                            <span class="badge badge-soft-{{ $lastAttempt->percent >= 60 ? 'success' : ($lastAttempt->percent >= 35 ? 'warning' : 'danger') }}">
                                {{ fa_num($lastAttempt->percent) }}٪ — {{ $lastAttempt->exam->title }}
                            </span>
                        @else
                            <span class="text-muted small">آزمونی نداده</span>
                        @endif
                    </td>
                    <td><a href="{{ route_if('student.360', $student) }}" class="btn btn-sm btn-outline-primary">پرونده ۳۶۰</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-4">
                    <x-empty icon="bi-people" title="دانشجویی به شما تخصیص داده نشده است"
                             message="مدیر سامانه می‌تواند دانشجویان را به شما تخصیص دهد."/>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

<x-card title="جلسات مشاوره پیش‌رو" icon="bi-calendar-heart"
        actions="<a href='{{ route_if('counselor.sessions') }}' class='btn btn-sm btn-outline-primary'>مدیریت جلسات</a>">
    @forelse ($upcoming as $session)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div>
                <span class="fw-bold small">{{ $session->student?->name }}</span>
                <span class="text-muted small"> — {{ fa_datetime($session->scheduled_at) }}</span>
            </div>
            <x-badge status="warning" labels="['warning' => 'در انتظار برگزاری']"/>
        </div>
    @empty
        <x-empty icon="bi-calendar-check" title="جلسه‌ای در پیش‌رو ندارید"/>
    @endforelse
</x-card>
@endsection
