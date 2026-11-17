@extends('layouts.app')

@section('title', 'جلسه مرور')
@section('page_title', 'جلسه مرور هوشمند')

@section('content')
@php
    $item = $review->reviewable;
@endphp

<div class="row justify-content-center">
    <div class="col-lg-8">
        <x-card :title="$review->reviewable_type === 'App\Models\Question' ? 'مرور سؤال' : 'مرور خلاصه/نکته'" icon="bi-arrow-repeat"
                actions="<span class='badge badge-soft-warning'>فاصله فعلی: {{ fa_num($review->interval_days) }} روز</span>">
            @if ($item instanceof \App\Models\Question)
                <div class="q-text wysiwyg-content mb-3">{{ $item->text }}</div>
                @if ($item->type === 'multi')
                    <div class="options mb-3" id="reviewOptions">
                        @foreach ($item->options as $option)
                            <div class="option-item" data-selectable data-correct="{{ $option->is_correct ? '1' : '0' }}">
                                <span class="badge bg-secondary">{{ ['۱','۲','۳','۴'][$option->order - 1] ?? $option->order }}</span>
                                <span class="flex-grow-1">{{ $option->text }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="d-none" id="reviewAnswer">
                    <div class="alert alert-info small">
                        @if ($item->explanation)
                            <strong>توضیح:</strong> {{ $item->explanation }}
                        @endif
                        @if ($item->answer_text)
                            <div class="mt-1"><strong>پاسخ تشریحی:</strong> {{ $item->answer_text }}</div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('review.submit') }}">
                        @csrf
                        <input type="hidden" name="review_id" value="{{ $review->id }}">
                        <p class="small text-muted mb-2">کیفیت یادآوری شما؟</p>
                        <div class="row g-2">
                            <div class="col-3"><button name="quality" value="1" class="btn btn-danger w-100">دوباره</button></div>
                            <div class="col-3"><button name="quality" value="2" class="btn btn-warning w-100 text-white">سخت</button></div>
                            <div class="col-3"><button name="quality" value="3" class="btn btn-primary w-100">خوب</button></div>
                            <div class="col-3"><button name="quality" value="4" class="btn btn-success w-100">آسان</button></div>
                        </div>
                    </form>
                </div>
                <button id="showAnswerBtn" class="btn btn-outline-primary">نمایش پاسخ</button>
            @else
                <h5 class="fw-bold">{{ $item?->title }}</h5>
                <div class="wysiwyg-content">{{ $item?->content }}</div>
                <div class="d-none" id="reviewAnswer">
                    <form method="POST" action="{{ route('review.submit') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="review_id" value="{{ $review->id }}">
                        <p class="small text-muted mb-2">مطلب را به خاطر آوردید؟</p>
                        <div class="row g-2">
                            <div class="col-3"><button name="quality" value="1" class="btn btn-danger w-100">دوباره</button></div>
                            <div class="col-3"><button name="quality" value="2" class="btn btn-warning w-100 text-white">سخت</button></div>
                            <div class="col-3"><button name="quality" value="3" class="btn btn-primary w-100">خوب</button></div>
                            <div class="col-3"><button name="quality" value="4" class="btn btn-success w-100">آسان</button></div>
                        </div>
                    </form>
                </div>
                <button id="showAnswerBtn" class="btn btn-outline-primary mt-3">نمایش پاسخ</button>
            @endif
        </x-card>

        <div class="text-muted small">مرورهای انجام‌شده این مطلب: {{ fa_num($review->total_reviews) }} — فراموشی: {{ fa_num($review->lapses) }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('showAnswerBtn').addEventListener('click', function () {
    document.querySelectorAll('.option-item[data-selectable]').forEach(function (o) {
        if (o.dataset.correct === '1') o.classList.add('correct');
    });
    document.getElementById('reviewAnswer').classList.remove('d-none');
    this.remove();
});
</script>
@endpush
