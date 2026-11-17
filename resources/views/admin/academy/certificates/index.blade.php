@extends('layouts.app')

@section('title', 'مدیریت گواهی‌نامه‌ها')
@section('page_title', 'مدیریت گواهی‌نامه‌ها')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-patch-check" label="کل گواهی‌نامه‌ها" value="{{ fa_num($stats['all']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-circle" label="معتبر" value="{{ fa_num($stats['active']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-x-octagon" label="ابطال‌شده" value="{{ fa_num($stats['revoked']) }}" color="danger"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-people" label="کاربران فعال" value="{{ fa_num($stats['users']) }}" color="info"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <x-card title="صدور گواهی‌نامه" icon="bi-plus-circle">
            <form method="POST" action="{{ route('admin.certificates.issue') }}">
                @csrf
                <x-input name="title" label="عنوان گواهی‌نامه" required :value="old('title')"
                         placeholder="مثلاً: گواهینامه تکمیل دوره ..."/>
                <div class="mb-3">
                    <label class="form-label" for="fld-user_id">کاربر (صدور تکی)</label>
                    <select id="fld-user_id" name="user_id" class="form-select">
                        <option value="">— انتخاب کاربر —</option>
                        @foreach (\App\Models\User::orderBy('name')->get(['id','name','email']) as $u)
                            <option value="{{ $u->id }}" @if (old('user_id') == $u->id) selected @endif>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                    <div class="form-text">برای صدور گروهی، کاربر را خالی بگذارید و از فیلد پایین استفاده کنید.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fld-bulk_emails">صدور گروهی — ایمیل‌های کاربران</label>
                    <textarea id="fld-bulk_emails" name="bulk_emails" rows="4" class="form-control" dir="ltr"
                              placeholder="user1@example.com&#10;user2@example.com&#10;user3@example.com">{{ old('bulk_emails') }}</textarea>
                    <div class="form-text">هر ایمیل در یک خط (یا با ویرگول جدا شود)؛ برای همه یک گواهی‌نامه صادر می‌شود.</div>
                    @error('bulk_emails')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                </div>
                <x-select name="course_id" label="دوره مرتبط (اختیاری)" :options="$courses" :value="old('course_id')"/>
                <x-select name="type" label="زبان گواهی‌نامه" required :options="['fa' => 'فارسی', 'en' => 'انگلیسی']" :value="old('type', 'fa')"/>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-patch-check"></i> صدور گواهی‌نامه</button>
            </form>
        </x-card>
    </div>

    <div class="col-xl-8">
        <x-card title="فهرست گواهی‌نامه‌ها" icon="bi-list-ul">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="جستجو: عنوان، کد یکتا یا نام کاربر..." value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <select name="state" class="form-select form-select-sm">
                        <option value="">همه</option>
                        <option value="active" @if (request('state') === 'active') selected @endif>معتبر</option>
                        <option value="revoked" @if (request('state') === 'revoked') selected @endif>ابطال‌شده</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> فیلتر</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>عنوان</th><th>دارنده</th><th>دوره</th><th>زبان</th><th>کد یکتا</th><th>صادرکننده</th><th>تاریخ</th><th>وضعیت</th><th></th></tr>
                    </thead>
                    <tbody>
                    @forelse ($certificates as $certificate)
                        <tr>
                            <td class="small fw-bold">{{ $certificate->title }}</td>
                            <td class="small">{{ $certificate->user?->name }}</td>
                            <td class="small">{{ $certificate->course?->title ?? '—' }}</td>
                            <td class="small">{{ $certificate->type === 'fa' ? 'فارسی' : 'انگلیسی' }}</td>
                            <td class="small text-muted" dir="ltr">
                                <a href="{{ url('public/certificate/' . $certificate->uuid) }}" target="_blank" class="text-decoration-none">
                                    {{ \Illuminate\Support\Str::limit($certificate->uuid, 10) }}...
                                </a>
                            </td>
                            <td class="small">{{ $certificate->issuer?->name ?? 'سیستم' }}</td>
                            <td class="small text-muted">{{ fa_date($certificate->issued_at) }}</td>
                            <td>
                                @if ($certificate->isRevoked())
                                    <span class="badge badge-soft-danger">ابطال‌شده</span>
                                @else
                                    <span class="badge badge-soft-success">معتبر</span>
                                @endif
                            </td>
                            <td>
                                @if (! $certificate->isRevoked())
                                    <form method="POST" action="{{ route('admin.certificates.revoke', $certificate) }}"
                                          onsubmit="return confirm('ابطال گواهی‌نامه «{{ $certificate->title }}» مطمئن هستید؟')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" title="ابطال"><i class="bi bi-x-octagon"></i> ابطال</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty icon="bi-patch-check" title="گواهی‌نامه‌ای صادر نشده است" message="با فرم صدور، اولین گواهی‌نامه را ثبت کنید."/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{ $certificates->links() }}
    </div>
</div>
@endsection
