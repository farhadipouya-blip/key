@extends('layouts.app')

@section('title', 'برنامه مطالعاتی')
@section('page_title', 'برنامه مطالعاتی')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-calendar2-week" label="برنامه فعال" value="{{ $plan ? $plan->title : 'ندارید' }}" color="primary" hint="{{ $plan ? fa_date($plan->start_date).' تا '.fa_date($plan->end_date) : '' }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clock-history" label="مطالعه این هفته (جلسات واقعی)" value="{{ duration_human($weekSessionsMinutes) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-list-check" label="برنامه‌ریزی‌شده این هفته" value="{{ duration_human($weekPlanned) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-square" label="انجام‌شده آیتم‌ها" value="{{ duration_human($weekCompleted) }}" color="warning"/>
    </div>
</div>

@if (! $plan)
    <div class="row g-3">
        <div class="col-lg-6">
            <x-card title="ساخت برنامه مطالعاتی جدید" icon="bi-calendar-plus">
                <form method="POST" action="{{ route('plan.store') }}">
                    @csrf
                    <x-input name="title" label="عنوان برنامه" required placeholder="مثل: برنامه جمع‌بندی نوبت دوم"/>
                    <x-textarea name="description" label="توضیحات" rows="2" placeholder="توضیح اختیاری"/>
                    <div class="row">
                        <div class="col-md-6">
                            <x-input name="start_date" label="تاریخ شروع" type="date" required value="{{ now()->toDateString() }}"/>
                        </div>
                        <div class="col-md-6">
                            <x-input name="end_date" label="تاریخ پایان" type="date" required value="{{ now()->addMonth()->toDateString() }}"/>
                        </div>
                    </div>
                    <x-input name="weekly_target_minutes" label="هدف مطالعه هفتگی (دقیقه)" type="number" required value="600" help="مثلاً ۶۰۰ یعنی ۱۰ ساعت در هفته"/>
                    <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> ساخت برنامه</button>
                </form>
            </x-card>
        </div>
        <div class="col-lg-6">
            <x-card title="راهنما" icon="bi-info-circle">
                <p class="small text-muted">
                    با ساخت برنامه مطالعاتی می‌توانید برای هر روز آیتم‌های درسی تعریف کنید، جلسات مطالعه واقعی را ثبت کنید
                    و در گزارش استمرار، عملکرد واقعی خود را با برنامه مقایسه کنید.
                    در صورت عقب‌افتادگی از برنامه، دکمه «بازطراحی هوشمند» آیتم‌های missed را به روزهای بعد منتقل می‌کند.
                </p>
            </x-card>
        </div>
    </div>
@else
    <div class="row g-3">
        {{-- ستون اصلی --}}
        <div class="col-lg-8">
            <x-card title="آیتم‌های امروز" icon="bi-sun" bodyClass="py-0"
                    actions="<a href='{{ route('plan.report') }}' class='btn btn-sm btn-outline-primary'>گزارش استمرار</a>">
                @forelse ($todayItems as $item)
                    <div class="d-flex align-items-center justify-content-between py-3 border-bottom px-1">
                        <div>
                            <div class="fw-bold small">{{ $item->topic ?? 'مطالعه عمومی' }}</div>
                            <div class="small text-muted">
                                {{ fa_num($item->completed_minutes) }} / {{ fa_num($item->target_minutes) }} دقیقه
                                @if ($item->start_time) — از {{ fa_num(substr($item->start_time, 0, 5)) }} @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('plan.session.store') }}" class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="hidden" name="plan_item_id" value="{{ $item->id }}">
                            <input type="hidden" name="date" value="{{ now()->toDateString() }}">
                            <input type="hidden" name="subject_id" value="{{ $item->subject_id }}">
                            <input type="number" name="minutes" class="form-control form-control-sm" style="width:90px" placeholder="دقیقه" min="1" max="720" required>
                            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> ثبت جلسه</button>
                        </form>
                    </div>
                @empty
                    <div class="py-4"><x-empty icon="bi-cup-hot" title="امروز آیتمی در برنامه ندارید" message="از فرم «افزودن آیتم» یک مبحث برای امروز انتخاب کنید."/></div>
                @endforelse
            </x-card>

            <x-card title="آیتم‌های این هفته" icon="bi-calendar-week" bodyClass="py-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>تاریخ</th><th>موضوع</th><th>هدف</th><th>انجام</th><th>وضعیت</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($weekItems as $item)
                            <tr>
                                <td class="small">{{ fa_date($item->date) }}</td>
                                <td class="small fw-bold">{{ $item->topic ?? 'مطالعه عمومی' }}</td>
                                <td class="small">{{ fa_num($item->target_minutes) }} دقیقه</td>
                                <td class="small">{{ fa_num($item->completed_minutes) }} دقیقه</td>
                                <td>
                                    <x-badge :status="$item->status" :labels="['pending'=>'در انتظار','partial'=>'نیمه‌تمام','done'=>'انجام شد','missed'=>'عقب‌افتاده']"/>
                                </td>
                                <td>
                                    @if ($item->status !== 'done')
                                        <form method="POST" action="{{ route('plan.item.update', [$plan, $item]) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="done">
                                            <input type="hidden" name="completed_minutes" value="{{ $item->target_minutes }}">
                                            <button class="btn btn-sm btn-outline-success" title="علامت‌گذاری انجام‌شده"><i class="bi bi-check2-all"></i></button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('plan.item.destroy', [$plan, $item]) }}" class="d-inline"
                                          onsubmit="return confirm('این آیتم حذف شود؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-4"><x-empty icon="bi-calendar-x" title="آیتمی برای این هفته نیست"/></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="افزودن آیتم مطالعاتی" icon="bi-plus-square">
                <form method="POST" action="{{ route('plan.item.store', $plan) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-3">
                            <x-input name="date" label="تاریخ" type="date" required value="{{ now()->toDateString() }}"/>
                        </div>
                        <div class="col-md-4">
                            <x-select name="subject_id" label="مبحث" :options="$subjects->mapWithKeys(fn($s) => [$s->id => $s->fullTitle()])" value="{{ old('subject_id') }}"/>
                        </div>
                        <div class="col-md-3">
                            <x-input name="topic" label="عنوان آزاد (اختیاری)" placeholder="مثلاً مرور تست‌های فیزیک"/>
                        </div>
                        <div class="col-md-2">
                            <x-input name="target_minutes" label="زمان هدف (دقیقه)" type="number" required value="60"/>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> افزودن به برنامه</button>
                </form>
            </x-card>
        </div>

        {{-- ستون فرعی --}}
        <div class="col-lg-4">
            <x-card title="ثبت جلسه مطالعه واقعی" icon="bi-stopwatch">
                <form method="POST" action="{{ route('plan.session.store') }}">
                    @csrf
                    <x-select name="plan_item_id" label="آیتم برنامه (اختیاری)" :options="$weekItems->mapWithKeys(fn($i) => [$i->id => fa_date($i->date).' — '.\Illuminate\Support\Str::limit($i->topic ?? 'مطالعه عمومی', 30)])"/>
                    <x-select name="subject_id" label="درس/مبحث" :options="$subjects->mapWithKeys(fn($s) => [$s->id => $s->fullTitle()])"/>
                    <x-input name="date" label="تاریخ" type="date" required value="{{ now()->toDateString() }}"/>
                    <x-input name="minutes" label="مدت (دقیقه)" type="number" required value="45"/>
                    <x-textarea name="note" label="یادداشت" rows="2"/>
                    <button class="btn btn-success w-100"><i class="bi bi-play-fill"></i> ثبت جلسه</button>
                </form>
            </x-card>

            <x-card title="ثبت هدف" icon="bi-bullseye">
                <form method="POST" action="{{ route('plan.goal.store') }}">
                    @csrf
                    <x-select name="period" label="بازه هدف" :options="['daily'=>'روزانه','weekly'=>'هفتگی','monthly'=>'ماهانه']"/>
                    <x-input name="target_minutes" label="هدف دقیقه مطالعه" type="number" value="120"/>
                    <x-input name="target_questions" label="هدف تعداد تست" type="number" placeholder="اختیاری"/>
                    <div class="row">
                        <div class="col-6"><x-input name="start_date" label="از" type="date" required value="{{ now()->toDateString() }}"/></div>
                        <div class="col-6"><x-input name="end_date" label="تا" type="date" required value="{{ now()->addDays(6)->toDateString() }}"/></div>
                    </div>
                    <button class="btn btn-outline-primary w-100"><i class="bi bi-flag"></i> ثبت هدف</button>
                </form>

                @if ($goals->isNotEmpty())
                    <hr class="my-3">
                    <div class="small fw-bold mb-2">اهداف من</div>
                    @foreach ($goals as $goal)
                        <div class="d-flex justify-content-between border-bottom py-1 small">
                            <span>{{ ['daily'=>'روزانه','weekly'=>'هفتگی','monthly'=>'ماهانه'][$goal->period] ?? $goal->period }}</span>
                            <span class="text-muted">
                                {{ fa_date($goal->start_date) }} تا {{ fa_date($goal->end_date) }}
                                — {{ $goal->target_minutes ? duration_human($goal->target_minutes) : '' }}
                                {{ $goal->target_questions ? fa_num($goal->target_questions).' تست' : '' }}
                            </span>
                        </div>
                    @endforeach
                @endif
            </x-card>

            <x-card title="بازطراحی هوشمند" icon="bi-magic">
                <p class="small text-muted mb-2">
                    آیتم‌های عقب‌افتاده (missed) به روزهای خالی بعد منتقل می‌شوند و بار هفتگی باقی‌مانده بر اساس
                    هدف {{ duration_human($plan->weekly_target_minutes) }} در هفته بازتوزیع می‌شود.
                </p>
                <form method="POST" action="{{ route('plan.replan', $plan) }}" onsubmit="return confirm('بازطراحی هوشمند اجرا شود؟')">
                    @csrf
                    <button class="btn btn-warning w-100"><i class="bi bi-arrow-repeat"></i> اجرای بازطراحی</button>
                </form>
            </x-card>

            <x-card title="آخرین جلسات مطالعه" icon="bi-clock" bodyClass="py-0">
                <div style="max-height:260px; overflow-y:auto">
                    @forelse ($sessions as $session)
                        <div class="d-flex justify-content-between py-2 border-bottom px-1 small">
                            <span>{{ $session->subject?->fullTitle() ?? ($session->note ?: 'مطالعه') }}</span>
                            <span class="text-muted">{{ fa_num($session->minutes) }} د — {{ fa_date($session->date) }}</span>
                        </div>
                    @empty
                        <div class="p-3 text-center text-muted small">هنوز جلسه‌ای ثبت نشده</div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
@endif
@endsection
