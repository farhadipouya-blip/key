@extends('layouts.app')

@section('title', 'پیگیری‌ها')
@section('page_title', 'پیگیری وضعیت دانشجویان')

@section('content')
<div class="row g-3 mb-2">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-arrow-left-right" label="کل پیگیری‌ها" value="{{ fa_num($followups->count()) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-hourglass-split" label="باز" value="{{ fa_num($followups->where('status', 'open')->count()) }}" color="warning"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-circle" label="بسته‌شده" value="{{ fa_num($followups->where('status', 'done')->count()) }}" color="success"/>
    </div>
</div>

<x-card title="ثبت پیگیری جدید" icon="bi-plus-circle" class="mb-3">
    <form method="POST" action="{{ route('counselor.followups.store') }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-4">
            <x-select name="student_id" label="دانشجو" :options="$students->pluck('name', 'id')" :value="old('student_id')" required/>
        </div>
        <div class="col-md-3">
            <x-input name="next_date" label="تاریخ پیگیری بعدی (اختیاری)" type="text" placeholder="1404/09/01" :value="old('next_date')"/>
        </div>
        <div class="col-12">
            <x-textarea name="note" label="متن پیگیری" :value="old('note')" rows="2" required/>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> ثبت پیگیری</button>
        </div>
    </form>
</x-card>

<x-card title="فهرست پیگیری‌ها" icon="bi-list-check" bodyClass="py-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>دانشجو</th><th>متن پیگیری</th><th>پیگیری بعدی</th><th>وضعیت</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($followups as $followup)
                <tr>
                    <td class="small fw-bold">{{ $followup->student?->name ?? '—' }}</td>
                    <td class="small">{{ $followup->note }}</td>
                    <td class="small">{{ $followup->next_date ? fa_date($followup->next_date) : '—' }}</td>
                    <td>
                        <x-badge :status="$followup->status" :labels="['open' => 'باز', 'done' => 'بسته‌شده']"/>
                    </td>
                    <td>
                        @if ($followup->status === 'open')
                            <form method="POST" action="{{ route('counselor.followups.done', $followup) }}" class="d-inline"
                                  onsubmit="return confirm('این پیگیری بسته شود؟')">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" title="بستن پیگیری"><i class="bi bi-check2"></i> بستن</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4">
                    <x-empty icon="bi-arrow-left-right" title="پیگیری‌ای ثبت نشده است"
                             message="برای پیگیری وضعیت مطالعه دانشجویان، از فرم بالا استفاده کنید."/>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
