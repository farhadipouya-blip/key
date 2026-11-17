@extends('layouts.app')

@section('title', 'ویرایش کاربر')
@section('page_title', 'ویرایش کاربر — ' . $user->name)

@php
    $fieldGroups = [
        'math' => 'ریاضی و فیزیک',
        'science' => 'علوم تجربی',
        'humanities' => 'علوم انسانی',
        'language' => 'زبان‌های خارجی',
        'arts' => 'هنر',
    ];
    $userRoleIds = $user->roles->pluck('id')->all();
@endphp

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <x-card title="اطلاعات کاربر — {{ $user->name }}" icon="bi-person-gear">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <x-input name="name" label="نام و نام خانوادگی" required value="{{ old('name', $user->name) }}"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="email" type="email" label="ایمیل" required value="{{ old('email', $user->email) }}"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="phone" label="شماره موبایل" value="{{ old('phone', $user->phone) }}"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="password" type="password" label="رمز عبور جدید" help="برای حفظ رمز فعلی خالی بگذارید"/>
                    </div>
                    <div class="col-md-6">
                        <x-input name="grade" label="پایه تحصیلی" value="{{ old('grade', $user->grade) }}"/>
                    </div>
                    <div class="col-md-6">
                        <x-select name="field_group" label="رشته تحصیلی" :options="$fieldGroups" :value="old('field_group', $user->field_group)"/>
                    </div>
                </div>

                <label class="form-label">نقش‌ها <span class="text-danger">*</span> <span class="text-muted small">(حداقل یک نقش)</span></label>
                <div class="row g-2 mb-3">
                    @foreach ($roles as $role)
                        <div class="col-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="role_ids[]"
                                       id="role-{{ $role->id }}" value="{{ $role->id }}"
                                       @if (in_array($role->id, $userRoleIds)) checked @endif>
                                <label class="form-check-label small" for="role-{{ $role->id }}">{{ $role->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('role_ids')
                    <div class="small text-danger mb-2">{{ $message }}</div>
                @enderror

                <x-switch name="is_active" label="حساب فعال باشد" checked="{{ old('is_active', $user->is_active) }}"/>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> ذخیره تغییرات</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">بازگشت</a>
                </div>
            </form>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="مشاوران تخصیص‌یافته" icon="bi-person-heart" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>مشاور</th><th>وضعیت</th><th>یادداشت</th></tr></thead>
                    <tbody>
                    @forelse ($counselorLinks as $link)
                        <tr>
                            <td class="small fw-bold">
                                {{ $link->counselor?->name ?? '—' }}
                                <div class="text-muted" style="font-size:.75rem">{{ $link->counselor?->email }}</div>
                            </td>
                            <td>
                                <x-badge :status="$link->status" :labels="['active' => 'فعال', 'archived' => 'بایگانی']"/>
                            </td>
                            <td class="small text-muted">{{ Str::limit($link->note, 40) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3 small">مشاوری تخصیص نیافته است</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($counselors->isNotEmpty())
            <x-card title="تخصیص مشاور جدید" icon="bi-plus-circle">
                <form method="POST" action="{{ route('admin.users.assign-counselor') }}">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $user->id }}">
                    <label class="form-label small">انتخاب مشاور <span class="text-danger">*</span></label>
                    <select name="counselor_id" class="form-select mb-3" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach ($counselors as $counselor)
                            <option value="{{ $counselor->id }}">{{ $counselor->name }} ({{ $counselor->email }})</option>
                        @endforeach
                    </select>
                    <label class="form-label small">یادداشت</label>
                    <textarea name="note" rows="2" class="form-control mb-3" placeholder="اختیاری"></textarea>
                    <button class="btn btn-success w-100"><i class="bi bi-link-45deg"></i> تخصیص دانشجو به مشاور</button>
                </form>
            </x-card>
        @endif

        <x-card title="اطلاعات سیستمی" icon="bi-clock-history">
            <div class="d-flex justify-content-between py-1 small">
                <span class="text-muted">تاریخ ثبت‌نام</span><span>{{ fa_datetime($user->created_at) }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
                <span class="text-muted">آخرین ورود</span><span>{{ $user->last_login_at ? fa_datetime($user->last_login_at) : '—' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
                <span class="text-muted">آخرین فعالیت</span><span>{{ $user->last_seen_at ? fa_datetime($user->last_seen_at) : '—' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 small">
                <span class="text-muted">وضعیت ایمیل</span>
                <span>{{ $user->email_verified_at ? 'تأیید شده' : 'تأیید نشده' }}</span>
            </div>
        </x-card>
    </div>
</div>
@endsection
