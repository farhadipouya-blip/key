@extends('layouts.app')

@section('title', 'پایش زنده')
@section('page_title', 'پایش زنده سامانه')

@section('content')
<div class="row g-3 mb-1">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-broadcast" label="کاربران آنلاین (۵ دقیقه اخیر)" value="{{ fa_num($stats['online']) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-person-plus" label="ثبت‌نام امروز" value="{{ fa_num($stats['today_registrations']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-calendar-week" label="ثبت‌نام ۷ روز اخیر" value="{{ fa_num($stats['week_registrations']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-people" label="کل کاربران" value="{{ fa_num($stats['total_users']) }}" color="warning" url="{{ route_if('admin.users.index') }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-hourglass-split" label="کارهای در صف" value="{{ fa_num($stats['jobs_pending']) }}" color="secondary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-exclamation-triangle" label="کارهای شکست‌خورده" value="{{ fa_num($stats['failed_jobs']) }}" color="{{ $stats['failed_jobs'] > 0 ? 'danger' : 'secondary' }}"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="روند ثبت‌نام کاربران (۱۴ روز اخیر)" icon="bi-graph-up" bodyClass="pb-4">
            <canvas id="regChart" height="110"></canvas>
        </x-card>

        <x-card title="عملکرد کارکنان" icon="bi-person-badge" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>کارمند</th>
                            <th>نقش</th>
                            <th>رخدادها</th>
                            <th>سؤال ساخته</th>
                            <th>واردات</th>
                            <th>رسانه</th>
                            <th>آخرین فعالیت</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($staffPerformance as $staff)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar" style="width:30px;height:30px;font-size:.8rem">{{ mb_substr($staff->name, 0, 1) }}</span>
                                    <span class="small fw-bold">{{ $staff->name }}</span>
                                </div>
                            </td>
                            <td class="small">{{ $staff->roles->pluck('name')->implode('، ') ?: '—' }}</td>
                            <td class="small">{{ fa_num($staff->logs_count) }}</td>
                            <td class="small">{{ fa_num($staff->questions_count) }}</td>
                            <td class="small">{{ fa_num($staff->imports_count) }}</td>
                            <td class="small">{{ fa_num($staff->media_count) }}</td>
                            <td class="small text-muted">{{ $staff->last_seen_at ? fa_datetime($staff->last_seen_at) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4 small">کارمندی یافت نشد</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="کاربران آنلاین" icon="bi-broadcast-pin" bodyClass="py-0">
            <div style="max-height:260px; overflow-y:auto">
                @forelse ($onlineUsers as $u)
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar" style="width:30px;height:30px;font-size:.8rem">{{ mb_substr($u->name, 0, 1) }}</span>
                            <div>
                                <div class="small fw-bold">{{ $u->name }}</div>
                                <div class="text-muted" style="font-size:.72rem">{{ $u->email }}</div>
                            </div>
                        </div>
                        <span class="badge rounded-pill badge-soft-success">{{ fa_datetime($u->last_seen_at) }}</span>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted small">در ۵ دقیقه اخیر کاربری آنلاین نبوده است</div>
                @endforelse
            </div>
        </x-card>

        <x-card title="آخرین رخدادها" icon="bi-activity" bodyClass="py-0">
            <div style="max-height:300px; overflow-y:auto">
                @forelse ($recentActivity as $log)
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

        <x-card title="آخرین پیامک‌ها" icon="bi-envelope-paper" bodyClass="py-0">
            <div style="max-height:260px; overflow-y:auto">
                @forelse ($recentSms as $sms)
                    <div class="p-2 border-bottom small">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">{{ $sms->user?->name ?? fa_num($sms->phone ?? '—') }}</span>
                            <x-badge :status="$sms->status" :labels="['sent' => 'ارسال شد', 'failed' => 'ناموفق', 'queued' => 'در صف', 'pending' => 'در انتظار', 'simulated' => 'شبیه‌سازی‌شده']"/>
                        </div>
                        <div class="text-muted">{{ Str::limit($sms->body, 60) }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ fa_datetime($sms->created_at) }}</div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted small">پیامکی ارسال نشده است</div>
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
                tension: .35,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endpush
