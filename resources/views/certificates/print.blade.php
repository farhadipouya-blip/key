@extends('layouts.app')

@section('title', 'گواهی‌نامه: ' . $certificate->title)
@section('page_title', 'مشاهده و چاپ گواهی‌نامه')

@section('content')
@push('styles')
<style>
    .certificate-card {
        border: 6px double var(--key-primary, #2f5bea);
        border-radius: 14px;
        padding: 2.5rem 2rem;
        text-align: center;
        background:
            radial-gradient(circle at top left, rgba(47,91,234,.06), transparent 55%),
            radial-gradient(circle at bottom right, rgba(124,58,237,.06), transparent 55%);
    }
    .certificate-card.en { direction: ltr; font-family: Georgia, 'Times New Roman', serif; }
    @media print {
        .key-sidebar, .key-topbar, footer, .no-print { display: none !important; }
        .key-wrapper { margin: 0 !important; }
        .certificate-card { page-break-inside: avoid; }
        body { background: #fff !important; }
    }
</style>
@endpush

@if ($certificate->isRevoked())
    <div class="alert alert-danger no-print d-flex gap-2 align-items-center">
        <i class="bi bi-x-octagon"></i>
        <div><strong>این گواهی‌نامه ابطال شده است</strong> — از تاریخ {{ fa_datetime($certificate->revoked_at) }} اعتبار خود را از دست داده و قابل چاپ نیست.</div>
    </div>
@else
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="certificate-card {{ $certificate->type === 'en' ? 'en' : '' }}" id="certCard">
                <div class="mb-3">
                    <span class="logo d-inline-grid" style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#2f5bea,#7c3aed);place-items:center;color:#fff;font-weight:800;font-size:1.6rem">K</span>
                </div>

                @if ($certificate->type === 'en')
                    <div class="text-uppercase text-muted small tracking-wide">Certificate of Completion</div>
                    <h2 class="fw-bold mt-2">{{ $certificate->data['site_name'] ?? 'کلید (KEY)' }}</h2>
                    <p class="mt-3 mb-1">This is to certify that</p>
                    <h3 class="fw-bold text-primary">{{ $certificate->data['student_name'] ?? $certificate->user->name }}</h3>
                    <p class="mt-2 mb-1">has successfully completed</p>
                    <h4 class="fw-bold">{{ $certificate->data['course_title'] ?: $certificate->title }}</h4>
                    <p class="text-muted small mt-3">Issued on: {{ $certificate->data['issue_date_en'] ?? $certificate->issued_at->format('Y-m-d') }}</p>
                    <p class="text-muted small">Certificate ID: {{ $certificate->uuid }}</p>
                @else
                    <div class="text-muted small">سامانه جامع آموزشی کلید</div>
                    <h2 class="fw-bold mt-2 mb-3">گواهی‌نامه اتمام دوره</h2>
                    <p class="mb-1">بدین‌وسیله گواهی می‌شود</p>
                    <h3 class="fw-bold text-primary">{{ $certificate->data['student_name'] ?? $certificate->user->name }}</h3>
                    <p class="mt-2 mb-1">در دوره</p>
                    <h4 class="fw-bold">{{ $certificate->data['course_title'] ?: $certificate->title }}</h4>
                    <p class="mt-2">با موفقیت تکمیل کرده است.</p>
                    <div class="d-flex justify-content-center gap-4 mt-3 small text-muted">
                        <span><i class="bi bi-calendar3"></i> تاریخ صدور: {{ $certificate->data['issue_date_fa'] ?? fa_date($certificate->issued_at) }}</span>
                        @if ($certificate->issuer)
                            <span><i class="bi bi-person-badge"></i> صادرکننده: {{ $certificate->issuer->name }}</span>
                        @endif
                    </div>
                    <p class="text-muted small mt-2" dir="ltr">کد یکتا: {{ $certificate->uuid }}</p>
                @endif

                <div class="d-flex justify-content-center mt-4">
                    <div id="qrcode"></div>
                </div>
                <div class="small text-muted mt-2" dir="ltr">
                    {{ url('public/certificate/' . $certificate->uuid) }}
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-center mt-4 no-print">
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> چاپ گواهی‌نامه</button>
                <a href="{{ url('public/certificate/' . $certificate->uuid) }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-link-45deg"></i> صفحه استعلام عمومی</a>
                <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> بازگشت</a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('assets/js/qrcode.min.js') }}"></script>
    <script>
        new QRCode(document.getElementById('qrcode'), {
            text: '{{ url("public/certificate/" . $certificate->uuid) }}',
            width: 120,
            height: 120
        });
    </script>
    @endpush
@endif
@endsection
