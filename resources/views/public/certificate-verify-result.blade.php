@extends('layouts.public')

@section('title', 'نتیجه استعلام گواهی‌نامه')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        @if ($certificate->isRevoked())
            <div class="alert alert-danger d-flex gap-2 align-items-center">
                <i class="bi bi-x-octagon"></i>
                <div><strong>این گواهی‌نامه ابطال شده است.</strong> از تاریخ {{ fa_datetime($certificate->revoked_at) }} اعتبار ندارد.</div>
            </div>
        @else
            <div class="alert alert-success d-flex gap-2 align-items-center">
                <i class="bi bi-shield-check"></i>
                <div><strong>گواهی‌نامه معتبر است</strong> — اصالت این گواهی‌نامه تأیید شد.</div>
            </div>
        @endif

        <div class="card certificate-result text-center p-4">
            <div class="mb-3">
                <span class="d-inline-grid" style="width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#2f5bea,#7c3aed);place-items:center;color:#fff;font-weight:800;font-size:1.5rem">K</span>
            </div>
            <h5 class="fw-bold mb-3">{{ $certificate->title }}</h5>
            <table class="table table-borderless w-auto mx-auto mb-0 text-start small">
                <tbody>
                    <tr>
                        <th class="text-muted">دارنده گواهی‌نامه</th>
                        <td class="fw-bold">{{ $certificate->data['student_name'] ?? $certificate->user?->name }}</td>
                    </tr>
                    @if ($certificate->course)
                        <tr>
                            <th class="text-muted">دوره</th>
                            <td>{{ $certificate->course->title }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th class="text-muted">تاریخ صدور</th>
                        <td>{{ $certificate->data['issue_date_fa'] ?? fa_date($certificate->issued_at) }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">کد یکتا</th>
                        <td dir="ltr">{{ $certificate->uuid }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">وضعیت</th>
                        <td>
                            @if ($certificate->isRevoked())
                                <span class="badge badge-soft-danger">ابطال‌شده</span>
                            @else
                                <span class="badge badge-soft-success">معتبر</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('verify.certificate.form') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shield-check"></i> استعلام گواهی‌نامه دیگر</a>
            <a href="{{ route('articles.public.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-newspaper"></i> مقالات آموزشی</a>
        </div>
    </div>
</div>
@endsection
