@extends('layouts.public')

@section('title', 'استعلام گواهینامه')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <x-card title="استعلام اصالت گواهینامه" icon="bi-shield-check">
            <p class="text-muted small">کد یکتای روی گواهینامه را وارد کنید تا اصالت آن بررسی شود.</p>
            <form method="POST" action="{{ route('verify.certificate.check') }}">
                @csrf
                <div class="input-group">
                    <input type="text" name="code" class="form-control" placeholder="کد یکتا (۳۲ نویسه)" value="{{ old('code', $code ?? '') }}" dir="ltr" required>
                    <button class="btn btn-primary" type="submit">استعلام</button>
                </div>
                @error('code') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
            </form>

            @if (isset($certificate) && $certificate)
                @if ($certificate->isRevoked())
                    <div class="alert alert-danger mt-3">
                        <strong>این گواهینامه ابطال شده است.</strong>
                    </div>
                @else
                    <div class="alert alert-success mt-3">
                        <strong><i class="bi bi-check-circle"></i> گواهینامه معتبر است</strong>
                        <ul class="mb-0 mt-2 small">
                            <li>نام دارنده: {{ $certificate->data['student_name'] ?? $certificate->user->name }}</li>
                            <li>عنوان: {{ $certificate->title }}</li>
                            <li>تاریخ صدور: {{ $certificate->data['issue_date_fa'] ?? fa_date($certificate->issued_at) }}</li>
                            <li>کد یکتا: {{ $certificate->uuid }}</li>
                        </ul>
                    </div>
                @endif
            @elseif (isset($code) && $code)
                <div class="alert alert-warning mt-3">گواهینامه‌ای با این کد یافت نشد.</div>
            @endif
        </x-card>
    </div>
</div>
@endsection
