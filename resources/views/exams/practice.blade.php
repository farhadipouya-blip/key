@extends('layouts.app')

@section('title', 'بانک تست — تمرین')
@section('page_title', 'بانک تست و تمرین سؤالات')

@section('content')
<x-card title="فیلتر سؤالات" icon="bi-funnel">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <x-select name="subject_id" label="درس/مبحث" :options="\App\Models\Subject::query()->whereNull('parent_id')->pluck('title', 'id')->all()" :value="request('subject_id')" placeholder="همه دروس"/>
        </div>
        <div class="col-md-3">
            <x-select name="difficulty" label="سختی" :options="['easy'=>'آسان','medium'=>'متوسط','hard'=>'سخت']" :value="request('difficulty')"/>
        </div>
        <div class="col-md-3">
            <x-select name="type" label="نوع" :options="['multi'=>'تستی','descriptive'=>'تشریحی']" :value="request('type')"/>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">اعمال</button>
        </div>
    </form>
</x-card>

<div class="d-flex justify-content-between align-items-center mb-2">
    <span class="text-muted small">{{ fa_num($questions->total()) }} سؤال یافت شد</span>
</div>

@foreach ($questions as $question)
    <x-card bodyClass="pb-2">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge badge-soft-primary">{{ $question->subject?->fullTitle() }}</span>
                <span class="badge badge-soft-{{ $question->difficulty === 'easy' ? 'success' : ($question->difficulty === 'hard' ? 'danger' : 'warning') }}">
                    {{ ['easy'=>'آسان','medium'=>'متوسط','hard'=>'سخت'][$question->difficulty] }}
                </span>
                <span class="badge badge-soft-secondary">{{ $question->type === 'multi' ? 'تستی' : 'تشریحی' }}</span>
                @if ($question->source)
                    <span class="badge badge-soft-info">{{ $question->source }}</span>
                @endif
            </div>
        </div>

        <div class="q-text wysiwyg-content mb-2">{{ $question->text }}</div>

        @if ($question->type === 'multi')
            <div class="options small mb-2">
                @foreach ($question->options as $option)
                    <div class="option-item practice-option" data-qid="{{ $question->id }}"
                         data-correct="{{ $option->is_correct ? '1' : '0' }}">
                        <span class="badge bg-secondary">{{ ['۱','۲','۳','۴'][$option->order - 1] ?? $option->order }}</span>
                        <span class="flex-grow-1">{{ $option->text }}</span>
                    </div>
                @endforeach
            </div>
            <div class="alert alert-light border small practice-explain d-none mb-1" id="explain-{{ $question->id }}">
                @if ($question->explanation)
                    <strong><i class="bi bi-lightbulb text-warning"></i> توضیح:</strong> {{ $question->explanation }}
                @endif
            </div>
        @else
            <div class="alert alert-light border small mb-2">
                <strong>پاسخ تشریحی:</strong> {{ $question->answer_text ?: '—' }}
            </div>
        @endif

        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('review.add') }}">
                @csrf
                <input type="hidden" name="type" value="question">
                <input type="hidden" name="id" value="{{ $question->id }}">
                <button class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-repeat"></i> افزودن به مرور</button>
            </form>
        </div>
    </x-card>
@endforeach

{{ $questions->links() }}
@endsection

@push('scripts')
<script>
document.querySelectorAll('.practice-option').forEach(function (el) {
    el.addEventListener('click', function () {
        const card = el.closest('.card');
        card.querySelectorAll('.practice-option').forEach(function (o) {
            o.classList.remove('correct', 'wrong');
        });
        if (el.dataset.correct === '1') {
            el.classList.add('correct');
        } else {
            el.classList.add('wrong');
            card.querySelector('.practice-option[data-correct="1"]')?.classList.add('correct');
        }
        const explain = document.getElementById('explain-' + el.dataset.qid);
        explain?.classList.remove('d-none');
    });
});
</script>
@endpush
