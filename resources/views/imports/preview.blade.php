@extends('layouts.app')

@section('title', 'پیش‌نمایش ورود گروهی')
@section('page_title', 'پیش‌نمایش و اعتبارسنجی — ' . ($import->original_name ?: ('فایل #' . $import->id)))

@section('content')
<div class="row g-3 mb-1">
    <div class="col-6 col-md-3">
        <x-stat icon="bi-list-ol" label="کل سطرها" value="{{ fa_num($parsed['total']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-check2-circle" label="سطرهای معتبر" value="{{ fa_num(count($parsed['valid'])) }}" color="success"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-x-circle" label="سطرهای نامعتبر" value="{{ fa_num(count($parsed['invalid'])) }}" color="{{ count($parsed['invalid']) > 0 ? 'danger' : 'secondary' }}"/>
    </div>
    <div class="col-6 col-md-3">
        <x-stat icon="bi-tag" label="نوع ورود" :value="$import->type === 'questions' ? 'سؤالات' : 'کارنامه'" color="primary"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <x-card title="نتیجه اعتبارسنجی سطرها" icon="bi-list-check" bodyClass="py-0">
            @if ($subject)
                <div class="px-3 py-2 border-bottom small">
                    <i class="bi bi-diagram-3 text-primary"></i>
                    مقصد ثبت سؤالات: <strong>{{ $subject->fullTitle() }}</strong>
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        @if ($import->type === 'questions')
                            <tr><th>#</th><th>متن سؤال</th><th>گزینه‌ها</th><th>پاسخ صحیح</th><th>دشواری</th><th>وضعیت</th><th>خطاها</th></tr>
                        @else
                            <tr><th>#</th><th>ایمیل کاربر</th><th>آزمون</th><th>سال</th><th>رتبه</th><th>درصد</th><th>وضعیت</th><th>خطاها</th></tr>
                        @endif
                    </thead>
                    <tbody>
                    @if ($import->type === 'questions')
                        @forelse ($parsed['valid'] as $item)
                            <tr class="table-success">
                                <td class="small">{{ fa_num($item['row']) }}</td>
                                <td class="small" style="max-width:280px">{{ Str::limit($item['text'], 120) }}</td>
                                <td class="small">
                                    <ol class="mb-0 ps-3" style="font-size:.75rem">
                                        @foreach ($item['options'] as $i => $option)
                                            <li class="{{ ($i + 1) === $item['correct_index'] ? 'fw-bold text-success' : '' }}">{{ Str::limit($option, 40) }}</li>
                                        @endforeach
                                    </ol>
                                </td>
                                <td class="small fw-bold text-success">{{ fa_num($item['correct_index']) }}</td>
                                <td class="small">
                                    @php $diffLabels = ['easy' => 'آسان', 'medium' => 'متوسط', 'hard' => 'سخت']; @endphp
                                    <x-badge :status="$item['difficulty']" :labels="$diffLabels"/>
                                </td>
                                <td><span class="badge rounded-pill badge-soft-success">معتبر</span></td>
                                <td class="text-muted small">—</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3 small">سطر معتبری یافت نشد</td></tr>
                        @endforelse
                        @foreach ($parsed['invalid'] as $item)
                            <tr class="table-danger">
                                <td class="small">{{ fa_num($item['row']) }}</td>
                                <td class="small" style="max-width:280px">{{ Str::limit($item['text'] ?: '—', 80) }}</td>
                                <td class="small text-muted" colspan="5">
                                    <ul class="mb-0 ps-3" style="font-size:.75rem">
                                        @foreach ($item['errors'] as $error)
                                            <li class="text-danger">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        @forelse ($parsed['valid'] as $item)
                            <tr class="table-success">
                                <td class="small">{{ fa_num($item['row']) }}</td>
                                <td class="small" dir="ltr">{{ $item['email'] }}</td>
                                <td class="small">{{ $item['exam_name'] }}</td>
                                <td class="small">{{ fa_num($item['year']) }}</td>
                                <td class="small">{{ $item['rank'] ? fa_num($item['rank']) : '—' }}</td>
                                <td class="small">{{ $item['total_percent'] !== null ? fa_num($item['total_percent']) . '٪' : '—' }}</td>
                                <td><span class="badge rounded-pill badge-soft-success">معتبر</span></td>
                                <td class="text-muted small">—</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3 small">سطر معتبری یافت نشد</td></tr>
                        @endforelse
                        @foreach ($parsed['invalid'] as $item)
                            <tr class="table-danger">
                                <td class="small">{{ fa_num($item['row']) }}</td>
                                <td class="small" dir="ltr">{{ $item['text'] }}</td>
                                <td class="small text-muted" colspan="6">
                                    <ul class="mb-0 ps-3" style="font-size:.75rem">
                                        @foreach ($item['errors'] as $error)
                                            <li class="text-danger">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted">
                    @if (count($parsed['valid']) > 0)
                        با تأیید نهایی، <strong>{{ fa_num(count($parsed['valid'])) }}</strong> سطر معتبر در پایگاه داده ثبت می‌شود؛ سطرهای نامعتبر نادیده گرفته می‌شوند.
                    @else
                        هیچ سطر معتبری برای ثبت وجود ندارد.
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('imports.index') }}" class="btn btn-outline-secondary">بازگشت</a>
                    @if (count($parsed['valid']) > 0 && $import->status !== 'completed')
                        <form method="POST" action="{{ route('imports.commit', $import) }}"
                              onsubmit="return confirm('ثبت نهایی انجام شود؟ این عمل برای هر فایل یک‌بار است.')">
                            @csrf
                            <button class="btn btn-success"><i class="bi bi-check-lg"></i> تأیید و ثبت گروهی</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
