@extends('layouts.app')

@section('title', 'پرونده جامع دانشجو (Student 360)')
@section('page_title', 'پرونده جامع دانشجو — Student 360')

@php
    $fieldLabels = ['experimental' => 'تجربی', 'math' => 'ریاضی', 'humanities' => 'انسانی', 'arts' => 'هنر', 'language' => 'زبان'];
    $modeLabels = ['online' => 'آنلاین', 'phone' => 'تلفنی', 'in_person' => 'حضوری'];
    $statusLabels = ['scheduled' => 'در انتظار برگزاری', 'done' => 'برگزار شده', 'canceled' => 'لغو شده'];
    $goalPeriods = ['daily' => 'روزانه', 'weekly' => 'هفتگی', 'monthly' => 'ماهانه'];
@endphp

@section('content')
{{-- اطلاعات فردی و آموزشی --}}
<x-card title="اطلاعات فردی و آموزشی" icon="bi-person-vcard" class="mb-3">
    <div class="row g-3">
        <div class="col-md-3"><div class="small text-muted">نام</div><div class="fw-bold">{{ $student->name }}</div></div>
        <div class="col-md-3"><div class="small text-muted">پایه</div><div class="fw-bold">{{ $profile['grade'] ?: '—' }}</div></div>
        <div class="col-md-3"><div class="small text-muted">گروه آزمایشی</div><div class="fw-bold">{{ $fieldLabels[$profile['field']] ?? ($profile['field'] ?: '—') }}</div></div>
        <div class="col-md-3"><div class="small text-muted">شهر</div><div class="fw-bold">{{ $profile['city'] ?: '—' }}</div></div>
        <div class="col-md-3"><div class="small text-muted">موبایل</div><div class="fw-bold">{{ $profile['phone'] ? fa_num($profile['phone']) : '—' }}</div></div>
        <div class="col-md-3"><div class="small text-muted">ایمیل</div><div class="fw-bold">{{ $profile['email'] ?: '—' }}</div></div>
        <div class="col-md-3"><div class="small text-muted">تاریخ عضویت</div><div class="fw-bold">{{ fa_date($profile['joined']) }}</div></div>
        <div class="col-md-3"><div class="small text-muted">آخرین حضور</div><div class="fw-bold">{{ $profile['lastSeen'] ? fa_datetime($profile['lastSeen']) : '—' }}</div></div>
    </div>
</x-card>

