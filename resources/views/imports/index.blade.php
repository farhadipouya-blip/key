@extends('layouts.app')

@section('title', 'ورود گروهی اطلاعات')
@section('page_title', 'ورود گروهی اطلاعات (Excel / Word / CSV)')

@section('content')
<div class="row g-3 mb-1">
    <div class="col-6 col-md-4">
        <x-stat icon="bi-database" label="کل سؤالات بانک" value="{{ fa_num($counts['questions']) }}" color="primary"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-filetype-xlsx" label="فایل‌های ورود گروهی" value="{{ fa_num($counts['imports']) }}" color="info"/>
    </div>
    <div class="col-6 col-md-4">
        <x-stat icon="bi-check2-circle" label="رکوردهای ثبت‌شده" value="{{ fa_num($counts['success']) }}" color="success"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <x-card title="بارگذاری فایل جدید" icon="bi-cloud-arrow-up">
            <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data">
                @csrf
                <x-select name="type" label="نوع اطلاعات" :options="['questions' => 'سؤالات چهارگزینه‌ای', 'historical_ranks' => 'کارنامه و رتبه‌های تاریخی']" :value="old('type', 'questions')" required/>

                <div id="subjectField" class="@if (old('type', 'questions') !== 'questions') d-none @endif">
                    <label class="form-label">درس / مبحث <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select mb-3" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @if (old('subject_id') == $subject->id) selected @endif>{{ $subject->fullTitle() }}</option>
                        @endforeach
                    </select>
                    @error('subject_id') <div class="small text-danger mb-2">{{ $message }}</div> @enderror
                </div>

                <label class="form-label">فایل داده <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control mb-1" required accept=".xlsx,.csv,.txt,.docx">
                <div class="form-text mb-3">قالب‌های مجاز: xlsx، csv، txt، docx (جدول Word) — حداکثر ۲۰ مگابایت</div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="preview" value="1" id="previewSwitch" checked>
                    <label class="form-check-label small" for="previewSwitch">ابتدا پیش‌نمایش و اعتبارسنجی سطرها (توصیه می‌شود)</label>
                </div>

                <button class="btn btn-primary w-100"><i class="bi bi-upload"></i> بارگذاری و پردازش</button>
            </form>
        </x-card>

        <x-card title="فایل‌های نمونه" icon="bi-download">
            <p class="small text-muted mb-3">برای دیدن قالب صحیح ستون‌ها، فایل نمونه را دانلود کنید:</p>
            <div class="d-flex gap-2">
                <a href="{{ route('imports.sample', 'questions') }}" class="btn btn-outline-primary btn-sm flex-grow-1">
                    <i class="bi bi-file-earmark-excel"></i> نمونه سؤالات
                </a>
                <a href="{{ route('imports.sample', 'historical') }}" class="btn btn-outline-primary btn-sm flex-grow-1">
                    <i class="bi bi-file-earmark-excel"></i> نمونه کارنامه
                </a>
            </div>
        </x-card>

        <x-card title="قالب ستون‌ها" icon="bi-table">
            <div class="small">
                <div class="fw-bold mb-1">سؤالات (به همین ترتیب):</div>
                <div class="text-muted mb-2" dir="ltr">متن سؤال | گزینه ۱ | گزینه ۲ | گزینه ۳ | گزینه ۴ | پاسخ صحیح (۱-۴) | توضیح | دشواری</div>
                <div class="fw-bold mb-1">کارنامه تاریخی:</div>
                <div class="text-muted mb-2" dir="ltr">ایمیل کاربر | عنوان آزمون | سال | رتبه | درصد کل</div>
                <div class="text-muted">سطر اول می‌تواند سرآیند باشد؛ «دشواری» با مقادیر آسان/متوسط/سخت. سطرهای نامعتبر در گزارش ذکر می‌شوند.</div>
            </div>
        </x-card>
    </div>

    <div class="col-lg-7">
        <x-card title="تاریخچه ورود گروهی" icon="bi-clock-history" bodyClass="py-0"
                >
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نوع</th>
                            <th>فایل</th>
                            <th>وضعیت</th>
                            <th>نتایج</th>
                            <th>زمان</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($imports as $import)
                        @php
                            $statusColors = ['pending' => 'secondary', 'preview' => 'info', 'completed' => 'success', 'failed' => 'danger'];
                            $statusLabels = ['pending' => 'در انتظار', 'preview' => 'پیش‌نمایش', 'completed' => 'تکمیل‌شده', 'failed' => 'ناموفق'];
                        @endphp
                        <tr>
                            <td class="small">{{ fa_num($import->id) }}</td>
                            <td class="small">
                                @if ($import->type === 'questions')
                                    <span class="badge rounded-pill badge-soft-primary">سؤالات</span>
                                @else
                                    <span class="badge rounded-pill badge-soft-info">کارنامه</span>
                                @endif
                            </td>
                            <td class="small" title="{{ $import->original_name }}">{{ Str::limit($import->original_name, 24) }}</td>
                            <td>
                                <span class="badge rounded-pill badge-soft-{{ $statusColors[$import->status] ?? 'secondary' }}">
                                    {{ $statusLabels[$import->status] ?? $import->status }}
                                </span>
                            </td>
                            <td class="small">
                                <span class="text-success">{{ fa_num($import->success_rows) }}</span> /
                                <span class="text-danger">{{ fa_num($import->error_rows) }}</span>
                                از {{ fa_num($import->total_rows) }}
                            </td>
                            <td class="small text-muted">{{ fa_datetime($import->created_at) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('imports.preview', $import) }}" class="btn btn-sm btn-outline-primary" title="پیش‌نمایش/گزارش">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if ($import->status !== 'completed')
                                    <form method="POST" action="{{ route('imports.commit', $import) }}" class="d-inline"
                                          onsubmit="return confirm('سطرهای معتبر ثبت شوند؟')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" title="ثبت نهایی"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4"><x-empty icon="bi-inbox" title="فایلی بارگذاری نشده است" message="با فرم بالا اولین ورود گروهی را انجام دهید."/></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
        {{ $imports->links() }}

        <x-card title="خروجی اکسل بانک سؤال" icon="bi-file-earmark-arrow-down">
            <form method="GET" action="{{ route('imports.export.questions') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">درس / مبحث</label>
                    <select name="subject_id" class="form-select">
                        <option value="">همه</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->fullTitle() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">دشواری</label>
                    <select name="difficulty" class="form-select">
                        <option value="">همه</option>
                        <option value="easy">آسان</option>
                        <option value="medium">متوسط</option>
                        <option value="hard">سخت</option>
                    </select>
                </div>
                <div class="col-md-4 d-grid">
                    <button class="btn btn-success"><i class="bi bi-download"></i> دانلود خروجی اکسل</button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // نمایش/مخفی کردن فیلد درس بر اساس نوع اطلاعات
    document.querySelector('select[name="type"]').addEventListener('change', function () {
        document.getElementById('subjectField').classList.toggle('d-none', this.value !== 'questions');
    });
</script>
@endpush
