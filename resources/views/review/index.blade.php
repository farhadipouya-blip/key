@extends('layouts.app')

@section('title', 'مرور هوشمند')
@section('page_title', 'مرور هوشمند (فاصله‌دار)')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-hourglass-split" label="در انتظار مرور" value="{{ fa_num($due->count()) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clipboard-data" label="ماندگاری مطالب (۳۰ روز)" value="{{ fa_num($stats['retention']) }}٪" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-layers" label="کل مرورهای ثبت‌شده" value="{{ fa_num($total) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clock" label="مرورهای آینده" value="{{ fa_num($upcoming->count()) }}" color="info"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <x-card title="صف مرور امروز" icon="bi-arrow-repeat" bodyClass="py-0"
                actions="<a href='{{ route('review.session') }}' class='btn btn-sm btn-primary'>شروع مرور</a>">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>موضوع</th><th>نوع</th><th>فاصله فعلی</th><th>سررسید</th><th>مرورها</th></tr></thead>
                    <tbody>
                    @forelse ($due as $item)
                        <tr>
                            <td class="small fw-bold">
                                @if ($item->reviewable instanceof \App\Models\Question)
                                    {{ \Illuminate\Support\Str::limit($item->reviewable->text, 60) }}
                                @else
                                    {{ $item->reviewable?->title ?? '—' }}
                                @endif
                            </td>
                            <td>
                                <x-badge :status="$item->reviewable_type === 'App\Models\Question' ? 'primary' : 'info'"
                                         :labels="['primary'=>'سؤال','info'=>'خلاصه/نکته']"/>
                            </td>
                            <td class="small">{{ fa_num($item->interval_days) }} روز</td>
                            <td class="small text-danger">{{ fa_date($item->due_at) }}</td>
                            <td class="small">{{ fa_num($item->total_reviews) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4"><x-empty icon="bi-check2-all" title="صف مرور خالی است" message="سؤالات اشتباه آزمون‌ها به‌طور خودکار به مرور اضافه می‌شوند."/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="مرورهای آینده" icon="bi-calendar-event" bodyClass="py-0">
            <div style="max-height:320px; overflow-y:auto">
                @forelse ($upcoming as $item)
                    <div class="d-flex justify-content-between py-2 border-bottom small">
                        <span>{{ $item->reviewable?->title ?? \Illuminate\Support\Str::limit($item->reviewable?->text ?? '—', 30) }}</span>
                        <span class="text-muted">{{ fa_date($item->due_at) }}</span>
                    </div>
                @empty
                    <div class="p-3 text-center text-muted small">برنامه مرور آینده‌ای ثبت نشده</div>
                @endforelse
            </div>
        </x-card>

        <x-card title="راهنمای روش" icon="bi-info-circle">
            <p class="small text-muted mb-0">
                سامانه با الگوریتم علمی SM-2 فاصله مرور هر مطلب را بر اساس کیفیت یادآوری شما تنظیم می‌کند؛
                مطلبی که «آسان» پاسخ می‌شود با فاصله طولانی‌تر بازمی‌گردد و مطلب «دوباره» زودتر مرور می‌شود.
                سؤالات اشتباه و نزده هر آزمون به‌صورت خودکار به این صف اضافه می‌شوند.
            </p>
        </x-card>
    </div>
</div>
@endsection
