@extends('layouts.app')

@section('title', 'فرم‌های انتخاب رشته من')
@section('page_title', 'فرم‌های انتخاب رشته من')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-collection" label="تعداد فرم‌های ذخیره‌شده" value="{{ fa_num($selections->count()) }}" color="primary"
                url="{{ route('rank.selection') }}"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-check2-circle" label="فرم‌های نهایی‌شده" value="{{ fa_num($selections->where('status', 'final')->count()) }}" color="success"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-code-square" label="مجموع کد رشته‌ها" value="{{ fa_num($selections->sum(fn ($s) => count($s->items ?? []))) }}" color="info"/>
    </div>
</div>

<div class="row g-3">
    @forelse ($selections as $selection)
        <div class="col-lg-6">
            <x-card title="{{ $selection->title ?: 'فرم بدون عنوان' }}" icon="bi-card-checklist"
                    actions="<span class='badge rounded-pill {{ $selection->status === 'final' ? 'badge-soft-success' : 'badge-soft-secondary' }}'>{{ $selection->status === 'final' ? 'نهایی' : 'پیش‌نویس' }}</span>">

                <div class="row text-center small mb-3 g-2">
                    <div class="col-3"><div class="border rounded-2 py-2"><div class="fw-bold">{{ fa_num($selection->summary['total'] ?? 0) }}</div>کد رشته</div></div>
                    <div class="col-3"><div class="border rounded-2 py-2 text-success"><div class="fw-bold">{{ fa_num($selection->summary['safe_count'] ?? 0) }}</div>امن</div></div>
                    <div class="col-3"><div class="border rounded-2 py-2 text-warning"><div class="fw-bold">{{ fa_num($selection->summary['mid_count'] ?? 0) }}</div>متوسط</div></div>
                    <div class="col-3"><div class="border rounded-2 py-2 text-danger"><div class="fw-bold">{{ fa_num($selection->summary['risky_count'] ?? 0) }}</div>ریسکی</div></div>
                </div>

                <div class="table-responsive mb-2">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>اولویت</th><th>کد رشته</th><th style="width:90px">ترتیب</th></tr></thead>
                        <tbody>
                        @foreach (($selection->items ?? []) as $item)
                            <tr>
                                <td><span class="badge badge-soft-primary">{{ fa_num($item['priority'] ?? 0) }}</span></td>
                                <td class="small fw-bold">{{ $item['title'] ?? 'کد ' . fa_num($item['major_university_id'] ?? '') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" action="{{ route('rank.selection.save') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="selection_id" value="{{ $selection->id }}">
                                            <input type="hidden" name="action" value="move">
                                            <input type="hidden" name="item_id" value="{{ $item['major_university_id'] }}">
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="btn btn-outline-secondary py-0 px-1" title="بالا"><i class="bi bi-arrow-up small"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('rank.selection.save') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="selection_id" value="{{ $selection->id }}">
                                            <input type="hidden" name="action" value="move">
                                            <input type="hidden" name="item_id" value="{{ $item['major_university_id'] }}">
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="btn btn-outline-secondary py-0 px-1" title="پایین"><i class="bi bi-arrow-down small"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span><i class="bi bi-clock"></i> آخرین تغییر: {{ fa_datetime($selection->updated_at) }}</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('rank.selections.show', $selection) }}" class="btn btn-sm btn-primary"><i class="bi bi-eye ms-1"></i> مشاهده و گزارش</a>
                        <a href="{{ route('rank.selection') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus-lg"></i> افزودن کد رشته</a>
                    </div>
                </div>
            </x-card>
        </div>
    @empty
        <div class="col-12">
            <x-card>
                <x-empty icon="bi-card-checklist" title="هنوز فرم انتخاب رشته‌ای ذخیره نکرده‌اید"
                         message="از بانک رشته‌ها، رشته‌های دلخواه را علامت بزنید و ذخیره کنید تا شانس قبولی هر کد رشته برایتان محاسبه شود."
                         actionLabel="رفتن به بانک رشته‌ها" actionUrl="{{ route('rank.selection') }}"/>
            </x-card>
        </div>
    @endforelse
</div>
@endsection
