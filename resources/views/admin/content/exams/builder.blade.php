@extends('layouts.app')

@section('title', 'سازنده آزمون: ' . $exam->title)
@section('page_title', 'سازنده آزمون: ' . $exam->title)

@php
    $diffLabels = ['easy' => 'آسان', 'medium' => 'متوسط', 'hard' => 'دشوار'];
    $typeLabels = ['practice' => 'تمرین', 'timed' => 'زمان‌دار', 'simulator' => 'شبیه‌ساز', 'homework' => 'تکلیف'];
@endphp

@section('content')
<x-alerts/>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i> مشخصات آزمون</a>
    <span class="badge badge-soft-primary align-self-center">{{ $typeLabels[$exam->type] ?? $exam->type }}</span>
    @if ($exam->duration_minutes)
        <span class="badge badge-soft-info align-self-center">مدت: {{ fa_num($exam->duration_minutes) }} دقیقه</span>
    @endif
    @if ($exam->negative_marking)
        <span class="badge badge-soft-danger align-self-center">نمره منفی {{ fa_num($exam->negative_value) }}</span>
    @endif
    @if ($exam->is_public)
        <span class="badge badge-soft-success align-self-center">عمومی</span>
    @else
        <span class="badge badge-soft-secondary align-self-center">خصوصی</span>
    @endif
</div>

