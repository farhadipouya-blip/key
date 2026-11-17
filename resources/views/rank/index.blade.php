@extends('layouts.app')

@section('title', 'تخمین رتبه')
@section('page_title', 'تخمین رتبه و انتخاب رشته')

@section('content')
@php
    $groups = \App\Http\Controllers\RankController::GROUPS;
@endphp

<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-trophy" label="رتبه تخمینی فعلی" value="{{ $latest?->estimated_rank ? fa_num(number_format($latest->estimated_rank)) : '—' }}" color="primary" hint="کشوری"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-percent" label="صدک (درصد نسبت به سایرین)" value="{{ $latest?->percentile !== null ? fa_num($latest->percentile) . '٪' : '—' }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-clock-history" label="تعداد تخمین‌های من" value="{{ fa_num($estimates->count()) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-archive" label="نتایج تاریخی ثبت‌شده" value="{{ fa_num($historical->count()) }}" color="warning"/>
    </div>
</div>

@if ($result)
    <div class="card mb-3 border-primary shadow-sm">
        <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-trophy text-primary ms-1"></i> نتیجه تخمین رتبه</h6>
            <span class="small text-muted">{{ fa_datetime($result->created_at) }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 text-center mb-3">
                <div class="col-4">
                    <div class="p-3 border rounded-3">
                        <div class="fw-bold fs-4 text-primary">{{ fa_num(number_format($result->estimated_rank)) }}</div>
                        <div class="small text-muted">رتبه کشوری تخمینی</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 border rounded-3">
                        <div class="fw-bold fs-4 text-success">{{ fa_num($result->percentile) }}٪</div>
                        <div class="small text-muted">صدک</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 border rounded-3">
                        <div class="fw-bold fs-4 text-info">
                            @php $composite = collect($result->input ?? [])->avg('percent'); @endphp
                            {{ fa_num($composite) }}٪
                        </div>
                        <div class="small text-muted">میانگین درصدهای واردشده</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-3">
                    <thead class="table-light"><tr><th>سناریو</th><th>رتبه</th><th>توضیح</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><x-badge status="success" :labels="['success' => 'خوش‌بینانه']"/></td>
                            <td class="fw-bold">{{ fa_num(number_format($result->scenario['best'] ?? 0)) }}</td>
                            <td class="small text-muted">اگر عملکرد روز آزمون عالی باشد</td>
                        </tr>
                        <tr>
                            <td><x-badge status="primary" :labels="['primary' => 'واقع‌بینانه']"/></td>
                            <td class="fw-bold">{{ fa_num(number_format($result->scenario['expected'] ?? 0)) }}</td>
                            <td class="small text-muted">محتمل‌ترین نتیجه بر اساس درصدهای فعلی</td>
                        </tr>
                        <tr>
                            <td><x-badge status="danger" :labels="['danger' => 'بدبینانه']"/></td>
                            <td class="fw-bold">{{ fa_num(number_format($result->scenario['worst'] ?? 0)) }}</td>
                            <td class="small text-muted">در صورت افت عملکرد در روز آزمون</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('rank.selection') }}" class="btn btn-primary"><i class="bi bi-list-stars ms-1"></i> انتخاب رشته بر اساس این رتبه</a>
                <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary"><i class="bi bi-graph-up ms-1"></i> تحلیل عملکرد</a>
            </div>
        </div>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <x-card title="فرم تخمین رتبه" icon="bi-calculator"
                actions="<span class='badge badge-soft-info'>درصد هر درس را از ۰ تا ۱۰۰ وارد کنید</span>">
            <form method="POST" action="{{ route('rank.estimate') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <x-select name="group" label="گروه آزمون" :options="$groups" :value="old('group', auth()->user()->field_group ?: 'experimental')" required/>
                    </div>
                </div>

                @if ($subjects->isEmpty())
                    <x-empty icon="bi-book" title="درسی تعریف نشده است" message="برای تخمین رتبه ابتدا باید درس‌ها در سامانه ثبت شوند."/>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr><th style="width:40%">درس</th><th>درصد</th><th>نوع</th></tr>
                            </thead>
                            <tbody>
                            @foreach ($subjects as $subject)
                                @php
                                    $oldRow = old('scores.' . $subject->id, []);
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $subject->title }}</td>
                                    <td style="max-width:130px">
                                        <input type="number" min="0" max="100" step="0.1"
                                               name="scores[{{ $subject->id }}][percent]"
                                               value="{{ $oldRow['percent'] ?? '' }}"
                                               class="form-control form-control-sm text-center" placeholder="مثلاً ۷۰">
                                    </td>
                                    <td>
                                        <select name="scores[{{ $subject->id }}][special]" class="form-select form-select-sm">
                                            <option value="1" @if((string)($oldRow['special'] ?? '1') === '1') selected @endif>اختصاصی</option>
                                            <option value="0" @if((string)($oldRow['special'] ?? '1') === '0') selected @endif>عمومی</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @error('scores')
                    <div class="small text-danger mb-2">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn btn-primary"><i class="bi bi-magic ms-1"></i> محاسبه و ذخیره تخمین</button>
            </form>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="نتایج تاریخی من (کارنامه)" icon="bi-archive" bodyClass="py-0"
                actions="<button class='btn btn-sm btn-outline-primary' type='button' data-bs-toggle='collapse' data-bs-target='#histForm'><i class='bi bi-plus-lg'></i> افزودن</button>">
            <div class="collapse {{ $errors->hasAny(['year', 'exam_name', 'total_percent', 'rank']) ? 'show' : '' }}" id="histForm">
                <div class="p-3 border-bottom bg-body-tertiary">
                    <form method="POST" action="{{ route('rank.historical.store') }}" class="row g-2">
                        @csrf
                        <div class="col-6 col-md-3">
                            <label class="form-label small">سال <span class="text-danger">*</span></label>
                            <input type="number" name="year" min="1380" max="1450" value="{{ old('year') }}" class="form-control form-control-sm" required>
                            @error('year')<div class="small text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small">نام آزمون</label>
                            <input type="text" name="exam_name" value="{{ old('exam_name') }}" class="form-control form-control-sm" placeholder="کنکور سراسری">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">درصد</label>
                            <input type="number" name="total_percent" min="0" max="100" step="0.01" value="{{ old('total_percent') }}" class="form-control form-control-sm text-center">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">رتبه</label>
                            <input type="number" name="rank" min="1" value="{{ old('rank') }}" class="form-control form-control-sm text-center">
                            @error('rank')<div class="small text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check2 ms-1"></i> ذخیره نتیجه</button>
                            <span class="small text-muted">درصد یا رتبه (حداقل یکی) — برای کالیبراسیون تخمین‌ها استفاده می‌شود.</span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>سال</th><th>آزمون</th><th>درصد</th><th>رتبه</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($historical as $item)
                        <tr>
                            <td>{{ fa_num($item->year) }}</td>
                            <td class="small">{{ $item->exam_name }}</td>
                            <td>{{ $item->total_percent !== null ? fa_num($item->total_percent) . '٪' : '—' }}</td>
                            <td class="fw-bold">{{ $item->rank ? fa_num(number_format($item->rank)) : '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('rank.historical.destroy', $item) }}"
                                      onsubmit="return confirm('این نتیجه حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="حذف"><i class="bi bi-trash3"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4">
                            <x-empty icon="bi-archive" title="نتیجه تاریخی ثبت نشده"
                                     message="اگر قبلاً کنکور یا آزمون آزمایشی داده‌اید، از دکمه «افزودن» بالای جدول نتیجه را ثبت کنید تا تخمین رتبه دقیق‌تر شود."/>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="تاریخچه تخمین‌های من" icon="bi-clock-history" bodyClass="py-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>تاریخ</th><th>رتبه</th><th>صدک</th></tr></thead>
                    <tbody>
                    @forelse ($estimates as $estimate)
                        <tr>
                            <td class="small">{{ fa_datetime($estimate->created_at) }}</td>
                            <td class="fw-bold">{{ fa_num(number_format($estimate->estimated_rank)) }}</td>
                            <td>{{ fa_num($estimate->percentile) }}٪</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4">
                            <x-empty icon="bi-calculator" title="هنوز تخمینی محاسبه نکرده‌اید"
                                     message="درصدهای دروس را در فرم بالا وارد کنید و تخمین بگیرید."/>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection
