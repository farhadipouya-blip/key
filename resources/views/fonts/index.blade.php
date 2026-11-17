@extends('layouts.app')

@section('title', 'مدیریت فونت')
@section('page_title', 'مدیریت فونت سامانه')

@section('content')
@php
    $cssFormats = ['ttf' => 'truetype', 'otf' => 'opentype', 'woff' => 'woff', 'woff2' => 'woff2'];
@endphp

{{-- استایل پیش‌نمایش هر فونت با @font-face اختصاصی --}}
<style>
    @foreach ($fonts as $font)
        @font-face {
            font-family: 'font-preview-{{ $font->id }}';
            src: url('{{ storage_public_url($font->file_path) }}') format('{{ $cssFormats[$font->format] ?? 'woff2' }}');
            font-display: swap;
        }
        .font-preview-{{ $font->id }} { font-family: 'font-preview-{{ $font->id }}', 'Vazirmatn', sans-serif; }
    @endforeach
</style>

<div class="row g-3">
    <div class="col-lg-4">
        <x-card title="بارگذاری فونت جدید" icon="bi-fonts">
            <form method="POST" action="{{ route('fonts.store') }}" enctype="multipart/form-data">
                @csrf
                <x-input name="name" label="نام فونت (خانواده CSS)" required value="{{ old('name') }}" placeholder="مثلاً IRANSansX" help="این نام به‌عنوان font-family در کل سامانه استفاده می‌شود."/>
                <label class="form-label">فایل فونت <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control mb-1" required accept=".ttf,.otf,.woff,.woff2">
                <div class="form-text mb-3">قالب‌های مجاز: ttf، otf، woff، woff2 — حداکثر ۵ مگابایت</div>

                <label class="form-label">وزن‌های فونت</label>
                <div class="row g-1 mb-3">
                    @foreach ($weightsOptions as $weight => $label)
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="weights[]" value="{{ $weight }}"
                                       id="w{{ $weight }}"
                                       @checked(is_array(old('weights')) ? in_array($weight, old('weights')) : $weight === 400)>
                                <label class="form-check-label small" for="w{{ $weight }}">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <x-input name="license_name" label="نام مجوز" value="{{ old('license_name') }}" placeholder="مثلاً OFL / رایگان"/>
                <x-textarea name="license_note" label="ملاحظات مجوز" rows="2" value="{{ old('license_note') }}" placeholder="اختیاری"/>
                <button class="btn btn-primary w-100"><i class="bi bi-cloud-arrow-up"></i> بارگذاری فونت</button>
            </form>
        </x-card>

        <x-card title="راهنما" icon="bi-info-circle">
            <ul class="small text-muted mb-0 ps-3">
                <li>با فعال‌سازی، نام خانواده فونت در تنظیمات سامانه ثبت می‌شود و کل صفحات از آن استفاده می‌کنند.</li>
                <li>فونت فعال با نشان «فعال در کل سامانه» مشخص می‌شود.</li>
                <li>حذف فونت فعال امکان‌پذیر نیست؛ ابتدا آن را غیرفعال کنید.</li>
                <li>غیرفعال‌سازی، سامانه را به فونت وزیرمتن پیش‌فرض برمی‌گرداند.</li>
            </ul>
        </x-card>
    </div>

    <div class="col-lg-8">
        @if ($activeFamily)
            <div class="alert alert-info d-flex align-items-center gap-2">
                <i class="bi bi-check-circle"></i>
                <div>فونت فعال سامانه در حال حاضر: <strong>{{ $activeFamily }}</strong></div>
            </div>
        @else
            <div class="alert alert-secondary d-flex align-items-center gap-2">
                <i class="bi bi-info-circle"></i>
                <div>فونت ویژه‌ای فعال نشده است — سامانه از فونت پیش‌فرض وزیرمتن استفاده می‌کند.</div>
            </div>
        @endif

        <div class="row g-3">
            @forelse ($fonts as $font)
                @php
                    $isActive = $activeFamily === $font->family;
                    $fileSize = null;
                    try {
                        $fileSize = \Storage::disk('public')->exists($font->file_path) ? \Storage::disk('public')->size($font->file_path) : null;
                    } catch (\Throwable) {}
                @endphp
                <div class="col-12 col-md-6">
                    <div class="card mb-3 {{ $isActive ? 'border-primary' : '' }}">
                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-type text-primary"></i>
                                {{ $font->name }}
                                @if ($isActive)
                                    <span class="badge rounded-pill badge-soft-success"><i class="bi bi-check-lg"></i> فعال در کل سامانه</span>
                                @endif
                            </h6>
                            <code class="small text-muted" dir="ltr">{{ strtoupper($font->format) }}</code>
                        </div>
                        <div class="card-body">
                            <div class="border rounded-3 p-3 bg-body-tertiary mb-3">
                                <div class="font-preview-{{ $font->id }}" style="font-size:1.4rem">نمونه متن کلید ۱۲۳۴</div>
                                <div class="font-preview-{{ $font->id }}" style="font-size:1rem" dir="ltr">The quick brown fox 0123456789</div>
                            </div>

                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @forelse ((array) ($font->weights ?: [400]) as $weight)
                                    <span class="badge rounded-pill badge-soft-primary" dir="ltr">{{ fa_num($weight) }}</span>
                                @empty
                                    <span class="badge rounded-pill badge-soft-secondary">{{ fa_num(400) }}</span>
                                @endforelse
                            </div>

                            <div class="small text-muted">
                                @if ($fileSize !== null)
                                    <div><i class="bi bi-hdd"></i> حجم نصب‌شده: {{ fa_num(round($fileSize / 1024)) }} کیلوبایت</div>
                                @endif
                                <div><i class="bi bi-calendar3"></i> بارگذاری: {{ fa_datetime($font->created_at) }} — {{ $font->uploader?->name ?? '—' }}</div>
                                @if ($font->license_name)
                                    <div><i class="bi bi-card-checklist"></i> مجوز: {{ $font->license_name }}</div>
                                @endif
                                @if ($font->license_note)
                                    <div class="mt-1">{{ $font->license_note }}</div>
                                @endif
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                @if ($isActive)
                                    <form method="POST" action="{{ route('fonts.deactivate', $font) }}" class="flex-grow-1">
                                        @csrf
                                        <button class="btn btn-outline-warning w-100"><i class="bi bi-pause-circle"></i> غیرفعال‌سازی</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('fonts.activate', $font) }}" class="flex-grow-1">
                                        @csrf
                                        <button class="btn btn-primary w-100"><i class="bi bi-play-circle"></i> فعال‌سازی</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('fonts.destroy', $font) }}"
                                      onsubmit="return confirm('فونت «{{ $font->name }}» حذف شود؟ فایل آن نیز پاک می‌شود.')">
                                    @csrf
                                    <button class="btn btn-outline-danger" @if ($isActive) disabled title="ابتدا فونت را غیرفعال کنید" @endif>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card mb-3"><div class="card-body">
                        <x-empty icon="bi-fonts" title="فونتی بارگذاری نشده است"
                                 message="با فرم بالا اولین فونت را بارگذاری کنید."
                                 actionLabel="بارگذاری فونت" actionUrl="#"/>
                    </div></div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
