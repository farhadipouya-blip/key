@extends('layouts.app')

@section('title', 'گزارش عملکرد هوشمند')
@section('page_title', 'گزارش عملکرد هوشمند')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .key-sidebar, .key-topbar { display: none !important; }
        .key-wrapper { margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        body { background: #fff !important; }
    }
    .report-body { white-space: pre-wrap; line-height: 2.1; font-size: .95rem; }
</style>
@endpush

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clipboard-data" label="تعداد آزمون‌ها" value="{{ fa_num($performance['attempts_count']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-bullseye" label="میانگین درصد" value="{{ fa_num($performance['avg_percent']) }}٪" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-alarm" label="مطالعه ۱۴ روز اخیر" value="{{ duration_human($performance['study_minutes_14d']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-arrow-repeat" label="ماندگاری مرور" value="{{ fa_num($performance['review_retention_percent']) }}٪" color="warning"/>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-file-earmark-bar-graph text-primary ms-1"></i> متن گزارش</h6>
        <span class="badge rounded-pill {{ $usedAi ? 'badge-soft-success' : 'badge-soft-secondary' }}">
            {{ $usedAi ? 'تولید با هوش مصنوعی' : 'تولید قانون‌محور (AI تنظیم نشده)' }}
        </span>
    </div>
    <div class="card-body">
        <div class="report-body">{{ $message->content }}</div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center no-print">
        <span class="small text-muted">گزارش در {{ fa_datetime($message->created_at) }} تولید شد — در گفتگوهای شما (نوع گزارش) ذخیره شد.</span>
        <div class="d-flex gap-2">
            <a href="{{ route('ai.index', ['c' => $conversation->id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chat-dots ms-1"></i> گفتگوها</a>
            <a href="{{ route('ai.report') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise ms-1"></i> تولید گزارش جدید</a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer ms-1"></i> چاپ / خروجی PDF</button>
        </div>
    </div>
</div>

<div class="row g-3 no-print">
    <div class="col-lg-6">
        <x-card title="داده خام گزارش (نقاط قوت)" icon="bi-hand-thumbs-up" bodyClass="py-0">
            <div class="list-group list-group-flush">
                @forelse (collect($performance['strengths'])->take(3) as $s)
                    <div class="list-group-item d-flex justify-content-between small">
                        <span class="fw-bold">{{ $s['subject'] ?? '—' }}</span>
                        <span>تسلط {{ fa_num($s['level']) }}٪</span>
                    </div>
                @empty
                    <div class="p-3 text-center text-muted small">داده‌ای ثبت نشده است</div>
                @endforelse
            </div>
        </x-card>
    </div>
    <div class="col-lg-6">
        <x-card title="داده خام گزارش (نقاط ضعف)" icon="bi-hand-thumbs-down" bodyClass="py-0">
            <div class="list-group list-group-flush">
                @forelse (collect($performance['weaknesses'])->take(3) as $s)
                    <div class="list-group-item d-flex justify-content-between small">
                        <span class="fw-bold">{{ $s['subject'] ?? '—' }}</span>
                        <span>تسلط {{ fa_num($s['level']) }}٪</span>
                    </div>
                @empty
                    <div class="p-3 text-center text-muted small">داده‌ای ثبت نشده است</div>
                @endforelse
            </div>
        </x-card>
    </div>
</div>
@endsection
