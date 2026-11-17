@extends('layouts.app')

@section('title', 'دانشجویان من')
@section('page_title', 'دانشجویان من — پنل مشاور')

@section('content')
@if (auth()->user()->canAccess('users.manage'))
    <x-card title="تخصیص دانشجو به مشاور" icon="bi-person-plus" class="mb-3">
        <form method="POST" action="{{ route('counselor.students.assign') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <x-select name="counselor_id" label="مشاور" :options="$counselors->pluck('name', 'id')" :value="old('counselor_id')" required/>
            </div>
            <div class="col-md-4">
                <x-select name="student_id" label="دانشجو" :options="$allStudents->pluck('name', 'id')" :value="old('student_id')" required/>
            </div>
            <div class="col-md-2">
                <x-input name="note" label="یادداشت (اختیاری)" :value="old('note')"/>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> تخصیص</button>
            </div>
        </form>
    </x-card>
@endif

<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-people" label="دانشجویان فعال" value="{{ fa_num($students->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-arrow-repeat" label="مرور در انتظار (مجموع)" value="{{ fa_num(collect($summaries)->sum(fn ($s) => $s['pendingReviews'])) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-clock-history" label="مطالعه ۷ روز (مجموع)" value="{{ duration_human(collect($summaries)->sum(fn ($s) => $s['study7'])) }}" color="info"/>
    </div>
</div>

<x-card title="فهرست دانشجویان" icon="bi-people" bodyClass="py-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>دانشجو</th>
                <th>پایه / گروه</th>
                <th>آخرین آزمون</th>
                <th>مطالعه ۷ روز</th>
                <th>مرور در انتظار</th>
                <th>اشتراک</th>
                <th>آزمون‌ها</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($students as $student)
                @php
                    $s = $summaries[$student->id] ?? null;
                    $sub = $s['activeSubscription'] ?? null;
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
                    <td class="small">
                        {{ $student->grade ?: '—' }}
                        <span class="badge badge-soft-secondary ms-1">{{ ['experimental' => 'تجربی', 'math' => 'ریاضی', 'humanities' => 'انسانی', 'arts' => 'هنر', 'language' => 'زبان'][$student->field_group] ?? ($student->field_group ?: '—') }}</span>
                    </td>
                    <td>
                        @if ($s && $s['lastAttempt'])
                            <span class="badge badge-soft-{{ $s['lastAttempt']->percent >= 60 ? 'success' : ($s['lastAttempt']->percent >= 35 ? 'warning' : 'danger') }}">
                                {{ fa_num($s['lastAttempt']->percent) }}٪
                            </span>
                            <div class="text-muted" style="font-size:.75rem">{{ $s['lastAttempt']->exam->title }}</div>
                        @else
                            <span class="text-muted small">آزمونی نداده</span>
                        @endif
                    </td>
                    <td class="small">{{ duration_human($s['study7'] ?? 0) }}</td>
                    <td>
                        <span class="badge badge-soft-{{ ($s['pendingReviews'] ?? 0) > 0 ? 'warning' : 'secondary' }}">
                            {{ fa_num($s['pendingReviews'] ?? 0) }} مورد
                        </span>
                    </td>
                    <td>
                        @if ($sub)
                            <span class="badge badge-soft-success">فعال تا {{ fa_date($sub->ends_at) }}</span>
                        @else
                            <span class="badge badge-soft-secondary">بدون اشتراک</span>
                        @endif
                    </td>
                    <td class="small">{{ fa_num($student->attempts_finished_count) }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('counselor.student', $student) }}" class="btn btn-sm btn-outline-primary">پرونده ۳۶۰</a>
                        <a href="{{ route('analytics.user', $student) }}" class="btn btn-sm btn-outline-info">تحلیل</a>
                        <form method="POST" action="{{ route('counselor.students.archive', $student) }}" class="d-inline"
                              onsubmit="return confirm('این دانشجو آرشیو شود؟')">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger" title="آرشیو"><i class="bi bi-box-arrow-down"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-4">
                    <x-empty icon="bi-people" title="دانشجویی به شما تخصیص داده نشده است"
                             message="مدیر سامانه می‌تواند دانشجویان را به شما تخصیص دهد."/>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
