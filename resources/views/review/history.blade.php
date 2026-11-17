@extends('layouts.app')

@section('title', 'تاریخچه مرور')
@section('page_title', 'تاریخچه مرور')

@section('content')
<x-card title="گزارش مرورها" icon="bi-clock-history" bodyClass="py-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>کیفیت</th><th>فاصله قبل</th><th>فاصله بعد</th><th>ضریب سهولت</th><th>زمان</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>
                        <x-badge :status="match($log->quality){1=>'danger',2=>'warning',3=>'primary',default=>'success'}"
                                 :labels="['1'=>'دوباره','2'=>'سخت','3'=>'خوب','4'=>'آسان','danger'=>'—']"/>
                    </td>
                    <td class="small">{{ fa_num($log->interval_before) }} روز</td>
                    <td class="small">{{ fa_num($log->interval_after) }} روز</td>
                    <td class="small">{{ fa_num($log->easiness_after) }}</td>
                    <td class="small text-muted">{{ fa_datetime($log->reviewed_at) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4"><x-empty icon="bi-arrow-repeat" title="مروری ثبت نشده" actionLabel="شروع مرور" actionUrl="{{ route_if('review.index') }}"/></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
{{ $logs->links() }}
@endsection
