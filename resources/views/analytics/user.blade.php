@extends('layouts.app')

@section('title', 'تحلیل عملکرد دانشجو')
@section('page_title', 'تحلیل عملکرد: ' . $target->name)

@push('styles')
<style>
    .chart-box { position: relative; }
</style>
@endpush

@section('content')
<div class="card mb-3 border-info">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between py-3">
        <div class="d-flex align-items-center gap-3">
            <span class="avatar" style="width:48px; height:48px; font-size:1.3rem">{{ mb_substr($target->name, 0, 1) }}</span>
            <div>
                <div class="fw-bold">{{ $target->name }}</div>
                <div class="small text-muted">{{ $target->email }} | پایه: {{ $target->grade ?: '—' }} | گروه: {{ $target->field_group ?: '—' }}</div>
            </div>
        </div>
        <div class="d-flex gap-2 no-print">
            <a href="{{ route_if('counselor.students') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right ms-1"></i> پنل مشاور</a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer ms-1"></i> چاپ گزارش</button>
        </div>
    </div>
</div>

@include('analytics._panel')
@endsection

@push('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
(function () {
    Chart.defaults.plugins.tooltip.rtl = true;
    Chart.defaults.plugins.tooltip.titleAlign = 'right';
    Chart.defaults.plugins.tooltip.bodyAlign = 'right';

    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        const trend = @json($trend);
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: trend.map(r => r.label),
                datasets: [{
                    label: 'درصد آزمون',
                    data: trend.map(r => r.value),
                    borderColor: '#2f5bea',
                    backgroundColor: 'rgba(47,91,234,.12)',
                    fill: true,
                    tension: .35
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { title: items => trend[items[0].dataIndex].exam || items[0].label } }
                },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }

    const accEl = document.getElementById('accuracyChart');
    if (accEl) {
        const acc = @json($subjectAccuracy);
        new Chart(accEl, {
            type: 'bar',
            data: {
                labels: acc.map(r => r.title),
                datasets: [{
                    label: 'دقت (٪)',
                    data: acc.map(r => r.accuracy),
                    backgroundColor: acc.map(r => r.accuracy >= 60 ? 'rgba(22,163,74,.7)' : (r.accuracy >= 40 ? 'rgba(245,158,11,.7)' : 'rgba(220,38,38,.7)')),
                    borderRadius: 6
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }

    const studyEl = document.getElementById('studyChart');
    if (studyEl) {
        const study = @json($study);
        new Chart(studyEl, {
            type: 'bar',
            data: {
                labels: study.map(r => r.label),
                datasets: [{
                    label: 'دقیقه مطالعه',
                    data: study.map(r => r.value),
                    backgroundColor: 'rgba(8,145,178,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    const retEl = document.getElementById('retentionChart');
    if (retEl) {
        const retention = {{ json_encode($retention['retention'] ?? 0) }};
        new Chart(retEl, {
            type: 'doughnut',
            data: {
                labels: ['ماندگار', 'فراموش‌شده'],
                datasets: [{
                    data: [retention, Math.max(0, 100 - retention)],
                    backgroundColor: ['#16a34a', 'rgba(107,117,144,.25)'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }
})();
</script>
@endpush
