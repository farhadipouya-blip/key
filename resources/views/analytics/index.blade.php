@extends('layouts.app')

@section('title', 'تحلیل عملکرد')
@section('page_title', 'تحلیل عملکرد من')

@push('styles')
<style>
    .chart-box { position: relative; }
</style>
@endpush

@section('content')
@include('analytics._panel')
@endsection

@push('scripts')
<script src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script>
(function () {
    const faFont = getComputedStyle(document.documentElement).getPropertyValue('--key-font-family') || 'inherit';
    Chart.defaults.font.family = faFont.trim();
    Chart.defaults.plugins.tooltip.rtl = true;
    Chart.defaults.plugins.tooltip.titleAlign = 'right';
    Chart.defaults.plugins.tooltip.bodyAlign = 'right';

    // روند میانگین درصد
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
                    tension: .35,
                    max: 100,
                    min: 0
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

    // دقت هر درس
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

    // دقیقه مطالعه ۱۴ روز
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

    // ماندگاری مرور
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
