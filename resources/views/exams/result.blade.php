@extends('layouts.app')

@section('title', 'کارنامه آزمون')
@section('page_title', 'کارنامه — ' . $attempt->exam->title)

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <x-stat icon="bi-graph-up" label="درصد نهایی" value="{{ fa_num($attempt->percent) }}٪" color="primary"/>
    </div>
    <div class="col-md-3">
        <x-stat icon="bi-check2-circle" label="پاسخ صحیح" value="{{ fa_num($attempt->correct_count) }}" color="success"/>
    </div>
    <div class="col-md-3">
        <x-stat icon="bi-x-circle" label="پاسخ غلط" value="{{ fa_num($attempt->wrong_count) }}" color="danger"/>
    </div>
    <div class="col-md-3">
        <x-stat icon="bi-dash-circle" label="نزده" value="{{ fa_num($attempt->blank_count) }}" color="secondary"/>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <x-card title="خلاصه کارنامه" icon="bi-clipboard-data">
            <ul class="list-unstyled small mb-0">
                <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">آزمون</span><span class="fw-bold">{{ $attempt->exam->title }}</span></li>
                <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">نمره</span><span>{{ fa_num($attempt->score) }} از {{ fa_num($attempt->total_score) }}</span></li>
                <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">زمان مصرف‌شده</span><span>{{ duration_human(intdiv($attempt->time_spent_seconds ?? 0, 60)) }}</span></li>
                <li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">رتبه در آزمون</span><span>{{ $rankInExam ? fa_num($rankInExam) . ' از ' . fa_num($participantsCount) : '—' }}</span></li>
                <li class="d-flex justify-content-between py-2"><span class="text-muted">تاریخ</span><span>{{ fa_datetime($attempt->finished_at) }}</span></li>
            </ul>
            <div class="alert alert-success small mt-3 mb-0">
                سؤالات غلط و نزده این آزمون به‌صورت خودکار به صف مرور هوشمند اضافه شدند.
            </div>
        </x-card>

        <x-card title="تحلیل بر اساس مبحث" icon="bi-bar-chart" bodyClass="pb-3">
            @forelse ($bySubject as $subject)
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $subject['title'] }}</span>
                        <span class="text-muted">{{ fa_num(percent($subject['correct'], $subject['total'])) }}٪</span>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar bg-primary" style="width: {{ percent($subject['correct'], $subject['total']) }}%"></div>
                    </div>
                </div>
            @empty
                <div class="text-muted small">داده‌ای موجود نیست</div>
            @endforelse
        </x-card>
    </div>

    <div class="col-lg-8">
        <x-card title="تحلیل تفصیلی پاسخ‌ها" icon="bi-list-columns" bodyClass="py-2">
            <div id="questions">
                @foreach ($examQuestions as $i => $eq)
                    @php
                        $question = $eq->question;
                        $answer = $answers->get($question->id);
                        $correctOption = $question->options->firstWhere('is_correct', true);
                        $answerState = (! $answer || $answer->is_correct === null) ? 'blank' : ($answer->is_correct ? 'correct' : 'wrong');
                    @endphp
                    <div class="question-card border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge badge-soft-primary">سؤال {{ fa_num($i + 1) }}</span>
                            <x-badge :status="match($answerState){'correct'=>'success','wrong'=>'danger',default=>'secondary'}"
                                     :labels="['correct'=>'صحیح','wrong'=>'غلط','secondary'=>'نزده']"/>
                        </div>
                        <div class="q-text wysiwyg-content mb-2">{{ $question->text }}</div>

                        <div class="options small">
                            @foreach ($question->options as $option)
                                @php
                                    $state = $option->is_correct ? 'correct' : (($answer?->selected_option_id === $option->id) ? 'wrong' : '');
                                @endphp
                                <div class="option-item {{ $state }}" style="cursor:default">
                                    <span class="badge bg-secondary">{{ ['۱', '۲', '۳', '۴'][$option->order - 1] ?? $option->order }}</span>
                                    <span class="flex-grow-1">{{ $option->text }}</span>
                                    @if ($state === 'correct')
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @elseif ($state === 'wrong')
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    @endif
                                    @php
                                        $picked = $optionStats->firstWhere('selected_option_id', $option->id) ?? $optionStats->first(fn ($s) => $s->question_id === $question->id && $s->selected_option_id === $option->id);
                                    @endphp
                                    @if ($picked)
                                        <span class="badge badge-soft-info ms-2">{{ fa_num(percent($picked->picked, $attempt->exam->attempts()->where('status', 'finished')->count() ?: 1)) }}٪ انتخاب</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($question->type === 'descriptive' && $answer?->text_answer)
                            <div class="mt-2 small"><strong>پاسخ شما:</strong> {{ $answer->text_answer }}</div>
                        @endif

                        @if ($question->explanation)
                            <div class="alert alert-light border small mt-2 mb-0">
                                <strong><i class="bi bi-lightbulb text-warning"></i> توضیح:</strong>
                                {{ $question->explanation }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
</div>

<div class="d-flex gap-2 no-print">
    <a href="{{ route('exams.hub') }}" class="btn btn-outline-secondary">بازگشت به مرکز آزمون</a>
    <a href="{{ route_if('review.index') }}" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> مرور سؤالات این آزمون</a>
    <button onclick="window.print()" class="btn btn-outline-primary">چاپ کارنامه</button>
</div>
@endsection

@push('styles')
<script src="{{ asset('assets/js/tex-svg.js') }}"></script>
@endpush
