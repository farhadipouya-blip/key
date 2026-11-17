@extends('layouts.app')

@section('title', 'انتخاب رشته')
@section('page_title', 'بانک رشته‌ها و شانس قبولی')

@php
    use \App\Http\Controllers\RankController;
    $groups = RankController::GROUPS;
    $tuitionTypes = RankController::TUITION_TYPES;
@endphp

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-trophy" label="رتبه مبنای محاسبه شانس" value="{{ $rank ? fa_num(number_format($rank)) : '—' }}" color="primary"
                hint="{{ $latestEstimate ? 'آخرین تخمین: ' . fa_datetime($latestEstimate->created_at) : 'هنوز تخمینی ثبت نکرده‌اید' }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-building" label="دانشگاه‌های سامانه" value="{{ fa_num($universitiesCount) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-list-check" label="نتایج این فیلتر" value="{{ fa_num($rows->total()) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-collection" label="سال حدنصاب" value="{{ fa_num($year) }}" color="warning"/>
    </div>
</div>

@if (! $rank)
    <div class="alert alert-warning d-flex gap-2 align-items-center">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            هنوز تخمین رتبه‌ای ندارید؛ شانس‌ها بر پایه رتبه دستی فیلتر «رتبه مبنای محاسبه» در فرم زیر محاسبه می‌شوند.
            <a href="{{ route('rank.index') }}">محاسبه تخمین رتبه</a>
        </div>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('rank.selection') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small">جستجو</label>
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="رشته، دانشگاه یا شهر...">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">گروه آزمون</label>
                <select name="group" class="form-select form-select-sm">
                    @foreach ($groups as $key => $label)
                        <option value="{{ $key }}" @if($group === $key) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">سال حدنصاب</label>
                <select name="year" class="form-select form-select-sm">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @if((int)$year === (int)$y) selected @endif>{{ fa_num($y) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">نوع دوره</label>
                <select name="tuition_type" class="form-select form-select-sm">
                    <option value="">همه</option>
                    @foreach ($tuitionTypes as $key => $label)
                        <option value="{{ $key }}" @if($tuition === $key) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">رتبه مبنای محاسبه (دستی)</label>
                <input type="number" name="rank" min="1" value="{{ $rank }}" class="form-control form-control-sm text-center">
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search ms-1"></i> جستجو</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('rank.selection.save') }}" id="selectionForm">
    @csrf
    <x-card title="کد رشته‌ها و شانس قبولی" icon="bi-list-stars" bodyClass="py-0">
        @if ($rows->isEmpty())
            <x-empty icon="bi-search" title="نتیجه‌ای یافت نشد" message="فیلترها را تغییر دهید یا عبارت دیگری جستجو کنید."/>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px"></th>
                            <th>کد</th>
                            <th>رشته</th>
                            <th>دانشگاه</th>
                            <th>دوره</th>
                            <th>حدنصاب سال قبل</th>
                            <th style="min-width:150px">شانس قبولی</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <input class="form-check-input" type="checkbox" name="items[]" value="{{ $row->id }}"
                                       @if(in_array($row->id, $selected)) checked @endif
                                       title="انتخاب برای فرم انتخاب رشته">
                            </td>
                            <td class="small text-muted">{{ fa_num($row->id) }}</td>
                            <td class="fw-bold">{{ $row->major?->title }}</td>
                            <td>
                                <div class="small fw-bold">{{ $row->university?->name }}</div>
                                <div class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $row->university?->city }}</div>
                            </td>
                            <td>
                                <x-badge :status="$row->tuition_type" :labels="$tuitionTypes + array_fill_keys(array_keys($tuitionTypes), '—')"/>
                            </td>
                            <td class="small">
                                {{ $row->last_rank_min ? fa_num(number_format($row->last_rank_min)) : '—' }}
                                تا
                                {{ $row->last_rank_max ? fa_num(number_format($row->last_rank_max)) : '—' }}
                            </td>
                            <td>
                                @php
                                    $chance = $row->chance;
                                    $bar = $chance >= 60 ? 'bg-success' : ($chance >= 30 ? 'bg-warning text-dark' : 'bg-danger');
                                    $zone = $chance >= 60 ? 'امن' : ($chance >= 30 ? 'متوسط' : 'ریسکی');
                                @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:10px">
                                        <div class="progress-bar {{ $bar }}" style="width: {{ max(3, $chance) }}%"></div>
                                    </div>
                                    <span class="small fw-bold">{{ fa_num($chance) }}٪</span>
                                    <span class="badge badge-soft-secondary small">{{ $zone }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <div class="card mb-3 border-primary">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">عنوان فرم انتخاب</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="مثلاً فرم اصلی تجربی">
                    @error('title')<div class="small text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save ms-1"></i> ذخیره انتخاب‌ها</button>
                </div>
                <div class="col-md-4 small text-muted">
                    کد رشته‌های علامت‌خورده به‌ترتیب جدول با اولویت ذخیره می‌شوند؛ بعداً می‌توانید در صفحه «فرم‌های من» ترتیب را با دکمه‌های بالا/پایین تغییر دهید.
                </div>
            </div>
            @error('items')
                <div class="small text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>
</form>

<div class="d-flex justify-content-between align-items-center no-print">
    <a href="{{ route('rank.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-right ms-1"></i> بازگشت به تخمین</a>
    {{ $rows->links('pagination::bootstrap-5') }}
</div>
@endsection
