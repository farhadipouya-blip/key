@extends('layouts.app')

@section('title', 'جزئیات فرم انتخاب رشته')
@section('page_title', $selection->title ?: 'فرم انتخاب رشته')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .key-sidebar, .key-topbar { display: none !important; }
        .key-wrapper { margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        body { background: #fff !important; }
    }
    .print-header { display: none; }
    @media print {
        .print-header { display: block; text-align: center; margin-bottom: 1rem; }
    }
</style>
@endpush

@section('content')
<div class="print-header">
    <h5 class="mb-1">سامانه کلید — فرم انتخاب رشته</h5>
    <div class="small">{{ $selection->title }} — {{ auth()->user()->name }} — {{ fa_datetime(now()) }}</div>
</div>

<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-trophy" label="رتبه تخمینی مبنا" value="{{ $latestEstimate?->estimated_rank ? fa_num(number_format($latestEstimate->estimated_rank)) : '—' }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-shield-check" label="کدهای امن (شانس ≥ ۶۰٪)" value="{{ fa_num($summary['safe_count']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-exclamation-triangle" label="کدهای متوسط (۳۰ تا ۵۹٪)" value="{{ fa_num($summary['mid_count']) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-x-octagon" label="کدهای ریسکی (< ۳۰٪)" value="{{ fa_num($summary['risky_count']) }}" color="danger"/>
    </div>
</div>

<div class="card mb-3 no-print">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="d-flex gap-2">
            <a href="{{ route('rank.selections') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right ms-1"></i> فرم‌های من</a>
            <a href="{{ route('rank.selection') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg"></i> افزودن کد رشته</a>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if ($selection->status === 'final')
                <form method="POST" action="{{ route('rank.selection.save') }}">
                    @csrf
                    <input type="hidden" name="selection_id" value="{{ $selection->id }}">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="status" value="draft">
                    <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-counterclockwise ms-1"></i> برگرداندن به پیش‌نویس</button>
                </form>
            @else
                <form method="POST" action="{{ route('rank.selection.save') }}"
                      onsubmit="return confirm('فرم نهایی شود؟ پس از ثبت نهایی در صورت نیاز می‌توانید به پیش‌نویس برگردانید.')">
                    @csrf
                    <input type="hidden" name="selection_id" value="{{ $selection->id }}">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="status" value="final">
                    <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check2-circle ms-1"></i> ثبت نهایی فرم</button>
                </form>
            @endif
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer ms-1"></i> چاپ / خروجی PDF</button>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-card-checklist text-primary ms-1"></i> کد رشته‌ها به‌ترتیب اولویت</h6>
        <span class="badge rounded-pill {{ $selection->status === 'final' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
            {{ $selection->status === 'final' ? 'نهایی‌شده' : 'پیش‌نویس' }}
        </span>
    </div>
    <div class="card-body py-0">
        @if ($items->isEmpty())
            <x-empty icon="bi-card-checklist" title="کد رشته‌ای ثبت نشده" message="از بانک رشته‌ها کد رشته اضافه کنید." actionLabel="بانک رشته‌ها" actionUrl="{{ route('rank.selection') }}"/>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>اولویت</th>
                            <th>رشته</th>
                            <th>دانشگاه</th>
                            <th>دوره</th>
                            <th>حدنصاب سال قبل</th>
                            <th style="min-width:150px">شانس قبولی</th>
                            <th class="no-print" style="width:90px">ترتیب</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $item)
                        @php
                            $row = $item['row'];
                            $chance = (int) $item['chance'];
                            $bar = $chance >= 60 ? 'bg-success' : ($chance >= 30 ? 'bg-warning text-dark' : 'bg-danger');
                        @endphp
                        <tr>
                            <td><span class="badge badge-soft-primary">{{ fa_num($item['priority'] ?? 0) }}</span></td>
                            <td class="fw-bold">{{ $row?->major?->title ?? '—' }}</td>
                            <td>
                                <div class="small">{{ $row?->university?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $row?->university?->city }}</div>
                            </td>
                            <td class="small">
                                {{ \App\Http\Controllers\RankController::TUITION_TYPES[$row?->tuition_type ?? ''] ?? '—' }}
                            </td>
                            <td class="small">
                                {{ $row?->last_rank_min ? fa_num(number_format($row->last_rank_min)) : '—' }}
                                تا
                                {{ $row?->last_rank_max ? fa_num(number_format($row->last_rank_max)) : '—' }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:10px">
                                        <div class="progress-bar {{ $bar }}" style="width: {{ max(3, $chance) }}%"></div>
                                    </div>
                                    <span class="small fw-bold">{{ fa_num($chance) }}٪</span>
                                </div>
                            </td>
                            <td class="no-print">
                                <div class="btn-group btn-group-sm">
                                    <form method="POST" action="{{ route('rank.selection.save') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="selection_id" value="{{ $selection->id }}">
                                        <input type="hidden" name="action" value="move">
                                        <input type="hidden" name="item_id" value="{{ $item['major_university_id'] }}">
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-outline-secondary py-0 px-1" title="اولویت بالاتر"><i class="bi bi-arrow-up small"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('rank.selection.save') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="selection_id" value="{{ $selection->id }}">
                                        <input type="hidden" name="action" value="move">
                                        <input type="hidden" name="item_id" value="{{ $item['major_university_id'] }}">
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-outline-secondary py-0 px-1" title="اولویت پایین‌تر"><i class="bi bi-arrow-down small"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <x-card title="خلاصه تحلیل شانس" icon="bi-graph-up">
            @php $avg = (float) ($summary['avg_chance'] ?? 0); @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>میانگین شانس قبولی فرم</span>
                    <span class="fw-bold">{{ fa_num($avg) }}٪</span>
                </div>
                <div class="progress" style="height:12px">
                    <div class="progress-bar {{ $avg >= 60 ? 'bg-success' : ($avg >= 30 ? 'bg-warning text-dark' : 'bg-danger') }}"
                         style="width: {{ max(3, $avg) }}%"></div>
                </div>
            </div>
            <ul class="small text-muted mb-0 ps-3">
                <li>ترکیب متعادل یعنی حدود ۵۰٪ کدهای امن، ۳۰٪ متوسط و ۲۰٪ ریسکی انتخاب کنید تا شانس پوشش کامل داشته باشید.</li>
                <li>کدهای امن را بالاتر از کدهای ریسکی بگذارید؛ در کنکور کد بالاتر اول پر می‌شود.</li>
                <li>حدنصاب‌ها بر اساس سال {{ fa_num($latestEstimate?->created_at?->year ?? 1403) }} است و هر سال تغییر می‌کند.</li>
            </ul>
        </x-card>
    </div>
    <div class="col-lg-5">
        <x-card title="گزارش نهایی (قابل چاپ)" icon="bi-file-earmark-text">
            <div class="small" style="white-space: pre-wrap; line-height: 2">گزارش فرم انتخاب رشته «{{ $selection->title }}»
مبنا: رتبه تخمینی {{ $summary['estimated_rank'] ? fa_num(number_format($summary['estimated_rank'])) : '—' }} کشوری
تعداد کد رشته: {{ fa_num($summary['total']) }} | میانگین شانس: {{ fa_num($summary['avg_chance']) }}٪
ترکیب: {{ fa_num($summary['safe_count']) }} امن، {{ fa_num($summary['mid_count']) }} متوسط، {{ fa_num($summary['risky_count']) }} ریسکی
@forelse ($items as $item)
    {{ fa_num($item['priority'] ?? 0) }}. {{ $item['row']?->major?->title }} — {{ $item['row']?->university?->name }} (شانس {{ fa_num($item['chance']) }}٪)
@empty
    (کد رشته‌ای ثبت نشده است)
@endforelse
تاریخ گزارش: {{ fa_datetime(now()) }}</div>
        </x-card>
    </div>
</div>
@endsection
