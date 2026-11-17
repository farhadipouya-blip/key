@extends('layouts.app')

@section('title', 'تنظیمات سامانه')
@section('page_title', 'تنظیمات سامانه')

@php
    $typeLabels = ['text' => 'متن', 'number' => 'عدد', 'bool' => 'کلید روشن/خاموش', 'json' => 'JSON'];
@endphp

@section('content')
<form method="POST" action="{{ route('admin.settings.save') }}">
    @csrf
    <div class="row g-3">
        @foreach ($groups as $group => $items)
            <div class="col-12 col-xl-6">
                <x-card title="تنظیمات «{{ $groupTitles[$group] ?? $group }}»" icon="bi-gear">
                    @foreach ($items as $item)
                        @php $field = 'settings.' . $item['key']; @endphp
                        <div class="mb-3">
                            <label class="form-label" for="fld-{{ md5($item['key']) }}">
                                {{ $item['label'] }}
                                <small class="text-muted" dir="ltr">({{ $item['key'] }})</small>
                            </label>

                            @if ($item['secret'])
                                <input type="text" id="fld-{{ md5($item['key']) }}" name="{{ $field }}"
                                       value="{{ $item['value'] ? '••••••••••' : '' }}"
                                       class="form-control" placeholder="خالی = ذخیره نشده"
                                       autocomplete="off">
                                <div class="form-text">مقدار حساس — برای حفظ مقدار فعلی دست نزنید؛ برای تغییر، مقدار جدید را وارد کنید.</div>
                            @elseif ($item['type'] === 'bool')
                                <input type="hidden" name="{{ $field }}" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="fld-{{ md5($item['key']) }}" name="{{ $field }}" value="1"
                                           @if ($item['value'] == '1') checked @endif>
                                    <label class="form-check-label small text-muted" for="fld-{{ md5($item['key']) }}">
                                        {{ $item['value'] == '1' ? 'فعال' : 'غیرفعال' }}
                                    </label>
                                </div>
                            @elseif ($item['type'] === 'number')
                                <input type="number" id="fld-{{ md5($item['key']) }}" name="{{ $field }}"
                                       value="{{ $item['value'] }}" class="form-control" step="any">
                            @elseif ($item['type'] === 'json')
                                <textarea id="fld-{{ md5($item['key']) }}" name="{{ $field }}" rows="3"
                                          class="form-control" dir="ltr">{{ $item['value'] }}</textarea>
                            @else
                                <input type="text" id="fld-{{ md5($item['key']) }}" name="{{ $field }}"
                                       value="{{ $item['value'] }}" class="form-control">
                            @endif
                        </div>
                    @endforeach
                </x-card>
            </div>
        @endforeach

        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="small text-muted">
                        <i class="bi bi-info-circle"></i>
                        کلیدهای حساس (حاوی «key/secret/password/token») فقط با وارد کردن مقدار جدید تغییر می‌کنند.
                    </span>
                    <button class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> ذخیره همه تنظیمات</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
