@extends('layouts.app')

@section('title', 'برگزاری آزمون: ' . $exam->title)
@section('page_title', 'برگزاری آزمون — ' . $exam->title)

@section('content')
<div class="row g-3">
    {{-- ناوبری سؤالات --}}
    <div class="col-lg-3 order-lg-2">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small">زمان باقی‌مانده</span>
                    @if ($remaining !== null)
                        <span id="timer" class="fw-bold fs-5" dir="ltr">--:--:--</span>
                    @else
                        <span class="badge badge-soft-success">نامحدود</span>
                    @endif
                </div>
                <div class="small text-muted mb-3">
                    پاسخ‌داده: <strong id="answeredCount">{{ fa_num($answers->count()) }}</strong> از {{ fa_num(count($examQuestions)) }}
                </div>
                <div class="d-grid gap-2">
                    <button id="finishBtn" class="btn btn-success"><i class="bi bi-check2-all"></i> پایان آزمون و صدور کارنامه</button>
                </div>
            </div>
        </div>

        <x-card title="سؤالات" icon="bi-grid-3x3" bodyClass="pb-2">
            <div class="d-flex flex-wrap gap-1" id="qNav">
                @foreach ($examQuestions as $i => $eq)
                    <a href="#q-{{ $eq->question_id }}"
                       class="btn btn-sm btn-outline-secondary q-nav-item" data-qid="{{ $eq->question_id }}"
                       style="min-width:2.4rem">
                        {{ fa_num($i + 1) }}
                    </a>
                @endforeach
            </div>
        </x-card>
    </div>

    {{-- سؤالات --}}
    <div class="col-lg-9 order-lg-1">
        <div id="questions">
            @foreach ($examQuestions as $i => $eq)
                @php
                    $question = $eq->question;
                    $answer = $answers->get($question->id);
                @endphp
                <div class="card question-card mb-3" id="q-{{ $question->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge badge-soft-primary">سؤال {{ fa_num($i + 1) }}</span>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge badge-soft-secondary">{{ fa_num($eq->marks) }} نمره</span>
                                <span class="badge badge-soft-{{ $question->difficulty === 'easy' ? 'success' : ($question->difficulty === 'hard' ? 'danger' : 'warning') }}">
                                    {{ ['easy' => 'آسان', 'medium' => 'متوسط', 'hard' => 'سخت'][$question->difficulty] ?? 'متوسط' }}
                                </span>
                                <button type="button" class="btn btn-sm btn-link text-warning p-0 flag-btn {{ ($answer?->flagged) ? 'flagged' : '' }}"
                                        data-qid="{{ $question->id }}" title="علامت‌گذاری برای بعد">
                                    <i class="bi {{ ($answer?->flagged) ? 'bi-flag-fill' : 'bi-flag' }}"></i>
                                </button>
                            </div>
                        </div>

                        <div class="q-text wysiwyg-content mb-3">{{ $question->text }}</div>
                        @if ($question->image_path)
                            <img src="{{ storage_public_url($question->image_path) }}" class="img-fluid rounded mb-3" alt="تصویر سؤال">
                        @endif

                        @if ($question->type === 'multi')
                            <div class="options">
                                @foreach ($question->options as $option)
                                    <div class="option-item {{ ($answer?->selected_option_id === $option->id) ? 'selected' : '' }}"
                                         data-selectable data-qid="{{ $question->id }}" data-option="{{ $option->id }}">
                                        <span class="badge bg-secondary">{{ ['۱', '۲', '۳', '۴'][$option->order - 1] ?? $option->order }}</span>
                                        <span class="flex-grow-1">{{ $option->text }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <textarea class="form-control" rows="5" placeholder="پاسخ تشریحی خود را بنویسید (به‌صورت خودکار ذخیره می‌شود)"
                                      data-qid="{{ $question->id }}" data-desc="1">{{ $answer?->text_answer }}</textarea>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('styles')
<script src="{{ asset('assets/js/tex-svg.js') }}"></script>
@endpush

@push('scripts')
<script>
(function () {
    const takeUrl = '{{ route('exams.take', $attempt) }}';
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let saving = false;
    let dirty = false;

    // شمارش معکوس
    @if ($remaining !== null)
        const timerEl = document.getElementById('timer');
        keyCountdown(timerEl, {{ $remaining }}, () => {
            keyToast('زمان آزمون به پایان رسید', 'warning');
            document.getElementById('finishForm').submit?.();
            window.location.href = '{{ route('exams.finish', $attempt) }}';
        });
    @endif

    // ذخیره پاسخ
    async function saveAnswer(qid, payload) {
        try {
            const res = await fetch(`{{ route('exams.take', $attempt) }}/answers/${qid}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                const ac = document.getElementById('answeredCount');
                if (ac) ac.textContent = keyFa(data.answered);
            }
        } catch (e) {
            keyToast('خطا در ذخیره پاسخ — اتصال اینترنت را بررسی کنید', 'danger');
        }
    }

    window.keyFa = function (n) {
        return String(n).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    };

    // انتخاب گزینه
    document.addEventListener('click', function (e) {
        const item = e.target.closest('.option-item[data-selectable]');
        if (!item) return;
        const qid = item.dataset.qid;
        const optionId = item.dataset.option;
        item.closest('.options').querySelectorAll('.option-item').forEach(o => o.classList.remove('selected'));
        item.classList.add('selected');
        saveAnswer(qid, { option_id: Number(optionId) });
        markNav(qid, true);
    });

    // ذخیره خودکار پاسخ تشریحی
    document.querySelectorAll('textarea[data-desc]').forEach(function (ta) {
        let t;
        ta.addEventListener('input', function () {
            dirty = true;
            clearTimeout(t);
            t = setTimeout(() => saveAnswer(ta.dataset.qid, { text_answer: ta.value }), 1200);
        });
    });

    // علامت‌گذاری
    document.querySelectorAll('.flag-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const on = !btn.classList.contains('flagged');
            btn.classList.toggle('flagged', on);
            btn.querySelector('i').className = on ? 'bi bi-flag-fill' : 'bi bi-flag';
            saveAnswer(btn.dataset.qid, { flagged: on });
        });
    });

    function markNav(qid, answered) {
        const nav = document.querySelector(`.q-nav-item[data-qid="${qid}"]`);
        if (nav && answered) nav.classList.replace('btn-outline-secondary', 'btn-success');
    }

    @foreach ($answers as $answer)
        @if ($answer->selected_option_id)
            markNav('{{ $answer->question_id }}', true);
        @endif
    @endforeach

    // پایان آزمون
    document.getElementById('finishBtn').addEventListener('click', function () {
        if (confirm('آیا مطمئن هستید که آزمون را نهایی کنید؟')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('exams.finish', $attempt) }}';
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrf;
            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
})();
</script>
@endpush