<div class="row g-3">
    {{-- بانک سؤال: انتخاب و افزودن --}}
    <div class="col-xl-6">
        <x-card title="بانک سؤال — انتخاب سؤالات" icon="bi-database">
            <form method="GET" class="row g-2 mb-2">
                <div class="col-md-3">
                    <select name="subject_id" class="form-select form-select-sm">
                        <option value="">همه دروس</option>
                        @foreach ($subjects as $id => $title)
                            <option value="{{ $id }}" @if (request('subject_id') == $id) selected @endif>{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="difficulty" class="form-select form-select-sm">
                        <option value="">سختی</option>
                        <option value="easy" @if (request('difficulty') === 'easy') selected @endif>آسان</option>
                        <option value="medium" @if (request('difficulty') === 'medium') selected @endif>متوسط</option>
                        <option value="hard" @if (request('difficulty') === 'hard') selected @endif>دشوار</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">نوع</option>
                        <option value="multi" @if (request('type') === 'multi') selected @endif>تستی</option>
                        <option value="descriptive" @if (request('type') === 'descriptive') selected @endif>تشریحی</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control form-select-sm form-control-sm" placeholder="جستجو..." value="{{ request('q') }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.exams.questions.attach', $exam) }}">
                @csrf
                <div class="bank-list border rounded-3 p-2 mb-2" style="max-height:420px; overflow-y:auto">
                    @forelse ($bank as $q)
                        <label class="d-flex align-items-start gap-2 border-bottom py-2 {{ $loop->last ? 'border-bottom-0' : '' }}">
                            <input type="checkbox" class="form-check-input mt-1" name="question_ids[]" value="{{ $q->id }}">
                            <div class="flex-grow-1 small">
                                <div>{{ \Illuminate\Support\Str::limit(strip_tags($q->text), 120) }}</div>
                                <div class="text-muted mt-1">
                                    <i class="bi bi-book"></i> {{ $q->subject?->parent?->title ? $q->subject->parent->title . ' › ' : '' }}{{ $q->subject?->title }}
                                    — {{ $diffLabels[$q->difficulty] }} — بارم {{ fa_num($q->default_marks) }}
                                    @if ($q->type === 'multi')
                                        @php $correct = $q->correctOption(); @endphp
                                        @if ($correct) <span class="badge badge-soft-success">گزینه {{ fa_num($correct->order) }}</span> @endif
                                    @else
                                        <span class="badge badge-soft-info">تشریحی</span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @empty
                        <x-empty icon="bi-database" title="سؤال جدیدی در بانک یافت نشد" message="همه سؤالات فیلتر فعلی قبلاً به آزمون اضافه شده‌اند یا بانک خالی است."/>
                    @endforelse
                </div>
                <button type="submit" class="btn btn-primary w-100" @if ($bank->isEmpty()) disabled @endif>
                    <i class="bi bi-plus-lg"></i> افزودن سؤالات انتخاب‌شده به آزمون
                </button>
            </form>
        </x-card>

        <x-card title="تولید خودکار سؤالات" icon="bi-magic">
            <form method="POST" action="{{ route('admin.exams.questions.generate', $exam) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label small">درس‌ها / مباحث (چندگزینه‌ای)</label>
                        <select name="subject_ids[]" class="form-select form-select-sm" multiple size="5" required>
                            @foreach ($subjects as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">زیرمجموعه‌ها (فصل‌ها و مباحث) به‌صورت خودکار پوشش داده می‌شوند.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">تعداد سؤال</label>
                        <input type="number" name="count" class="form-control form-control-sm" value="10" min="1" max="100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">سطح سختی</label>
                        <select name="difficulty" class="form-select form-select-sm">
                            <option value="">همه سطوح</option>
                            <option value="easy">آسان</option>
                            <option value="medium">متوسط</option>
                            <option value="hard">دشوار</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary w-100 mt-2"><i class="bi bi-magic"></i> تولید خودکار از بانک</button>
            </form>
        </x-card>
    </div>

    {{-- سؤالات آزمون --}}
    <div class="col-xl-6">
        <x-card title="سؤالات آزمون ({{ fa_num($exam->examQuestions->count()) }} سؤال — مجموع بارم {{ fa_num($exam->total_marks) }})" icon="bi-collection">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @if ($exam->status !== 'published')
                    <form method="POST" action="{{ route('admin.exams.status', $exam) }}" onsubmit="return confirm('انتشار آزمون در مرکز آزمون دانشجویان. مطمئن هستید؟')">
                        @csrf
                        <input type="hidden" name="status" value="published">
                        <button class="btn btn-sm btn-success" @if ($exam->examQuestions->isEmpty()) disabled title="حداقل یک سؤال لازم است" @endif>
                            <i class="bi bi-send"></i> انتشار آزمون
                        </button>
                    </form>
                @endif
                @if ($exam->status === 'published')
                    <form method="POST" action="{{ route('admin.exams.status', $exam) }}" onsubmit="return confirm('بستن آزمون دسترسی دانشجویان را قطع می‌کند. مطمئن هستید؟')">
                        @csrf
                        <input type="hidden" name="status" value="closed">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-lock"></i> بستن آزمون</button>
                    </form>
                @endif
                @if ($exam->status === 'closed' || $exam->status === 'published')
                    <form method="POST" action="{{ route('admin.exams.status', $exam) }}">
                        @csrf
                        <input type="hidden" name="status" value="draft">
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> بازگشت به پیش‌نویس</button>
                    </form>
                @endif
                @if ($exam->status === 'published')
                    <span class="badge badge-soft-success align-self-center">منتشرشده — در مرکز آزمون</span>
                @endif
            </div>

            <div class="border rounded-3 p-2" style="max-height:560px; overflow-y:auto">
                @forelse ($exam->examQuestions as $eq)
                    @php $q = $eq->question; @endphp
                    <div class="d-flex align-items-start gap-2 border-bottom py-2 {{ $loop->last ? 'border-bottom-0' : '' }}">
                        <span class="badge bg-secondary">{{ fa_num($eq->order) }}</span>
                        <div class="flex-grow-1 small">
                            <div>{{ \Illuminate\Support\Str::limit(strip_tags($q->text), 100) }}</div>
                            <div class="text-muted">
                                {{ $q->subject?->parent?->title ? $q->subject->parent->title . ' › ' : '' }}{{ $q->subject?->title }}
                                — {{ $diffLabels[$q->difficulty] }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.exams.questions.update', [$exam, $eq]) }}" class="d-flex align-items-center gap-1">
                            @csrf
                            @method('PUT')
                            <input type="number" name="marks" value="{{ $eq->marks }}" step="0.25" min="0.25" max="100"
                                   class="form-control form-control-sm" style="width:80px" title="بارم سؤال">
                            <button class="btn btn-sm btn-outline-success" title="ذخیره بارم"><i class="bi bi-check"></i></button>
                        </form>
                        <form method="POST" action="{{ route('admin.exams.questions.update', [$exam, $eq]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="move" value="up">
                            <button class="btn btn-sm btn-outline-secondary" title="بالا"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form method="POST" action="{{ route('admin.exams.questions.update', [$exam, $eq]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="move" value="down">
                            <button class="btn btn-sm btn-outline-secondary" title="پایین"><i class="bi bi-arrow-down"></i></button>
                        </form>
                        <form method="POST" action="{{ route('admin.exams.questions.detach', [$exam, $eq]) }}"
                              onsubmit="return confirm('حذف سؤال از آزمون؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </div>
                @empty
                    <x-empty icon="bi-collection" title="آزمون بدون سؤال است" message="از بانک سؤال انتخاب کنید یا از تولید خودکار استفاده کنید."/>
                @endforelse
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/tex-svg.js') }}"></script>
@endpush