{{-- شاخص‌های عملکرد واقعی --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-2">
        <x-stat icon="bi-graph-up" label="میانگین درصد" value="{{ fa_num($performance['avgPercent']) }}٪" color="success"/>
    </div>
    <div class="col-6 col-md-2">
        <x-stat icon="bi-ui-checks" label="آزمون‌های پایان‌یافته" value="{{ fa_num($performance['attemptsCount']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-2">
        <x-stat icon="bi-card-checklist" label="تعداد تست" value="{{ fa_num($performance['questionsAnswered']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-2">
        <x-stat icon="bi-clock-history" label="مطالعه ۷ روز" value="{{ duration_human($performance['study7']) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-2">
        <x-stat icon="bi-calendar2-week" label="مطالعه ۳۰ روز" value="{{ duration_human($performance['study30']) }}" color="secondary"/>
    </div>
    <div class="col-6 col-md-2">
        <x-stat icon="bi-lightning" label="استمرار ۱۴ روز" value="{{ fa_num($performance['consistency']) }}٪" color="danger"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        {{-- آزمون‌ها و کارنامه‌ها --}}
        <x-card title="آزمون‌ها و کارنامه‌ها" icon="bi-ui-checks" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>آزمون</th><th>درصد</th><th>صحیح/غلط/نزده</th><th>تاریخ</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td class="small">{{ $attempt->exam->title }}</td>
                            <td><strong>{{ fa_num($attempt->percent) }}٪</strong></td>
                            <td class="small text-muted">{{ fa_num($attempt->correct_count) }} / {{ fa_num($attempt->wrong_count) }} / {{ fa_num($attempt->blank_count) }}</td>
                            <td class="small text-muted">{{ fa_date($attempt->finished_at) }}</td>
                            <td>
                                @if (auth()->user()->canAccess('exams.take'))
                                    <a href="{{ route_if('exams.result', $attempt) }}" class="btn btn-sm btn-outline-primary">کارنامه</a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3"><x-empty icon="bi-ui-checks" title="آزمونی ثبت نشده است"/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- روند درصد آزمون‌ها --}}
        <x-card title="روند درصد آزمون‌ها" icon="bi-graph-up-arrow">
            <canvas id="trendChart" height="110"></canvas>
        </x-card>

        {{-- نقاط قوت و ضعف --}}
        <x-card title="نقاط قوت و ضعف" icon="bi-plus-slash-minus">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="fw-bold small text-success mb-2"><i class="bi bi-plus-circle"></i> نقاط قوت</div>
                    @forelse ($masteryRows->take(3) as $m)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span class="small">{{ $m->subject?->fullTitle() }}</span>
                            <span class="badge badge-soft-success">{{ fa_num($m->level) }}٪</span>
                        </div>
                    @empty
                        <div class="text-muted small">داده‌ای موجود نیست</div>
                    @endforelse
                </div>
                <div class="col-md-6">
                    <div class="fw-bold small text-danger mb-2"><i class="bi bi-exclamation-circle"></i> نقاط ضعف</div>
                    @forelse ($masteryRows->sortBy('level')->take(3) as $m)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span class="small">{{ $m->subject?->fullTitle() }}</span>
                            <span class="badge badge-soft-danger">{{ fa_num($m->level) }}٪</span>
                        </div>
                    @empty
                        <div class="text-muted small">داده‌ای موجود نیست</div>
                    @endforelse
                </div>
            </div>
        </x-card>

        {{-- سطح تسلط --}}
        <x-card title="سطح تسلط مباحث" icon="bi-speedometer" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>مبحث</th><th>تسلط</th><th>تست‌ها</th><th>ماندگاری</th></tr></thead>
                <tbody>
                @forelse ($masteryRows as $m)
                    <tr>
                        <td class="small">{{ $m->subject?->fullTitle() }}</td>
                        <td style="min-width:120px">
                            <div class="progress" style="height:6px">
                                <div class="progress-bar {{ $m->level >= 70 ? 'bg-success' : ($m->level >= 40 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ min(100, $m->level) }}%"></div>
                            </div>
                            <span class="small text-muted">{{ fa_num($m->level) }}٪</span>
                        </td>
                        <td class="small">{{ fa_num($m->correct) }} / {{ fa_num($m->attempted) }}</td>
                        <td class="small">{{ fa_num($m->retention) }}٪</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3"><x-empty icon="bi-speedometer" title="سطح تسلطی محاسبه نشده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
        </x-card>

        {{-- مرورها و ماندگاری --}}
        <x-card title="مرورها و ماندگاری مطالب" icon="bi-arrow-repeat">
            <div class="row text-center g-2">
                <div class="col-4">
                    <div class="border rounded py-2">
                        <div class="fs-4 fw-bold text-warning">{{ fa_num($reviewStats['pending']) }}</div>
                        <div class="small text-muted">در انتظار مرور</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded py-2">
                        <div class="fs-4 fw-bold text-primary">{{ fa_num($reviewStats['total']) }}</div>
                        <div class="small text-muted">کل کارت‌های مرور</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border rounded py-2">
                        <div class="fs-4 fw-bold text-success">{{ fa_num($reviewStats['retention']) }}٪</div>
                        <div class="small text-muted">ماندگاری ۳۰ روز</div>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- برنامه مطالعاتی فعال --}}
        <x-card title="برنامه مطالعاتی فعال" icon="bi-calendar2-week" bodyClass="py-0">
            @if ($activePlan)
                <div class="p-2 border-bottom small">
                    <strong>{{ $activePlan->title }}</strong>
                    <span class="text-muted"> — از {{ fa_date($activePlan->start_date) }} تا {{ fa_date($activePlan->end_date) }}</span>
                </div>
                <table class="table align-middle mb-0">
                    <thead><tr><th>تاریخ</th><th>مبحث/موضوع</th><th>هدف</th><th>انجام</th><th>وضعیت</th></tr></thead>
                    <tbody>
                    @foreach ($planItems as $item)
                        <tr>
                            <td class="small">{{ fa_date($item->date) }}</td>
                            <td class="small">{{ $item->subject?->title ?? $item->topic ?: 'مطالعه' }}</td>
                            <td class="small">{{ fa_num($item->target_minutes) }} دقیقه</td>
                            <td class="small">{{ fa_num($item->completed_minutes) }} دقیقه</td>
                            <td>
                                <x-badge :status="$item->status"
                                         :labels="['pending'=>'در انتظار','done'=>'انجام‌شده','partial'=>'نیمه‌تمام','missed'=>'عقب‌افتاده']"/>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="py-3"><x-empty icon="bi-calendar-x" title="برنامه مطالعاتی فعالی ندارد"/></div>
            @endif
        </x-card>

        {{-- اهداف --}}
        <x-card title="اهداف" icon="bi-bullseye" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>دوره</th><th>هدف زمان</th><th>هدف تست</th><th>بازه</th></tr></thead>
                <tbody>
                @forelse ($goals as $goal)
                    <tr>
                        <td class="small">{{ $goalPeriods[$goal->period] ?? $goal->period }}</td>
                        <td class="small">{{ duration_human($goal->target_minutes) }}</td>
                        <td class="small">{{ fa_num($goal->target_questions) }} تست</td>
                        <td class="small text-muted">{{ fa_date($goal->start_date) }} تا {{ fa_date($goal->end_date) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3"><x-empty icon="bi-bullseye" title="هدفی ثبت نشده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    <div class="col-lg-6">
        {{-- تخمین رتبه --}}
        <x-card title="تخمین رتبه" icon="bi-trophy">
            @if ($rankEstimate)
                <div class="text-center py-2">
                    <div class="text-muted small">آخرین تخمین — {{ fa_datetime($rankEstimate->created_at) }}</div>
                    <div class="fs-3 fw-bold text-primary">{{ fa_num(number_format($rankEstimate->estimated_rank)) }}</div>
                    <div class="small text-muted">رتبه تخمینی کشوری — صدک {{ fa_num($rankEstimate->percentile) }}</div>
                    <div class="mt-2 small">
                        بهترین: <strong>{{ fa_num(number_format($rankEstimate->scenario['best'] ?? 0)) }}</strong>
                        — منتظره: <strong>{{ fa_num(number_format($rankEstimate->scenario['expected'] ?? 0)) }}</strong>
                        — بدترین: <strong>{{ fa_num(number_format($rankEstimate->scenario['worst'] ?? 0)) }}</strong>
                    </div>
                </div>
            @else
                <x-empty icon="bi-trophy" title="تخمین رتبه‌ای ثبت نشده است"/>
            @endif
            @if ($historical->isNotEmpty())
                <div class="mt-2 border-top pt-2">
                    <div class="fw-bold small mb-1">نتایج تاریخی ثبت‌شده</div>
                    @foreach ($historical as $h)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>{{ $h->exam_name ?: 'کنکور' }} {{ fa_num($h->year) }}</span>
                            <span>رتبه واقعی: <strong>{{ fa_num(number_format($h->rank)) }}</strong> — درصد کل: {{ fa_num($h->total_percent) }}٪</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        {{-- اشتراک --}}
        <x-card title="اشتراک" icon="bi-star">
            @if ($subscription)
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">{{ $subscription->plan?->name ?? 'اشتراک' }}</div>
                        <div class="small text-muted">از {{ fa_date($subscription->starts_at) }} تا {{ fa_date($subscription->ends_at) }}</div>
                    </div>
                    <span class="badge badge-soft-success">فعال</span>
                </div>
            @else
                <x-empty icon="bi-star" title="اشتراک فعالی ندارد"/>
            @endif
        </x-card>

        {{-- دوره‌ها --}}
        <x-card title="دوره‌ها" icon="bi-mortarboard" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>دوره</th><th>پیشرفت</th></tr></thead>
                <tbody>
                @forelse ($enrollments as $en)
                    <tr>
                        <td class="small">{{ $en->course?->title }}</td>
                        <td style="min-width:130px">
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-primary" style="width: {{ $en->progress }}%"></div>
                            </div>
                            <span class="small text-muted">{{ fa_num($en->progress) }}٪</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="py-3"><x-empty icon="bi-book" title="در دوره‌ای ثبت‌نام نکرده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
        </x-card>

        {{-- کلاس‌ها --}}
        <x-card title="کلاس‌های آنلاین" icon="bi-camera-video" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>کلاس</th><th>زمان</th><th>وضعیت</th></tr></thead>
                <tbody>
                @forelse ($classes as $class)
                    <tr>
                        <td class="small">{{ $class->title }} <span class="text-muted">({{ $class->course?->title }})</span></td>
                        <td class="small text-muted">{{ fa_datetime($class->starts_at) }}</td>
                        <td>
                            <x-badge :status="$class->status"
                                     :labels="['scheduled'=>'زمان‌بندی‌شده','live'=>'در حال برگزاری','finished'=>'برگزار شد','canceled'=>'لغو شد']"/>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-3"><x-empty icon="bi-camera-video" title="کلاسی ثبت نشده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
        </x-card>

        {{-- خریدها --}}
        <x-card title="خریدها (سفارش‌ها)" icon="bi-receipt" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>شماره</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="small">{{ fa_num($order->number) }}</td>
                        <td class="small">{{ money($order->payable) }}</td>
                        <td>
                            <x-badge :status="$order->status"
                                     :labels="['pending'=>'در انتظار پرداخت','paid'=>'پرداخت‌شده','failed'=>'ناموفق','canceled'=>'لغو شده']"/>
                        </td>
                        <td class="small text-muted">{{ fa_date($order->created_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3"><x-empty icon="bi-receipt" title="سفارشی ثبت نشده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
        </x-card>

        {{-- تیکت‌ها --}}
        <x-card title="تیکت‌ها" icon="bi-life-preserver" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>موضوع</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="small">{{ $ticket->subject }}</td>
                        <td>
                            <x-badge :status="$ticket->status"
                                     :labels="['open'=>'باز','answered'=>'پاسخ داده شده','pending'=>'در انتظار','closed'=>'بسته']"/>
                        </td>
                        <td class="small text-muted">{{ fa_date($ticket->created_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-3"><x-empty icon="bi-life-preserver" title="تیکتی ثبت نشده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
        </x-card>

        {{-- جلسات مشاوره --}}
        <x-card title="جلسات مشاوره" icon="bi-calendar-heart" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>زمان</th><th>نوع</th><th>وضعیت</th><th>خلاصه</th></tr></thead>
                <tbody>
                @forelse ($sessions as $session)
                    <tr>
                        <td class="small">{{ fa_datetime($session->scheduled_at) }}</td>
                        <td class="small">{{ $modeLabels[$session->mode] ?? $session->mode }}</td>
                        <td><x-badge :status="$session->status" :labels="$statusLabels"/></td>
                        <td class="small text-muted" style="max-width:220px">{{ \Illuminate\Support\Str::limit($session->summary ?? '—', 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3"><x-empty icon="bi-calendar-heart" title="جلسه‌ای ثبت نشده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-2 border-top">
                <a href="{{ route_if('counselor.sessions') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> ثبت جلسه جدید</a>
            </div>
        </x-card>

        {{-- سوابق پیگیری --}}
        <x-card title="سوابق پیگیری" icon="bi-arrow-left-right" bodyClass="py-0">
            <table class="table align-middle mb-0">
                <thead><tr><th>متن</th><th>پیگیری بعدی</th><th>وضعیت</th></tr></thead>
                <tbody>
                @forelse ($followups as $fu)
                    <tr>
                        <td class="small" style="max-width:280px">{{ $fu->note }}</td>
                        <td class="small text-muted">{{ $fu->next_date ? fa_date($fu->next_date) : '—' }}</td>
                        <td><x-badge :status="$fu->status" :labels="['open'=>'باز','done'=>'انجام شد']"/></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-3"><x-empty icon="bi-arrow-left-right" title="پیگیری‌ای ثبت نشده است"/></td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-2 border-top">
                <a href="{{ route_if('counselor.followups') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> ثبت پیگیری</a>
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
(function () {
    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        const trend = @json($trend->values()->all());
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: trend.map(r => r.label),
                datasets: [{
                    label: 'درصد آزمون',
                    data: trend.map(r => r.value),
                    borderColor: '#2f5bea',
                    backgroundColor: 'rgba(47,91,234,.12)',
                    fill: true, tension: .35
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }
})();
</script>
@endpush
