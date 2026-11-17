@extends('layouts.app')

@section('title', 'مدیریت کاربران')
@section('page_title', 'مدیریت کاربران')

@php
    $fieldGroups = [
        'math' => 'ریاضی و فیزیک',
        'science' => 'علوم تجربی',
        'humanities' => 'علوم انسانی',
        'language' => 'زبان‌های خارجی',
        'arts' => 'هنر',
    ];
    $statusLabels = ['active' => 'فعال', 'inactive' => 'غیرفعال', 'online' => 'آنلاین'];
    $counselorOptions = \App\Models\User::query()
        ->whereHas('roles', fn ($q) => $q->where('roles.slug', 'counselor'))
        ->where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name', 'email'])
        ->mapWithKeys(fn ($c) => [$c->id => $c->name . ' (' . $c->email . ')'])
        ->all();
@endphp

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="کاربران سامانه" icon="bi-people" bodyClass="py-0 p-0">
            <div class="d-flex justify-content-end px-3 py-2 border-bottom">
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> کاربر جدید</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>کاربر</th>
                            <th>نقش‌ها</th>
                            <th>پایه / رشته</th>
                            <th>وضعیت</th>
                            <th>ثبت‌نام</th>
                            <th class="text-nowrap">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($users as $u)
                        @php $isOnline = $u->last_seen_at && $u->last_seen_at->gte(now()->subMinutes(5)); @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar" style="width:34px;height:34px;font-size:.85rem">{{ mb_substr($u->name, 0, 1) }}</span>
                                    <div>
                                        <div class="fw-bold small">{{ $u->name }}</div>
                                        <div class="text-muted" style="font-size:.75rem">{{ $u->email }}</div>
                                        @if ($u->phone)
                                            <div class="text-muted" style="font-size:.75rem">{{ fa_num($u->phone) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="small">
                                @forelse ($u->roles as $r)
                                    <span class="badge rounded-pill badge-soft-primary">{{ $r->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="small">
                                {{ $u->grade ? fa_num($u->grade) : '—' }}
                                @if ($u->field_group)
                                    <div class="text-muted" style="font-size:.75rem">{{ $fieldGroups[$u->field_group] ?? $u->field_group }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($isOnline)
                                    <span class="badge rounded-pill badge-soft-success"><i class="bi bi-broadcast"></i> آنلاین</span>
                                @elseif ($u->is_active)
                                    <span class="badge rounded-pill badge-soft-info">فعال</span>
                                @else
                                    <span class="badge rounded-pill badge-soft-danger">غیرفعال</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ fa_date($u->created_at) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary" title="ویرایش">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline"
                                          onsubmit="return confirm('کاربر «{{ $u->name }}» غیرفعال شود؟ (حذف نمی‌شود)')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="غیرفعال‌سازی"><i class="bi bi-person-slash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4"><x-empty icon="bi-person-x" title="کاربری یافت نشد" message="عبارت جست‌وجو یا فیلترها را تغییر دهید." actionLabel="کاربر جدید" actionUrl="{{ route('admin.users.create') }}"/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
        {{ $users->links() }}
    </div>

    <div class="col-lg-4">
        <x-card title="فیلتر و جست‌وجو" icon="bi-funnel">
            <form method="GET" action="{{ route('admin.users.index') }}">
                <label class="form-label small">جست‌وجو</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control mb-3" placeholder="نام، ایمیل، موبایل یا کد ملی...">
                <label class="form-label small">نقش</label>
                <select name="role" class="form-select mb-3">
                    <option value="">همه نقش‌ها</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->slug }}" @if ($roleSlug === $r->slug) selected @endif>{{ $r->name }}</option>
                    @endforeach
                </select>
                <label class="form-label small">وضعیت</label>
                <select name="status" class="form-select mb-3">
                    <option value="">همه</option>
                    @foreach ($statusLabels as $key => $text)
                        <option value="{{ $key }}" @if ($status === $key) selected @endif>{{ $text }}</option>
                    @endforeach
                </select>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> اعمال فیلتر</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">حذف فیلترها</a>
                </div>
            </form>
        </x-card>

        <x-card title="آمار سریع" icon="bi-activity">
            <div class="d-flex justify-content-between align-items-center py-1">
                <span class="small"><i class="bi bi-broadcast text-success"></i> کاربران آنلاین (۵ دقیقه اخیر)</span>
                <strong>{{ fa_num($onlineCount) }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center py-1">
                <span class="small"><i class="bi bi-database"></i> نتیجه فعلی</span>
                <strong>{{ fa_num($users->total()) }} کاربر</strong>
            </div>
        </x-card>

        <x-card title="تخصیص دانشجو به مشاور" icon="bi-person-heart">
            <form method="POST" action="{{ route('admin.users.assign-counselor') }}">
                @csrf
                <label class="form-label small">دانشجو <span class="text-danger">*</span></label>
                <select name="student_id" class="form-select mb-3" required>
                    <option value="">انتخاب دانشجو...</option>
                    @foreach ($users as $u)
                        @if (! $u->isStaff())
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endif
                    @endforeach
                </select>
                <label class="form-label small">مشاور <span class="text-danger">*</span></label>
                <select name="counselor_id" class="form-select mb-3" required>
                    <option value="">انتخاب مشاور...</option>
                    @foreach ($counselorOptions as $id => $text)
                        <option value="{{ $id }}">{{ $text }}</option>
                    @endforeach
                </select>
                <label class="form-label small">یادداشت</label>
                <textarea name="note" rows="2" class="form-control mb-3" placeholder="اختیاری"></textarea>
                <button class="btn btn-success w-100"><i class="bi bi-link-45deg"></i> تخصیص مشاور</button>
            </form>
            <div class="form-text">اگر دانشجوی موردنظر در فهرست نیست، از فیلتر «نقش: دانشجو» استفاده کنید یا کاربر جدید بسازید.</div>
        </x-card>
    </div>
</div>
@endsection
