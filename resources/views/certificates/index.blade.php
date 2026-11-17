@extends('layouts.app')

@section('title', 'گواهی‌نامه‌های من')
@section('page_title', 'گواهی‌نامه‌های من')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-patch-check" label="کل گواهی‌نامه‌ها" value="{{ fa_num($certificates->total()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-check2-circle" label="معتبر" value="{{ fa_num($certificates->filter(fn ($c) => ! $c->isRevoked())->count()) }}" color="success"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-x-octagon" label="ابطال‌شده" value="{{ fa_num($certificates->filter(fn ($c) => $c->isRevoked())->count()) }}" color="danger"/>
    </div>
</div>

<div class="row g-3">
    @forelse ($certificates as $certificate)
        <div class="col-md-6">
            <div class="card h-100 {{ $certificate->isRevoked() ? 'border-danger' : 'border-success' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-patch-check text-primary"></i> {{ $certificate->title }}</h6>
                        @if ($certificate->isRevoked())
                            <span class="badge badge-soft-danger">ابطال‌شده</span>
                        @else
                            <span class="badge badge-soft-success">معتبر</span>
                        @endif
                    </div>
                    @if ($certificate->course)
                        <div class="small text-muted mb-1"><i class="bi bi-mortarboard"></i> دوره: {{ $certificate->course->title }}</div>
                    @endif
                    <div class="small text-muted mb-3">
                        <div><i class="bi bi-calendar3"></i> تاریخ صدور: {{ fa_datetime($certificate->issued_at) }}</div>
                        <div dir="ltr" class="text-truncate"><i class="bi bi-upc-scan"></i> کد یکتا: {{ $certificate->uuid }}</div>
                    </div>
                    <div class="d-flex gap-2">
                        @if (! $certificate->isRevoked())
                            <a href="{{ route('certificates.show', $certificate) }}" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> مشاهده و چاپ</a>
                        @else
                            <button class="btn btn-secondary btn-sm" disabled><i class="bi bi-lock"></i> قابل چاپ نیست</button>
                        @endif
                        <a href="{{ url('public/certificate/' . $certificate->uuid) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-link-45deg"></i> لینک استعلام عمومی</a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty icon="bi-patch-check" title="گواهی‌نامه‌ای صادر نشده است"
                     message="با تکمیل دوره‌های آکادمی، گواهی‌نامه خودکار صادر می‌شود."
                     actionLabel="دوره‌های من" actionUrl="{{ route('academy.my-courses') }}"/>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $certificates->links() }}
</div>
@endsection
