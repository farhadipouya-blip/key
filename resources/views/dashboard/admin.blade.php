@extends('layouts.app')

@section('title', 'داشبورد مدیریت')
@section('page_title', 'پایش سامانه — نمای کلان')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-people" label="کل کاربران" value="{{ fa_num($stats['users']) }}" color="primary" url="{{ route_if('admin.users.index') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-mortarboard" label="دانشجویان" value="{{ fa_num($stats['students']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-broadcast" label="کاربران آنلاین (۵ دقیقه اخیر)" value="{{ fa_num($stats['online']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-person-plus" label="ثبت‌نام ۷ روز اخیر" value="{{ fa_num($stats['newUsers']) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-database" label="سؤالات بانک" value="{{ fa_num($stats['questions']) }}" color="secondary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-ui-checks" label="آزمون‌ها" value="{{ fa_num($stats['exams']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-cash-stack" label="درآمد کل" value="{{ money($stats['revenue']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-star" label="اشتراک فعال" value="{{ fa_num($stats['activeSubs']) }}" color="warning"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="روند ثبت‌نام کاربران (۱۴ روز اخیر)" icon="bi-graph-up" bodyClass="pb-4">
            <canvas id="regChart" height="110"></canvas>
        </x-card>

        <x-card title="کاربران جدید" icon="bi-person-plus" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>نام</th><th>موبایل</th><th>نقش</th><th>تاریخ ثبت‌نام</th></tr></thead>
                    <tbody>
                    @forelse ($recentUsers as $u)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar" style="width:32px;height:32px;font-size:.85rem">{{ mb_substr($u->name, 0, 1) }}</span>
                                    <span>{{ $u->name }}</span>
                                </div>
                            </td>
                            <td>{{ $u->phone ?: '—' }}</td>
                            <td><small>{{ $u->roles->pluck('name')->implode('، ') ?: '—' }}</small></td>
                            <td><small>{{ fa_datetime($u->created_at) }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">کاربری ثبت‌نام نکرده است</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
    <div class="col-lg-4">
        <x-card title="آمار عملیاتی" icon="bi-clipboard-data">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">مطالعه کاربران (۷ روز)</span>
                    <strong>{{ duration_human($stats['studyMinutes']) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">تیکت‌های باز</span>
                    <strong>{{ fa_num($stats['openTickets']) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">مشاوران</span>
                    <strong>{{ fa_num($stats['counselors']) }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">مدرسان</span>
                    <strong>{{ fa_num($stats['teachers']) }}</strong>
                </div>
            </div>
        </x-card>

        <x-card title="آخرین فعالیت‌ها" icon="bi-activity" bodyClass="py-0">
            <div style="max-height:320px; overflow-y:auto">
                @forelse ($recentAudit as $log)
                    <div class="p-2 border-bottom small d-flex gap-2 align-items-start">
                        <i class="bi bi-dot text-primary"></i>
                        <div>
                            <span class="fw-bold">{{ $log->user?->name ?? 'سیستم' }}</span>
                            <span class="text-muted">— {{ $log->action }}</span>
                            <div class="text-muted" style="font-size:.75rem">{{ fa_datetime($log->created_at) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-3 text-center text-muted small">فعالیتی ثبت نشده</div>
                @endforelse
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
(function () {
    const el = document.getElementById('regChart');
    if (!el) return;
    const data = @json($registrationsChart->map(fn ($r) => ['d' => fa_date($r->d), 'c' => (int) $r->c]));
    new Chart(el, {
        type: 'line',
        data: {
            labels: data.map(r => r.d),
            datasets: [{
                label: 'ثبت‌نام',
                data: data.map(r => r.c),
                borderColor: '#2f5bea',
                backgroundColor: 'rgba(47,91,234,.12)',
                fill: true,
                tension: .35
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endpush
