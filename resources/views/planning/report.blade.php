@extends('layouts.app')

@section('title', 'گزارش استمرار مطالعه')
@section('page_title', 'گزارش استمرار مطالعه')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-lightning-charge" label="درصد استمرار (۱۴ روز)" value="{{ fa_num($consistency) }}٪" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-list-check" label="مجموع برنامه‌ریزی‌شده" value="{{ duration_human($totalPlanned) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-all" label="مجموع انجام‌شده" value="{{ duration_human($totalCompleted) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clock" label="جلسات ثبت‌شده" value="{{ fa_num($totalSessions) }}" color="info"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="مطالعه ۱۴ روز اخیر — برنامه در برابر واقعیت" icon="bi-graph-up">
            <canvas id="studyChart" height="110"></canvas>
        </x-card>

        <x-card title="مقایسه روزبه‌روز" icon="bi-table" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>تاریخ</th><th>برنامه (دقیقه)</th><th>انجام (دقیقه)</th><th>نسبت</th><th>حالت</th></tr></thead>
                    <tbody>
                    @foreach (array_reverse($days) as $day)
                        @php $ratio = percent($day['completed'], $day['planned']); @endphp
                        <tr>
                            <td class="small">{{ fa_date($day['date']) }}</td>
                            <td class="small">{{ fa_num($day['planned']) }}</td>
                            <td class="small">{{ fa_num($day['completed']) }}</td>
                            <td class="small">{{ $day['planned'] ? fa_num($ratio).'٪' : '—' }}</td>
                            <td>
                                @if ($day['planned'] === 0)
                                    <span class="badge rounded-pill badge-soft-secondary">بدون برنامه</span>
                                @elseif ($day['completed'] >= $day['planned'])
                                    <span class="badge rounded-pill badge-soft-success">کامل</span>
                                @elseif ($day['completed'] >= $day['planned'] * 0.6)
                                    <span class="badge rounded-pill badge-soft-warning">قابل قبول</span>
                                @else
                                    <span class="badge rounded-pill badge-soft-danger">عقب‌افتادگی</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="وضعیت آیتم‌ها (۱۴ روز)" icon="bi-pie-chart">
            @php
                $statusLabels = ['pending'=>'در انتظار','partial'=>'نیمه‌تمام','done'=>'انجام‌شده','missed'=>'عقب‌افتاده'];
                $statusColors = ['pending'=>'info','partial'=>'warning','done'=>'success','missed'=>'danger'];
            @endphp
            @forelse ($statusLabels as $key => $label)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <x-badge :status="$key" :labels="$statusLabels"/>
                    <strong>{{ fa_num($statusCounts[$key] ?? 0) }}</strong>
                </div>
            @empty
                <p class="text-muted small mb-0">داده‌ای برای نمایش نیست</p>
            @endforelse
        </x-card>

        <x-card title="تحلیل استمرار" icon="bi-lightbulb">
            <p class="small text-muted mb-2">
                استمرار یعنی روزهایی که حداقل ۶۰٪ برنامه مطالعاتی آن روز انجام شده است.
                @if ($consistency >= 80)
                    عملکرد شما عالی است؛ همین روند را حفظ کنید.
                @elseif ($consistency >= 50)
                    روند شما متوسط است؛ سعی کنید حجم برنامه را با واقعیت زمانی خود هماهنگ کنید.
                @else
                    استمرار پایین است؛ از دکمه «بازطراحی هوشمند» در صفحه برنامه استفاده کنید تا آیتم‌های عقب‌مانده بازتوزیع شوند.
                @endif
            </p>
            <a href="{{ route('plan.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-right"></i> بازگشت به برنامه</a>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
    (function () {
        const ctx = document.getElementById('studyChart');
        if (!ctx || typeof Chart === 'undefined') return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($labels),
                datasets: [
                    {
                        label: 'برنامه‌ریزی‌شده (دقیقه)',
                        data: @json(collect($days)->pluck('planned')),
                        backgroundColor: 'rgba(47,91,234,.25)',
                        borderColor: 'rgba(47,91,234,1)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'انجام‌شده (دقیقه)',
                        data: @json(collect($days)->pluck('completed')),
                        backgroundColor: 'rgba(22,163,74,.65)',
                        borderColor: 'rgba(22,163,74,1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        type: 'line',
                        tension: .35,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'دقیقه' } }
                },
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: 'Vazirmatn' } } }
                }
            }
        });
    })();
</script>
@endpush
